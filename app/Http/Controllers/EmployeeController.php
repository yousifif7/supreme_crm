<?php

namespace App\Http\Controllers;

use App\DataTables\EmployeesDataTable;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Holiday;
use App\Models\License;
use App\Models\User;
use App\Models\VisaType;
use App\Models\EmployeeTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(EmployeesDataTable $dataTable)
    {
        $departments = Department::all();
        $visa_types = VisaType::all();
        $employee_types = EmployeeType::all();
        $licenses = License::all();

        return $dataTable->render('employees.index', compact('departments', 'visa_types', 'employee_types', 'licenses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'fore_name' => 'required|string',
            'sur_name' => 'required|string',
            'email' => 'required|email:dns|max:255|unique:users,email',
            'gender' => 'nullable|string',
            'ni_number' => 'nullable|string',
            'sia_licence' => 'nullable|string',
            'sia_expiry' => 'nullable',
            'licence_type' => 'nullable|string',
            'entry_date' => 'nullable',
            'dob' => 'nullable',
            'service_type' => 'nullable',
            'visa_type' => 'nullable',
            'visa_expiry' => 'nullable',
            'place_work' => 'nullable',
            'hour_per_week' => 'nullable',
            'passport_no' => 'nullable',
            'passport_expiry' => 'nullable',
            'address_group' => 'nullable',
            'address_group_additional' => 'nullable',
            'contact' => 'nullable',
            'emergency_contact' => 'nullable',
            'job_title' => 'nullable',
            'nationality' => 'nullable',
            'pin' => 'nullable',
            'reference_to_emp' => 'nullable',
            'relation_with_kin' => 'nullable',
            'kin_address' => 'nullable',
            'kin_number' => 'nullable',
            'kin_work_tel' => 'nullable',
            'kin_mobile' => 'nullable',
            'share_code' => 'nullable',
            'share_code_expiry' => 'nullable',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'nullable',
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
            'sia_licence_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'passport_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'proof_of_address_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'ni_letter_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'first_aid_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'act_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
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
            'terms' => 'nullable|array',
            'terms.*.from' => 'nullable|date',
            'terms.*.to' => 'nullable|date',
            'terms.*.term_name' => 'nullable',
            // 'username' => 'required|email|unique:users,username',

            // 'username'        => 'required|email',          // Assuming username is email for user
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

        $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file'];
        foreach ($documents as $document) {
            if ($request->hasFile($document)) {
                $file = $request->file($document);
                $fileName = time() . '_' . $document . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/' . $document), $fileName);
                $data[$document] = $fileName;
            }
        }

        // Create user with hashed password
        $user = User::create([
            'name' => $data['fore_name'],
            'first_name' => $data['fore_name'],
            'last_name' => '',
            'username' => $data['email'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Check if 'client' role exists, if not create it
        $role = Role::firstOrCreate(['name' => 'security_staff']);

        // Assign role to user
        $user->assignRole($role);
        $data['user_id'] = $user->id;

        // Prepare employee data by excluding user-related fields
        $employeeData = $data;
        // unset($employeeData['username'], $employeeData['password']);
        unset($employeeData['password']);

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

        if ($request->has('terms')) {
            foreach ($request->terms as $term) {
                EmployeeTerm::create([
                    'employee_id' => $employee->id,
                    'from_date' => $term['from'],
                    'to_date' => $term['to'],
                    'term_name' => $term['term_name'],
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
            'email' => 'required|email:dns|max:255',
            'gender' => 'nullable|string',
            'ni_number' => 'nullable|string',
            'sia_licence' => 'nullable|string',
            'sia_expiry' => 'nullable',
            'licence_type' => 'nullable|string',
            'entry_date' => 'nullable',
            'dob' => 'nullable',
            'service_type' => 'nullable',
            'visa_type' => 'nullable',
            'visa_expiry' => 'nullable',
            'place_work' => 'nullable',
            'hour_per_week' => 'nullable',
            'passport_no' => 'nullable',
            'passport_expiry' => 'nullable',
            'address_group' => 'nullable',
            'address_group_additional' => 'nullable',
            'contact' => 'nullable',
            'emergency_contact' => 'nullable',
            'job_title' => 'nullable',
            'nationality' => 'nullable',
            'pin' => 'nullable',
            'reference_to_emp' => 'nullable',
            'relation_with_kin' => 'nullable',
            'kin_address' => 'nullable',
            'kin_number' => 'nullable',
            'kin_work_tel' => 'nullable',
            'kin_mobile' => 'nullable',
            'share_code' => 'nullable',
            'share_code_expiry' => 'nullable',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'nullable',
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
            'sia_licence_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'passport_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'proof_of_address_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'ni_letter_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'first_aid_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
            'act_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)

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
            'terms' => 'nullable|array',
            'terms.*.from' => 'nullable|date',
            'terms.*.to' => 'nullable|date',
            'terms.*.term_name' => 'nullable',
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

        $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file'];
        foreach ($documents as $document) {
            if ($request->hasFile($document)) {
                $file = $request->file($document);
                $fileName = time() . '_' . $document . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/' . $document), $fileName);
                $data[$document] = $fileName;
            }
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

        // Update employee terms
        if ($request->has('terms')) {
            // Delete existing terms for clean sync
            $employee->terms()->delete();

            // Re-create terms from request
            foreach ($request->terms as $term) {
                if (!empty($term['from']) && !empty($term['to'])) {
                    $employee->terms()->create([
                        'from_date' => $term['from'],
                        'to_date' => $term['to'],
                        'term_name' => $term['term_name'] ?? 'Term',
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Employee updated successfully']);
    }

    public function edit($id)
    {
        $employee = Employee::with('holidays', 'terms')->find($id);

        return response()->json([
            'employee' => $employee,
            'holidays' => $employee->holidays,
            'terms' => $employee->terms,
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
    public function getLogs($id)
    {
        $employee = Employee::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $employee->logs->map(function ($log) {
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
        $employee = Employee::findOrFail($id);

        return response()->json([
            'fore_name'       => $employee->fore_name,
            'sur_name'        => $employee->sur_name,
            'email'           => $employee->email,
            'gender'          => $employee->gender,
            'ni_number'       => $employee->ni_number,
            'sia_licence'     => $employee->sia_licence,
            'sia_expiry'      => $employee->sia_expiry,
            'licence_type'    => $employee->licence_type,
            'entry_date'      => $employee->entry_date,
            'dob'             => $employee->dob,
            'service_type'    => $employee->service_type,
            'visa_type'       => $employee->visa_type,
            'visa_expiry'     => $employee->visa_expiry,
            'place_work'      => $employee->place_work,
            'contact'         => $employee->contact,
            'emergency_contact' => $employee->emergency_contact,
            'job_title'       => $employee->job_title,
            'nationality'     => $employee->nationality,
            'passport_no'     => $employee->passport_no,
            'passport_expiry' => $employee->passport_expiry,
            'address_group'   => $employee->address_group,
            'guard_rate'      => $employee->guard_rate,
            'bank_name'       => $employee->bank_name,
            'account_name'    => $employee->account_name,
            'account_number'  => $employee->account_number,
            'other_info'      => $employee->other_info,
            'sia_licence_file' => $employee->sia_licence_file,
            'passport_file' => $employee->passport_file,
            'proof_of_address_file' => $employee->proof_of_address_file,
            'ni_letter_file' => $employee->ni_letter_file,
            'first_aid_certificate_file' => $employee->first_aid_certificate_file,
            'act_certificate_file' => $employee->act_certificate_file,
        ]);
    }
    public function print($id)
    {
        $employee = Employee::findOrFail($id);

        return view('employees.print', [
            'fore_name'       => $employee->fore_name,
            'sur_name'        => $employee->sur_name,
            'email'           => $employee->email,
            'gender'          => $employee->gender,
            'ni_number'       => $employee->ni_number,
            'sia_licence'     => $employee->sia_licence,
            'sia_expiry'      => $employee->sia_expiry,
            'licence_type'    => $employee->licence_type,
            'entry_date'      => $employee->entry_date,
            'dob'             => $employee->dob,
            'service_type'    => $employee->service_type,
            'visa_type'       => $employee->visa_type,
            'visa_expiry'     => $employee->visa_expiry,
            'place_work'      => $employee->place_work,
            'contact'         => $employee->contact,
            'emergency_contact' => $employee->emergency_contact,
            'job_title'       => $employee->job_title,
            'nationality'     => $employee->nationality,
            'passport_no'     => $employee->passport_no,
            'passport_expiry' => $employee->passport_expiry,
            'address_group'   => $employee->address_group,
            'guard_rate'      => $employee->guard_rate,
            'bank_name'       => $employee->bank_name,
            'account_name'    => $employee->account_name,
            'account_number'  => $employee->account_number,
            'other_info'      => $employee->other_info,
            'sia_licence_file' => $employee->sia_licence_file,
            'passport_file' => $employee->passport_file,
            'proof_of_address_file' => $employee->proof_of_address_file,
            'ni_letter_file' => $employee->ni_letter_file,
            'first_aid_certificate_file' => $employee->first_aid_certificate_file,
            'act_certificate_file' => $employee->act_certificate_file,
        ]);
    }
}
