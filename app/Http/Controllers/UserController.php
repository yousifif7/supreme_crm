<?php

namespace App\Http\Controllers;

use App\DataTables\UsersDataTable;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\ShiftDate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function dashboard()
    {
        $shifts = ShiftDate::where('shift_date', Carbon::today()->toDateString())->with('shift.staff')->get();
        $invoices = Invoice::all();
        $review = ShiftDate::where('is_assign', '1')->count();
        $clients = Client::all();
        $staffs = Employee::all();
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

        return view('dashboard', compact('clients', 'staffs', 'shifts', 'invoices', 'review', 'clientgrowthPercentage', 'employeegrowthPercentage', 'invoicerowthPercentage', 'reviewrowthPercentage'));
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
            'username' => 'required|string|unique:users,username',
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
            'username' => 'required|string|unique:users,username,' . $id,
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
        $user = User::findOrFail($userId);
        $user->delete();

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
}
