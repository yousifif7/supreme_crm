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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function dashboard()
    {
        $shifts = ShiftDate::where('shift_date', Carbon::today()->toDateString())->with('shift.staff')->get();
        $invoices = Invoice::with(['client', 'site'])
            ->whereNotNull('client_id')->get();
        $review = ShiftDate::where('is_assign', '1')->count();
        $clients = Client::all();
        $staffs = User::role('security_staff')->get();

        $checkCalls = CheckCall::with('shiftDate')
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

        $bookings = ShiftBooking::with('shift')
            ->whereHas('shift')
            ->orderBy('timestamp', 'desc')
            ->take(10)
            ->get();

        $today = Carbon::today();

        $siaDocuments = Employee::whereNotNull('sia_licence')
            ->whereDate('sia_expiry', '<', Carbon::today()->toDateString())
            ->select('fore_name', 'sur_name', 'sia_expiry', 'sia_licence_file')
            ->paginate(10);

        // Get the full datetime range for this week
        $startOfThisWeek = Carbon::now()->startOfWeek()->startOfDay(); // Monday 00:00:00
        $endOfThisWeek = Carbon::now()->endOfWeek()->endOfDay();       // Sunday 23:59:59

        // Get the full datetime range for last week
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek()->startOfDay();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek()->endOfDay();

        // Query clients
        $clientsThisWeek = Client::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $clientsLastWeek = Client::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();
        // Query employees
        $employeesThisWeek = Employee::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $employeesLastWeek = Employee::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();
        // Query invoices
        $invoicesThisWeek = Invoice::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $invoicesLastWeek = Invoice::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();
        // Query review
        $reviewThisWeek = ShiftDate::where('is_assign', '1')->whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $reviewLastWeek = ShiftDate::where('is_assign', '1')->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();
        // Calculate growth
        if ($clientsLastWeek > 0) {
            $growthPercentage = (($clientsThisWeek - $clientsLastWeek) / $clientsLastWeek) * 100;
        } else {
            $growthPercentage = $clientsThisWeek > 0 ? 100 : 0;
        }

        $clientgrowthPercentage = round($growthPercentage, 2);
        if ($employeesLastWeek > 0) {
            $employeegrowthPercentage = (($employeesThisWeek - $employeesLastWeek) / $employeesLastWeek) * 100;
        } else {
            $employeegrowthPercentage = $employeesThisWeek > 0 ? 100 : 0;
        }
        $employeerowthPercentage = round($employeegrowthPercentage, 2);

        if ($invoicesLastWeek > 0) {
            $invoicegrowthPercentage = (($invoicesThisWeek - $invoicesLastWeek) / $invoicesLastWeek) * 100;
        } else {
            $invoicegrowthPercentage = $invoicesThisWeek > 0 ? 100 : 0;
        }
        $invoicerowthPercentage = round($invoicegrowthPercentage, 2);

        if ($reviewLastWeek > 0) {
            $reviewgrowthPercentage = (($reviewThisWeek - $reviewLastWeek) / $reviewLastWeek) * 100;
        } else {
            $reviewgrowthPercentage = $reviewThisWeek > 0 ? 100 : 0;
        }
        $reviewrowthPercentage = round($reviewgrowthPercentage, 2);

        // Checking for missed shifts 
        $now = now();

        // Checking for missed shifts 
        $now = now();

        // --- Missed Book On Notifications ---
        $missedBookOns = Shift::whereNotNull('staff_id')
            ->whereNull('book_in_time')
            ->whereDate('from_shift', '<=', $now->toDateString())
            ->whereTime('start_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->where('missed_book_on_notified', false) // prevent repeats
            ->get();

        foreach ($missedBookOns as $shift) {
            $employee = Employee::find($shift->staff_id);
            $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

            Notify::toDashboard(
                $employee->id,
                'alarm',
                'Missed Book On',
                "Guard {$guardName} did not book on for their shift starting at {$shift->start_shift} on {$shift->from_shift}.",
                "employees?$employee->id"
            );

            // ✅ Mark as notified
            $shift->update(['missed_book_on_notified' => true]);
        }

        // --- Missed Book Off Notifications ---
        $missedBookOffs = Shift::whereNotNull('staff_id')
            ->whereNull('book_off_time')
            ->whereDate('to_shift', '<=', $now->toDateString())
            ->whereTime('end_shift', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->where('missed_book_off_notified', false)
            ->get();

        foreach ($missedBookOffs as $shift) {
            $employee = Employee::find($shift->staff_id);
            $guardName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'Unknown';

            Notify::toDashboard(
                $employee->id,
                'alarm',
                'Missed Book Off',
                "Guard {$guardName} did not book off for their shift ending at {$shift->end_shift} on {$shift->to_shift}.",
                "/scheduling"
            );

            $shift->update(['missed_book_off_notified' => true]);
        }

        // --- Unassigned Shift Starting Soon ---
        $unassignedShifts = Shift::whereNull('staff_id')
            ->whereDate('from_shift', '=', $now->toDateString())
            ->whereTime('start_shift', '>=', $now->format('H:i:s'))
            ->whereTime('start_shift', '<=', $now->copy()->addHour()->format('H:i:s'))
            ->where('unassigned_shift_notified', false)
            ->get();

        foreach ($unassignedShifts as $shift) {
            Notify::toDashboard(
                null,
                'alarm',
                'Unassigned Shift',
                "A shift at {$shift->start_shift} on {$shift->from_shift} is starting soon and no guard has been assigned.",
                "/scheduling?shift_date_id=$shift->id"
            );

            $shift->update(['unassigned_shift_notified' => true]);
        }
        // --- Users (latest locations) ---
        $userLocations = Location::with([
            'user:id,first_name,last_name',
            'user.employee:id,user_id,service_type',
        ])
            ->whereNotNull('latitude') // ensure only real locations
            ->whereNotNull('longitude')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('locations')
                    ->whereNotNull('user_id')
                    ->groupBy('user_id');
            })
            ->get()
            ->map(function ($l) {
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

        // --- Sites (pass postal codes only, no server-side geocoding) ---
        $sites = Site::whereHas('shifts', function ($query) {
            $query->whereHas('shiftDates', function ($query) {
                $query->whereNotNull('staff_id');
            });
        })->select('id', 'site_name', 'post_code')->get();

        $siteLocations = $sites->map(function ($site) {
            return [
                'id' => 'site-' . $site->id,
                'name' => $site->site_name,
                'postalcode' => $site->post_code,
                'type' => 'site',
            ];
        });

        $this->weeklyHoursNotification();
        // --- Merge users and sites for frontend ---
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        return view('dashboard', compact('apiKey', 'siaDocuments', 'bookings', 'checkCalls', 'clients', 'staffs', 'shifts', 'invoices', 'review', 'clientgrowthPercentage', 'employeegrowthPercentage', 'invoicerowthPercentage', 'reviewrowthPercentage', 'userLocations', 'siteLocations'));
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
            'email' => 'required|email:dns|unique:users,email',
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
        }

        $user = User::create($validated);

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
            'email' => 'required|email:dns|unique:users,email,' . $id,
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
        }

        $user->update($validated);

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

        $user->forceDelete();

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

        // ✅ Prevent duplicate runs: cache key per calendar week (ISO week) so it runs once per week
        $isoYear = $today->isoFormat('GGGG'); // ISO week-year
        $isoWeek = $today->isoFormat('WW');   // ISO week number
        $cacheKey = "weekly_hours_notification_{$isoYear}_week_{$isoWeek}";

        if (Cache::has($cacheKey)) {
            return; // Already sent this week
        }

        $weekStart = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
        $weekEnd   = $today->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');

        // Normalize status check by lowercasing column comparison to be resilient to casing
        $guards = \App\Models\Employee::whereRaw('LOWER(status) = ?', ['active'])->get();

        foreach ($guards as $staff) {
            $entity = $staff->entity;
            $minWeeklyHours = $entity->hour_per_week ?? 40;

            $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staff->user_id)
                ->whereBetween('shift_date', [$weekStart, $weekEnd])
                ->sum('total_hours');

            if ($totalWeekHours < $minWeeklyHours) {
                $expectedHours = $totalWeekHours;

                Notify::toDashboard(
                    null,
                    'alert',
                    'Worked Hours',
                    "Guard {$staff->fore_name} {$staff->sur_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                    "#"
                );
            }
        }

        // ✅ Store flag in cache until the end of the ISO week (Sunday 23:59:59) to be safe
        // Calculate TTL as seconds until end of week
        $endOfWeek = $today->copy()->endOfWeek();
        $ttlSeconds = $endOfWeek->diffInSeconds($today);
        // Put cache with TTL (seconds)
        Cache::put($cacheKey, true, $ttlSeconds);
    }
}
