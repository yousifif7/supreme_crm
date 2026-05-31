<?php

namespace App\Http\Controllers;

use Notify;
use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Helpers\Logger;
use App\Models\Invoice;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use Illuminate\Support\Str;
use App\Models\BookingAlarm;
use App\Models\Notification;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Services\FileCompressor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function dashboard()
    {
        if(auth()->user()->hasRole('client')){
            return redirect()->route('client.dashboard');
        }

        // Lightweight instrumentation: measure dashboard duration
        $dashboardStart = microtime(true);

        $this->pruneOldNotifications();
        $this->pruneOldLogs();

        
        // Today's shift dates: select only needed columns to reduce payload
        $shifts = ShiftDate::whereDate('shift_date', Carbon::today()->toDateString())
            ->select('id', 'shift_id', 'staff_id', 'shift_date', 'start_time', 'end_time')
            ->with([
                'shift'
            ])->get();

        // Use direct count queries (avoid fetching entire collections)
        $invoices = Invoice::whereNotNull('client_id')->count();
        $review = ShiftDate::where('is_assign', '1')->count();
        $clients = Client::count();
        // Count staff using Employee model so dashboard matches Employees area
        $staffs = Employee::count();

        // Today only — CheckCalls whose scheduled_time falls inside today's window.
        // Filter the CheckCall row itself (not the shiftDate) so we don't pull
        // back any check call from a multi-day shift that isn't actually due today.
        // Note: image_path is NOT a column on check_calls (evidence lives in
        // the check_call_media table); the Blade reads $checkCall->image_path
        // but that just resolves to null. Don't add it to the select.
        $checkCalls = CheckCall::select('id', 'shift_id', 'name', 'scheduled_time', 'status', 'method', 'employee_id')
            ->with([
                'shiftDate' => function ($q) {
                    $q->select('id', 'shift_date', 'shift_id');
                },
                // One evidence file per check call. Column list intentionally
                // omitted: oldestOfMany() self-joins the media table, so any
                // unqualified column name (e.g. "check_call_id") becomes
                // ambiguous in the generated SQL. The table is tiny so the
                // extra columns (timestamps) cost nothing.
                'firstMedia',
            ])
            ->whereIn('status', ['pending', 'missed', 'completed'])
            ->whereBetween('scheduled_time', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->orderBy('scheduled_time', 'desc')
            ->limit(50)
            ->get();

        $now = Carbon::now();

        $bookings = ShiftBooking::select('id', 'user_id', 'shift_id', 'type', 'timestamp')
            ->with(['shift'])
            ->whereHas('shift')
            ->orderBy('timestamp', 'desc')
            ->take(10)
            ->get();

        $today = Carbon::today();

        // SIA — return the full list (capped) for client-side pagination in the
        // dashboard. Server-side pagination caused a full page reload per click.
        $siaDocuments = Employee::whereNotNull('sia_licence')
            ->whereDate('sia_expiry', '<', Carbon::today()->toDateString())
            ->select('fore_name', 'sur_name', 'sia_expiry', 'sia_licence_file')
            ->orderBy('fore_name', 'asc')
            ->get();


        
        // --- Users (latest locations) ---
        // Cache this expensive lookup for a short period (5m) to improve dashboard response.
        //
        // The map must show every guard who is on an ACTIVE shift right now — even if
        // their phone hasn't pinged GPS recently. So we no longer gate the map on a
        // short "recent ping" window (which silently dropped working guards whose app
        // was backgrounded or had poor signal). Instead we build the set of users to
        // show from two sources and union them:
        //   1. Anyone on a shift that is active at this moment (start <= now <= end today,
        //      overnight-aware), and
        //   2. Anyone who has pinged a location recently (keeps recently-active guards
        //      visible even between shifts).
        // For each such user we plot their LATEST known location, regardless of age.
        $recentCutoff = Carbon::now()->subMinutes(60);
        $dashboardAuthUser = auth()->user();
        $dashboardAdminId = ($dashboardAuthUser && $dashboardAuthUser->hasRole('admin')) ? $dashboardAuthUser->id : null;

        // Cache key includes admin ID so different admins never share cached map data
        $cacheKeyUsers = 'dashboard_user_locations_' . ($dashboardAdminId ?? 'all') . '_' . Carbon::today()->toDateString();
        $userLocations = Cache::remember($cacheKeyUsers, 300, function () use ($recentCutoff, $dashboardAdminId) {
            $now = Carbon::now();

            // For admin: restrict map pins to users belonging to this admin only.
            $adminUserIds = null;
            if ($dashboardAdminId !== null) {
                $adminUserIds = DB::table('users')
                    ->where('admin_id', $dashboardAdminId)
                    ->pluck('id')
                    ->toArray();
            }

            // (1) Shift dates with an assigned guard, with their shift+site.
            // We include today AND yesterday so overnight shifts that started
            // yesterday (e.g. 22:00 -> 06:00) and are still running now are caught.
            // "Active right now" is resolved in PHP (shiftIsActiveNow anchors each
            // row to its own shift_date and rolls the end over for overnight shifts).
            $shiftDatesQuery = ShiftDate::whereIn('shift_date', [
                    Carbon::yesterday()->toDateString(),
                    Carbon::today()->toDateString(),
                ])
                ->whereNotNull('staff_id')
                ->with(['shift.site']);
            if ($adminUserIds !== null) {
                $shiftDatesQuery->whereIn('staff_id', $adminUserIds);
            }
            $todayShiftDates = $shiftDatesQuery->get()->groupBy('staff_id');

            // Determine which staff are on an ACTIVE shift right now, and remember the
            // specific active shift date to show in the info window.
            $activeShiftByStaff = [];
            foreach ($todayShiftDates as $staffId => $group) {
                foreach ($group as $sd) {
                    if ($this->shiftIsActiveNow($sd, $now)) {
                        $activeShiftByStaff[$staffId] = $sd;
                        break;
                    }
                }
            }

            // (2) Users who pinged a location recently — keep them on the map too.
            $recentPingUserIds = DB::table('locations')
                ->whereNotNull('user_id')
                ->where('created_at', '>=', $recentCutoff)
                ->when($adminUserIds !== null, fn ($q) => $q->whereIn('user_id', $adminUserIds))
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            // Union of staff to plot: on-active-shift now OR recently pinging.
            $userIds = collect(array_keys($activeShiftByStaff))
                ->merge($recentPingUserIds)
                ->unique()
                ->filter()
                ->values()
                ->all();

            if (empty($userIds)) {
                return collect();
            }

            // Latest location row per user (any age) for the users we want to plot.
            $latest = DB::table('locations')
                ->selectRaw('user_id, MAX(id) as max_id')
                ->whereNotNull('user_id')
                ->whereIn('user_id', $userIds)
                ->groupBy('user_id');

            $locations = Location::select('locations.id', 'locations.user_id', 'locations.latitude', 'locations.longitude', 'locations.accuracy', 'locations.on_duty', 'locations.created_at')
                ->joinSub($latest, 'latest', function ($join) {
                    $join->on('locations.id', '=', 'latest.max_id');
                })
                ->with([
                    'user:id,first_name,last_name',
                    'user.employee:id,user_id,service_type',
                ])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->keyBy('user_id');

            // Map of employee_types id => name and a name => icon lookup, resolved once.
            $serviceIcons = $this->serviceTypeIconMap();

            return collect($userIds)->map(function ($userId) use ($locations, $activeShiftByStaff, $todayShiftDates, $serviceIcons) {
                $l = $locations->get($userId);
                if (!$l) {
                    // Guard is on an active shift but has no plottable location yet.
                    return null;
                }

                // Prefer the shift that is active right now; otherwise fall back to any
                // of today's shifts for this guard (so the info window still shows a site).
                $assignedShift = $activeShiftByStaff[$userId] ?? null;
                if (!$assignedShift && isset($todayShiftDates[$userId])) {
                    $assignedShift = $todayShiftDates[$userId]->first();
                }

                $siteName = null;
                if ($assignedShift && optional($assignedShift->shift)->site) {
                    $siteName = optional($assignedShift->shift->site)->site_name;
                }

                // Resolve the guard's service type (which may be stored as a numeric
                // employee_types id OR as a name, sometimes with stray punctuation)
                // to a canonical icon + clean name on the server, so the front end
                // never has to guess.
                $rawServiceType = optional(optional($l->user)->employee)->service_type;
                [$serviceName, $iconUrl] = $this->resolveServiceType($rawServiceType, $serviceIcons);

                return [
                    'id' => 'user-' . $l->user_id,
                    'latitude' => (float) $l->latitude,
                    'longitude' => (float) $l->longitude,
                    'name' => trim((optional($l->user)->first_name ?? '') . ' ' . (optional($l->user)->last_name ?? '')),
                    'type' => 'user',
                    'service_type_id' => $rawServiceType,
                    'service_name' => $serviceName,
                    'icon' => $iconUrl,
                    'accuracy' => $l->accuracy,
                    'on_duty' => isset($activeShiftByStaff[$userId]) ? true : (bool) $l->on_duty,
                    'timestamp' => optional($l->created_at)->toDateTimeString(),
                    'site_name' => $siteName,
                    'current_shift' => $assignedShift ? [
                        'id' => $assignedShift->id,
                        'shift_id' => $assignedShift->shift_id,
                        'shift_date' => $assignedShift->shift_date,
                        'start_time' => $assignedShift->start_time,
                        'end_time' => $assignedShift->end_time,
                        'site_name' => $siteName,
                        'site' => optional($assignedShift->shift)->site ? ['site_name' => optional($assignedShift->shift->site)->site_name] : null,
                    ] : null,
                ];
            })->filter()->values();
        });

        // --- Sites (pass postal codes only, no server-side geocoding) ---
        // Only include sites which have shifts with assigned staff in the last 7 days
        // $sevenDaysAgo = Carbon::now()->subDays(1)->startOfDay();
        // $sites = Site::query()
        //     ->select('sites.id', 'sites.site_name', 'sites.post_code', 'sites.address')
        //     ->join('shifts', 'shifts.site_id', '=', 'sites.id')
        //     ->join('shift_dates', 'shift_dates.shift_id', '=', 'shifts.id')
        //     ->whereNotNull('shift_dates.staff_id')
        //     ->where('shift_dates.shift_date', '>=', $sevenDaysAgo)
        //     ->distinct()
        //     ->get();

        // // Cache site locations for a slightly longer period (5 minutes)
        // $cacheKeySites = 'dashboard_site_locations_' . ($dashboardAdminId ?? 'all') . '_' . now()->startOfDay()->toDateString();
        // $siteLocations = Cache::remember($cacheKeySites, 300, function () use ($sites) {
        //     return $sites->map(function ($site) {
        //         return [
        //             'id' => 'site-' . $site->id,
        //             'name' => $site->site_name,
        //             'postalcode' => $site->post_code,
        //             'address' => $site->address,
        //             'type' => 'site',
        //         ];
        //     });
        // });

        $this->weeklyHoursNotification();

        \Log::info('dashboard total duration: ' . round(microtime(true) - $dashboardStart, 3) . 's');

        // --- Merge users and sites for frontend ---
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        return view('dashboard', compact('apiKey', 'siaDocuments', 'bookings', 'checkCalls', 'clients', 'staffs', 'shifts', 'invoices', 'review', 'userLocations'));
    }

    /**
     * Is the given shift date active at $now? start_time/end_time are stored as
     * time-only (HH:MM:SS) against the shift_date day, so we anchor them to the
     * shift's date and roll the end to the next day for overnight shifts.
     */
    protected function shiftIsActiveNow(ShiftDate $sd, Carbon $now): bool
    {
        if (empty($sd->start_time) || empty($sd->end_time)) {
            return false;
        }

        try {
            $day = $sd->shift_date ? Carbon::parse($sd->shift_date)->toDateString() : $now->toDateString();
            $start = Carbon::parse($day . ' ' . $sd->start_time);
            $end = Carbon::parse($day . ' ' . $sd->end_time);
            // Overnight shift: end time falls on the following day.
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }
            return $now->betweenIncluded($start, $end);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Build the canonical service-type icon lookup once. Keyed by:
     *   - employee_types id (int)
     *   - normalized name (lowercased, alphanumerics only)
     * so we can resolve a guard's service type whether it's stored as an id or a
     * (sometimes messy) name. Returns ['byId' => [...], 'byName' => [...]].
     */
    protected function serviceTypeIconMap(): array
    {
        // Maps a normalized service-type name to its icon file in /public/guard_icons.
        // There is no dedicated "static guards" icon, so it reuses event_staff (the
        // same fallback the previous front-end map used).
        $nameToIcon = [
            'alarmresponse'    => '/guard_icons/alarm_response.png',
            'doghandlers'      => '/guard_icons/doghandlers.png',
            'eventstaff'       => '/guard_icons/event_staff.png',
            'keyholding'       => '/guard_icons/key_holding.png',
            'mobilepatrol'     => '/guard_icons/mobile_patrol.png',
            'mobilepetrol'     => '/guard_icons/mobile_patrol.png', // seeded name has this spelling
            'staticguards'     => '/guard_icons/event_staff.png',
            'firewarden'       => '/guard_icons/fire_warden.png',
            'closeprotection'  => '/guard_icons/close_protection.png',
        ];

        $byId = [];
        try {
            foreach (EmployeeType::select('id', 'name')->get() as $type) {
                $key = $this->normalizeServiceKey($type->name);
                $byId[(int) $type->id] = [
                    'name' => $type->name,
                    'icon' => $nameToIcon[$key] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // If the table is unavailable, fall back to name-only resolution.
        }

        return ['byId' => $byId, 'byName' => $nameToIcon];
    }

    /**
     * Resolve a raw service_type value (id-as-string, a name, or a name with stray
     * punctuation/whitespace) to a [displayName, iconUrl] pair using the prepared map.
     */
    protected function resolveServiceType($rawServiceType, array $serviceIcons): array
    {
        if ($rawServiceType === null || $rawServiceType === '') {
            return [null, null];
        }

        $raw = trim((string) $rawServiceType, " \t\n\r\0\x0B,"); // strip stray commas/whitespace

        // Stored as a numeric employee_types id.
        if (ctype_digit($raw) && isset($serviceIcons['byId'][(int) $raw])) {
            $entry = $serviceIcons['byId'][(int) $raw];
            return [$entry['name'], $entry['icon']];
        }

        // Stored as a name — resolve via the normalized name map.
        $key = $this->normalizeServiceKey($raw);
        if (isset($serviceIcons['byName'][$key])) {
            return [$raw, $serviceIcons['byName'][$key]];
        }

        // Unknown service type: show the cleaned name with no icon (front end draws a dot).
        return [$raw, null];
    }

    /**
     * Normalize a service-type name for lookup: lowercase, alphanumerics only.
     * "Mobile Patrol" / "mobile  patrol," / "MOBILE-PATROL" => "mobilepatrol".
     */
    protected function normalizeServiceKey($name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
    }

    public function index(UsersDataTable $dataTable)
    {
        $roles = $this->availableRolesForCurrentUser();
        return $dataTable->render('user_management.users', compact('roles'));
    }

    public function create()
    {
        $roles = $this->availableRolesForCurrentUser();
        return view('role-permission.user.create', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $adminId   = $authUser->hasRole('admin') ? $authUser->id : null;

        $emailRule = Rule::unique('users', 'email')
            ->whereNull('deleted_at')
            ->when($adminId !== null,
                fn ($q) => $q->where('admin_id', $adminId),
                fn ($q) => $q->whereNull('admin_id')
            );

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            // 'username' => 'required|string|unique:users,username',
            'email' => ['required', 'email:dns', $emailRule],
            'password' => 'required|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'nullable|string',
            'roles' => 'nullable|array',
            'profile_picture' => 'nullable|image|max:4096'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $validated = $validator->validated();

        $plain_password = $validated['password'];
        $validated['password'] = Hash::make($validated['password']);
        $validated['username'] = $validated['email'];

        

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $uploadPath = public_path('uploads/profile_pictures');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $file = $request->file('profile_picture');
            $fileName = time() . '_profile.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);

            $validated['profile_picture'] = $fileName;
            try {
                (new FileCompressor())->compress($uploadPath . '/' . $fileName);
            } catch (\Exception $e) {
                Log::error('File compression failed for user profile_picture: ' . $e->getMessage());
            }
        }

        $user = User::create($validated);
        $user->plaintext_password = $plain_password;
        // Persist plaintext password field (if present on model/table)
        try {
            $user->save();
        } catch (\Exception $e) {
            Log::error('Failed to save plaintext password for new user: ' . $e->getMessage());
        }

        $requestedRoles = [];
        if (!empty($validated['roles'])) {
            $requestedRoles = is_array($validated['roles']) ? $validated['roles'] : [$validated['roles']];

            if ($authUser && $authUser->hasRole('admin') && in_array('superadmin', $requestedRoles, true)) {
                return response()->json(['message' => 'Admins cannot assign superadmin role.'], 403);
            }

            $assignableRoles = $this->filterAssignableRolesForCurrentUser($requestedRoles);
            if (!empty($assignableRoles)) {
                $user->assignRole($assignableRoles);
            }
        }

        // If an admin user is created outside an admin-owned context, make it self-owned.
        if (in_array('admin', $requestedRoles, true) && is_null($user->admin_id)) {
            $user->admin_id = $user->id;
            $user->save();
        }
        Logger::log(Auth::user(), 'Create', 'New user ' . $user->first_name . ' ' . $user->last_name);

        return response()->json(['message' => 'User created successfully']);
    }
    public function edit($id)
    {
        $user = User::find($id);
        $roles = $this->availableRolesForCurrentUser();
        $userRoles = $user->roles->pluck('name', 'name')->all();
        return response()->json(['user' => $user, 'userRoles' => $userRoles]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $authUser   = auth()->user();
        $adminId    = $authUser->hasRole('admin') ? $authUser->id : null;

        $emailRule = Rule::unique('users', 'email')
            ->ignore($id)
            ->whereNull('deleted_at')
            ->when($adminId !== null,
                fn ($q) => $q->where('admin_id', $adminId),
                fn ($q) => $q->whereNull('admin_id')
            );

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            // 'username' => 'required|string|unique:users,username,' . $id,
            'email' => ['required', 'email:dns', $emailRule],
            'password' => 'nullable|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'nullable|string',
            'roles' => 'nullable',
            'profile_picture' => 'nullable|image|max:4096'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $validated = $validator->validated();
        $validated['username'] = Str::slug($validated['first_name'] . $validated['last_name']) . rand(1, 100);

        if (!empty($validated['password'])) {
            
            $user->plaintext_password = $validated['password'];

            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('profile_picture')) {
            $uploadPath = public_path('uploads/profile_pictures');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $file = $request->file('profile_picture');
            $fileName = time() . '_profile.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);

            $validated['profile_picture'] = $fileName;
            try {
                (new FileCompressor())->compress($uploadPath . '/' . $fileName);
            } catch (\Exception $e) {
                Log::error('File compression failed for user profile_picture (update): ' . $e->getMessage());
            }
        }

        $user->update($validated);
        // Persist plaintext password when updating (was assigned above if password provided)
        try {
            $user->save();
        } catch (\Exception $e) {
            Log::error('Failed to save plaintext password on user update: ' . $e->getMessage());
        }

        if (!empty($validated['roles'])) {
            $requestedRoles = is_array($validated['roles']) ? $validated['roles'] : [$validated['roles']];

            if ($authUser && $authUser->hasRole('admin') && in_array('superadmin', $requestedRoles, true)) {
                return response()->json(['message' => 'Admins cannot assign superadmin role.'], 403);
            }

            $assignableRoles = $this->filterAssignableRolesForCurrentUser($requestedRoles);
            $user->syncRoles($assignableRoles);
        }
        Logger::log(Auth::user(), 'Update', 'User ' . $user->first_name . ' ' . $user->last_name . ' Updated');

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy($userId)
    {
        // \Log::info("Destroy called for user: " . $userId);
        $user = User::findOrFail($userId);
        Logger::log(Auth::user(), 'Delete', 'User ' . $user->first_name . ' ' . $user->last_name . ' Deleted');

        $user->delete();

        // $stillExists = User::find($userId);
        // \Log::info('Still exists after delete? ' . ($stillExists ? 'YES' : 'NO'));
        return response()->json(['success' => true]);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $users = User::whereIn('id', $request->ids)->get();
        foreach ($users as $user) {
            Logger::log(Auth::user(), 'Delete', 'User ' . $user->first_name . ' ' . $user->last_name . ' Deleted');
            $user->delete();
        }

        return response()->json(['message' => 'Selected users deleted.']);
    }
    public function getLogs($id)
    {
        $user = User::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $user->logs->map(function ($log) {
                return [
                    'user_name' => $log->user_name,
                    'action' => $log->action,
                    'description' => $log->description,
                    'time' => $log->created_at->diffForHumans(),
                    'success' => 'success',
                ];
            })
        ]);
    }
    public function view($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'phone_number' => $user->phone_number,
            'status' => ucfirst($user->status),
            'profile_picture' => $user->profile_picture ? asset('uploads/profile_picture/' . $user->profile_picture) : null,
        ]);
    }

    // booking function 
    public function acknowledge(Request $request, $id)
    {
        $alarm = BookingAlarm::findOrFail($id);
        $alarm->acknowledged = true;
        $alarm->save();

        return response()->json(['success' => true]);
    }

    public function weeklyHoursNotification()
    {
        $today = \Carbon\Carbon::now();

        // Only run on Thursday
        if (!$today->isThursday()) {
            return;
        }

        // ✅ Prevent duplicate runs: cache key for this Thursday
        $cacheKey = 'weekly_hours_notification_' . $today->toDateString();

        if (Cache::has($cacheKey)) {
            return; // Already sent today
        }

        $weekStart = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
        $weekEnd   = $today->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');

        // Fetch guards (with entity) and aggregated hours in two queries (avoid N+1)
        $guards = \App\Models\Employee::where(function ($q) {
            $q->where('sia_status', 'Active')->orWhere('sia_status', 'valid');
        })->get()->keyBy('user_id');

        $hoursAgg = \DB::table('shift_dates')
            ->select('staff_id', \DB::raw('SUM(total_hours) as total_week'))
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->groupBy('staff_id')
            ->get()
            ->keyBy('staff_id');

        foreach ($guards as $userId => $staff) {
            $minWeeklyHours = $staff->hour_per_week ?? 40;

            $totalWeekHours = isset($hoursAgg[$userId]) ? (float)$hoursAgg[$userId]->total_week : 0;

            if ($totalWeekHours < $minWeeklyHours) {
                Notify::toDashboard(
                    null,
                    'alert',
                    'Worked Hours',
                    "Guard {$staff->fore_name} {$staff->sur_name} has only {$totalWeekHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                    "#"
                );
            }
        }

        // ✅ Store flag in cache until the end of Thursday (23:59:59)
        Cache::put($cacheKey, true, $today->copy()->endOfDay());
    }

    private function availableRolesForCurrentUser(): array
    {
        $query = Role::query();
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();

        if ($authUser && $authUser->hasRole('admin')) {
            $query->where('name', '!=', 'superadmin');
        }

        return $query->pluck('name', 'name')->all();
    }

    private function filterAssignableRolesForCurrentUser(array $roles): array
    {
        $roles = array_values(array_filter($roles, fn ($role) => !empty($role)));
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();

        if ($authUser && $authUser->hasRole('admin')) {
            return array_values(array_filter($roles, fn ($role) => $role !== 'superadmin'));
        }

        return $roles;
    }

    /**
     * Prune notifications older than 15 days. Runs at most once per day (cache-guarded).
     */
    private function pruneOldNotifications()
    {
        // Use a file-based lock in storage to avoid dependency on Cache driver availability
        $lockFile = storage_path('app/pruned_notifications_' . now()->format('Ymd') . '.lock');
        if (file_exists($lockFile)) {
            return; // already pruned today
        }

        try {
            $cutoff = now()->subDays(30)->toDateTimeString();

            // If there are too many rows, avoid doing heavy deletes inside request
            $estimate = \DB::table('notifications')->where('created_at', '<', $cutoff)->count();
            if ($estimate === 0) {
                // nothing to do
                return;
            }

            if ($estimate > 5000) {
                \Log::warning('Skipping notifications prune in-request: too many rows (' . $estimate . ').');
                return;
            }

            $batchSize = 1000;
            $totalDeleted = 0;
            do {
                $ids = \DB::table('notifications')
                    ->where('created_at', '<', $cutoff)
                    ->limit($batchSize)
                    ->pluck('id')
                    ->toArray();

                if (empty($ids)) {
                    break;
                }

                $deleted = \DB::table('notifications')->whereIn('id', $ids)->delete();
                $totalDeleted += (int)$deleted;

                // small pause to reduce IO pressure on very busy servers
                usleep(100000); // 100ms
            } while (count($ids) === $batchSize);

            \Log::info("Pruned {$totalDeleted} notifications older than 7 days.");
        } catch (\Exception $e) {
            // swallow — pruning is best-effort; log for visibility
            \Log::error('Notification pruning failed: ' . $e->getMessage());
        }

        // create the lock file to mark pruning done for today (best-effort)
        try {
            $dir = dirname($lockFile);
            if (!file_exists($dir)) {
                \Illuminate\Support\Facades\File::makeDirectory($dir, 0755, true);
            }
            @file_put_contents($lockFile, now()->toDateTimeString());
        } catch (\Exception $e) {
            \Log::warning('Failed to create prune lock file: ' . $e->getMessage());
        }
    }

    private function pruneOldLogs()
    {
        // Use a file-based lock in storage to avoid dependency on Cache driver availability
        $lockFile = storage_path('app/pruned_logs_' . now()->format('Ymd') . '.lock');
        if (file_exists($lockFile)) {
            return; // already pruned today
        }

        $cutoff = now()->subDays(300)->toDateTimeString();
        $batchSize = 1000; // delete in batches to avoid long locks / large transactions
        $totalDeleted = 0;

        try {
            // Use direct table deletes (avoid Eloquent events and memory pressure)
            do {
                $ids = \DB::table('logs')
                    ->where('created_at', '<', $cutoff)
                    ->limit($batchSize)
                    ->pluck('id')
                    ->toArray();

                if (empty($ids)) {
                    break;
                }

                $deleted = \DB::table('logs')->whereIn('id', $ids)->delete();
                $totalDeleted += (int)$deleted;

                // small pause to reduce IO pressure on very busy servers
                usleep(150000); // 150ms
            } while (count($ids) === $batchSize);

            \Log::info("Pruned {$totalDeleted} log rows older than 300 days.");
        } catch (\Exception $e) {
            // best-effort
            \Log::error('Prune old logs failed: ' . $e->getMessage());
        }

        // create the lock file to mark pruning done for today (best-effort)
        try {
            $dir = dirname($lockFile);
            if (!file_exists($dir)) {
                \Illuminate\Support\Facades\File::makeDirectory($dir, 0755, true);
            }
            @file_put_contents($lockFile, now()->toDateTimeString());
        } catch (\Exception $e) {
            \Log::warning('Failed to create prune lock file: ' . $e->getMessage());
        }
    }

}
