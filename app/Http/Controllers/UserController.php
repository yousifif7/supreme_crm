<?php

namespace App\Http\Controllers;

use Notify;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use Illuminate\Support\Str;
use App\Models\BookingAlarm;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function dashboard()
{
    $today = Carbon::today();

    $shifts = ShiftDate::where('shift_date', $today->toDateString())
        ->with('shift.staff')
        ->get();

    $invoices = Invoice::all();
    $review = ShiftDate::where('is_assign', '1')->count();
    $clients = Client::all();
    $staffs = User::role('security_staff')->get();

    $checkCalls = CheckCall::with(['shift.staff'])
        ->whereIn('status', ['pending', 'missed', 'completed'])
        ->orderBy('scheduled_time', 'asc')
        ->limit(10)
        ->get();

    $now = Carbon::now();

    $bookingAlarms = BookingAlarm::with(['shift.staff'])
        ->orderBy('scheduled_time')
        ->get()
        ->map(function ($alarm) use ($now) {
            if ($alarm->acknowledged) {
                $alarm->status = 'Submitted';
            } elseif ($alarm->scheduled_time < $now) {
                $alarm->status = 'Missed';
            } else {
                $alarm->status = 'Due';
            }
            return $alarm;
        });

    $siaDocuments = Employee::whereNotNull('sia_licence')
        ->whereDate('sia_expiry', '<', $today->toDateString())
        ->select('fore_name', 'sur_name', 'sia_expiry', 'sia_licence_file')
        ->paginate(5);

    // Date ranges for this week & last week
    $startOfThisWeek = Carbon::now()->startOfWeek()->startOfDay();
    $endOfThisWeek = Carbon::now()->endOfWeek()->endOfDay();

        if ($reviewLastWeek > 0) {
            $reviewgrowthPercentage = (($reviewThisWeek - $reviewLastWeek) / $reviewLastWeek) * 100;
        } else {
            $reviewgrowthPercentage = $reviewThisWeek > 0 ? 100 : 0;
        }
        $reviewrowthPercentage = round($reviewgrowthPercentage, 2);

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

    $locations = Location::with([
            'user:id,name',
            'user.employee:id,user_id,service_type',
        ])
        ->whereNotNull('user_id')
        ->whereHas('user')            // only locations with a valid user
        ->whereHas('user.employee')   // only users that have an employee row
        ->get()
        ->map(function ($l) {
            return [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'latitude' => (float) $l->latitude,
                'longitude' => (float) $l->longitude,
                'accuracy' => $l->accuracy,
                'on_duty' => (bool) $l->on_duty,
                'timestamp' => optional($l->created_at)->toDateTimeString(),
                'user' => [
                    'id' => optional($l->user)->id,
                    'name' => optional($l->user)->name ?? 'Unknown',
                ],
                // THIS is the key bit the front-end needs:
                'service_type_id' => optional(optional($l->user)->employee)->service_type, // e.g. 1..8
            ];
        });
        
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        return view('dashboard', compact('apiKey','siaDocuments', 'bookingAlarms', 'checkCalls', 'clients', 'staffs', 'shifts', 'invoices', 'review', 'clientgrowthPercentage', 'employeegrowthPercentage', 'invoicerowthPercentage', 'reviewrowthPercentage','locations'));
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
        $validated['username'] = Str::slug($validated['first_name'] . $validated['last_name']).rand(1,100);

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

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy($userId)
    {
        // \Log::info("Destroy called for user: " . $userId);
        $user = User::findOrFail($userId);
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

        User::whereIn('id', $request->ids)->delete();

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
}
