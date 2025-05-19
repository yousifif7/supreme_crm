<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Holiday;
use App\Models\User;
use App\Models\VisaType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('id', 'desc')->paginate(15);
        $departments = Department::all();
        $visa_types = VisaType::all();
        $employee_types = EmployeeType::all();
        return view('employees.index', compact('employees', 'departments', 'visa_types', 'employee_types'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'fore_name' => 'required|string',
            'sur_name' => 'required|string',
            'email' => 'required|email',
            'gender' => 'required|string',
            'ni_number' => 'required|string',
            'sia_licence' => 'required|string',
            'sia_expiry' => 'required',
            'licence_type' => 'required|string',
            'entry_date' => 'required',
            'dob' => 'required',
            'service_type' => 'nullable',
            'visa_type' => 'required',
            'visa_expiry' => 'required',
            'place_work' => 'required',
            'hour_per_week' => 'required',
            'passport_no' => 'required',
            'passport_expiry' => 'required',
            'address_group' => 'nullable',
            'address_group_additional' => 'nullable',
            'contact' => 'nullable',
            'emergency_contact' => 'nullable',
            'job_title' => 'nullable',
            'nationality' => 'nullable',
            'pin' => 'required',
            'reference_to_emp' => 'nullable',
            'relation_with_kin' => 'nullable',
            'kin_address' => 'nullable',
            'kin_number' => 'nullable',
            'kin_work_tel' => 'nullable',
            'kin_mobile' => 'nullable',
            'share_code' => 'required',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'required',
            'tags' => 'nullable',
            'additional_sia_number' => 'nullable',
            'license_expiry' => 'nullable',
            'license_number' => 'nullable',
            'dbs_confirmed' => 'nullable',
            'prfoile_picture' => 'nullable|image',
            'employee_type' => 'nullable',
            'collar' => 'nullable',
            'waist' => 'nullable',
            'jacket' => 'nullable',
            'shoe' => 'nullable',
            'inseam' => 'nullable',
            'signature' => 'nullable|file',
            'guard_rate' => 'nullable',
            'payment_period' => 'nullable',
            'fixed_pay' => 'nullable',
            'account_name' => 'nullable',
            'account_number' => 'nullable',
            'sort_code' => 'nullable',
            'bank_name' => 'nullable',
            'bank_branch' => 'nullable',
            'other_info' => 'nullable',
            'driving_license' => 'nullable',
            'visa_to_work' => 'nullable',
            'vehicle_in_use' => 'nullable',
            'current_endorsement' => 'nullable',
            'holidays' => 'nullable|array',
            'holidays.*.from' => 'nullable|date',
            'holidays.*.to' => 'nullable|date',
            'holidays.*.entitlement' => 'nullable',
            'username'        => 'required|email',          // Assuming username is email for user
            'password'        => 'required|string|min:6',   // Add password validation
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        // ✅ Handle the checkbox manually
        // Handle files...
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_pics'), $imageName);
            $data['profile_picture'] = $imageName;
        }

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $fileName = time() . '_signature.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/signatures'), $fileName);
            $data['signature'] = $fileName;
        }

        // Create user with hashed password
        $user = User::create([
            'name' => $data['fore_name'],
            'username' => $data['sur_name'],
            'email' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        // Check if 'client' role exists, if not create it
        $role = Role::firstOrCreate(['name' => 'security_staff']);

        // Assign role to user
        $user->assignRole($role);
        $data['user_id'] = $user->id;

        // Prepare employee data by excluding user-related fields
        $employeeData = $data;
        unset($employeeData['username'], $employeeData['password']);

        // Save employee
        $employee = Employee::create($employeeData);
        if ($request->has('holidays')) {
            foreach ($request->holidays as $holiday) {
                Holiday::create([
                    'employee_id' => $employee->id,
                    'from_date' => $holiday['from'],
                    'to_date' => $holiday['to'],
                    'holidays_entitement' => $holiday['entitlement'],
                ]);
            }
        }

        return response()->json(['message' => 'Employee created successfully']);
    }
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'fore_name' => 'required|string',
            'sur_name' => 'required|string',
            'email' => 'required|email',
            'gender' => 'required|string',
            'ni_number' => 'required|string',
            'sia_licence' => 'required|string',
            'sia_expiry' => 'required',
            'licence_type' => 'required|string',
            'entry_date' => 'required',
            'dob' => 'required',
            'service_type' => 'nullable',
            'visa_type' => 'required',
            'visa_expiry' => 'required',
            'place_work' => 'required',
            'hour_per_week' => 'required',
            'passport_no' => 'required',
            'passport_expiry' => 'required',
            'address_group' => 'nullable',
            'address_group_additional' => 'nullable',
            'contact' => 'nullable',
            'emergency_contact' => 'nullable',
            'job_title' => 'nullable',
            'nationality' => 'nullable',
            'pin' => 'required',
            'reference_to_emp' => 'nullable',
            'relation_with_kin' => 'nullable',
            'kin_address' => 'nullable',
            'kin_number' => 'nullable',
            'kin_work_tel' => 'nullable',
            'kin_mobile' => 'nullable',
            'share_code' => 'required',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'required',
            'tags' => 'nullable',
            'additional_sia_number' => 'nullable',
            'license_expiry' => 'nullable',
            'license_number' => 'nullable',
            'dbs_confirmed' => 'nullable',
            'prfoile_picture' => 'nullable|image',
            'employee_type' => 'nullable',
            'collar' => 'nullable',
            'waist' => 'nullable',
            'jacket' => 'nullable',
            'shoe' => 'nullable',
            'inseam' => 'nullable',
            'signature' => 'nullable|file',
            'guard_rate' => 'nullable',
            'payment_period' => 'nullable',
            'fixed_pay' => 'nullable',
            'account_name' => 'nullable',
            'account_number' => 'nullable',
            'sort_code' => 'nullable',
            'bank_name' => 'nullable',
            'bank_branch' => 'nullable',
            'other_info' => 'nullable',
            'driving_license' => 'nullable',
            'visa_to_work' => 'nullable',
            'vehicle_in_use' => 'nullable',
            'current_endorsement' => 'nullable',
            'holidays' => 'nullable|array',
            'holidays.*.from' => 'nullable|date',
            'holidays.*.to' => 'nullable|date',
            'holidays.*.entitlement' => 'nullable',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        // Handle profile picture update
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_pics'), $imageName);
            $data['profile_picture'] = $imageName;
        }

        // Handle signature update
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $fileName = time() . '_signature.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/signatures'), $fileName);
            $data['signature'] = $fileName;
        }

        // Update the employee record
        $employee->update($data);
        // Update employee holidays
        if ($request->has('holidays')) {
            // Delete existing holidays for clean sync
            $employee->holidays()->delete();

            // Re-create holidays from request
            foreach ($request->holidays as $holiday) {
                if (!empty($holiday['from']) && !empty($holiday['to'])) {
                    $employee->holidays()->create([
                        'from_date' => $holiday['from'],
                        'to_date' => $holiday['to'],
                        'holidays_entitement' => $holiday['entitlement'] ?? 0,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Employee updated successfully']);
    }

    public function edit($id)
    {
        $employee = Employee::with('holidays')->find($id);

        return response()->json([
            'employee' => $employee,
            'holidays' => $employee->holidays
        ]);
    }
    public function delete($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json(['success' => true]);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        Employee::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected employees deleted.']);
    }
}
