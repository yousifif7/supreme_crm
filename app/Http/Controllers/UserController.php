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
        $staffs = User::role('security_staff')->count();

        // Limit columns and eager-load only necessary shiftDate fields
        $checkCalls = CheckCall::select('id', 'shift_id', 'name', 'scheduled_time', 'status')
            ->with(['shiftDate' => function ($q) {
                $q->select('id', 'shift_date', 'shift_id');
            }])
            ->whereIn('status', ['pending', 'missed', 'completed'])
            ->whereHas('shiftDate', function ($q) {
                $q->whereBetween('scheduled_time', [
                    now()->startOfDay(),
                    now()->addDay()->endOfDay()
                ]);
            })
            ->orderBy('scheduled_time', 'desc')
            ->limit(20)
            ->get();

        $now = Carbon::now();

        $bookings = ShiftBooking::select('id', 'user_id', 'shift_id', 'type', 'timestamp')
            ->with(['shift'])
            ->whereHas('shift')
            ->orderBy('timestamp', 'desc')
            ->take(10)
            ->get();

        $today = Carbon::today();

        $siaDocuments = Employee::whereNotNull('sia_licence')
            ->whereDate('sia_expiry', '<', Carbon::today()->toDateString())
            ->select('fore_name', 'sur_name', 'sia_expiry', 'sia_licence_file')
            ->take(50)
            ->paginate(10);


        
        // --- Users (latest locations) ---
        // Cache this expensive lookup for a short period (5m) to improve dashboard response
        // Use a derived-table JOIN to let the database compute MAX(id) per user efficiently.
        $cutoff24 = Carbon::now()->subMinutes(60);
        $cacheKeyUsers = 'dashboard_user_locations_' . $cutoff24->toDateString();
        $userLocations = Cache::remember($cacheKeyUsers, 300, function () use ($cutoff24) {
            $latest = DB::table('locations')
                ->selectRaw('user_id, MAX(id) as max_id')
                ->whereNotNull('user_id')
                ->where('created_at', '>=', $cutoff24)
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
                ->get();

            return $locations->map(function ($l) {
                return [
                    'id' => 'user-' . $l->user_id,
                    'latitude' => (float) $l->latitude,
                    'longitude' => (float) $l->longitude,
                    'name' => optional($l->user)->first_name . ' ' . optional($l->user)->last_name,
                    'type' => 'user',
                    'service_type_id' => optional(optional($l->user)->employee)->service_type,
                    'accuracy' => $l->accuracy,
                    'on_duty' => (bool) $l->on_duty,
                    'timestamp' => optional($l->created_at)->toDateTimeString(),
                ];
            });
        });

        // --- Sites (pass postal codes only, no server-side geocoding) ---
        // Only include sites which have shifts with assigned staff in the last 7 days
        $sevenDaysAgo = Carbon::now()->subDays(1)->startOfDay();
        $sites = Site::query()
            ->select('sites.id', 'sites.site_name', 'sites.post_code', 'sites.address')
            ->join('shifts', 'shifts.site_id', '=', 'sites.id')
            ->join('shift_dates', 'shift_dates.shift_id', '=', 'shifts.id')
            ->whereNotNull('shift_dates.staff_id')
            ->where('shift_dates.shift_date', '>=', $sevenDaysAgo)
            ->distinct()
            ->get();

        // Cache site locations for a slightly longer period (5 minutes)
        $cacheKeySites = 'dashboard_site_locations_' . now()->startOfDay()->toDateString();
        $siteLocations = Cache::remember($cacheKeySites, 300, function () use ($sites) {
            return $sites->map(function ($site) {
                return [
                    'id' => 'site-' . $site->id,
                    'name' => $site->site_name,
                    'postalcode' => $site->post_code,
                    'address' => $site->address,
                    'type' => 'site',
                ];
            });
        });

        $this->weeklyHoursNotification();

        \Log::info('dashboard total duration: ' . round(microtime(true) - $dashboardStart, 3) . 's');

        // --- Merge users and sites for frontend ---
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        return view('dashboard', compact('apiKey', 'siaDocuments', 'bookings', 'checkCalls', 'clients', 'staffs', 'shifts', 'invoices', 'review', 'userLocations', 'siteLocations'));
    }

    public function index(UsersDataTable $dataTable)
    {
        $roles = Role::pluck('name', 'name')->all();
        return $dataTable->render('user_management.users', compact('roles'));
    }

    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('role-permission.user.create', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            // 'username' => 'required|string|unique:users,username',
            'email' => 'required|email:dns|unique:users,email,NULL,id,deleted_at,NULL',
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

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }
        Logger::log(Auth::user(), 'Create', 'New user ' . $user->first_name . ' ' . $user->last_name);

        return response()->json(['message' => 'User created successfully']);
    }
    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRoles = $user->roles->pluck('name', 'name')->all();
        return response()->json(['user' => $user, 'userRoles' => $userRoles]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            // 'username' => 'required|string|unique:users,username,' . $id,
            'email' => 'required|email:dns|unique:users,email,' . $id . ',id,deleted_at,NULL',
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
            $user->syncRoles($validated['roles']);
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
        })->with('entity')->get()->keyBy('user_id');

        $hoursAgg = \DB::table('shift_dates')
            ->select('staff_id', \DB::raw('SUM(total_hours) as total_week'))
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->groupBy('staff_id')
            ->get()
            ->keyBy('staff_id');

        foreach ($guards as $userId => $staff) {
            $entity = $staff->entity;
            $minWeeklyHours = $entity->hour_per_week ?? 40;

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
            $cutoff = now()->subDays(7)->toDateTimeString();

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

        $cutoff = now()->subDays(14)->toDateTimeString();
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

            \Log::info("Pruned {$totalDeleted} log rows older than 14 days.");
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
