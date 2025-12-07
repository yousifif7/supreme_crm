<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Logger;
use App\Models\Holiday;
use App\Models\License;
use App\Models\Employee;
use App\Models\VisaType;
use App\Models\Department;
use App\Models\EmployeeTerm;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Permission\Models\Role;
use App\Services\SiaLicenceChecker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\DataTables\EmployeesDataTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\FileCompressor;

class EmployeeController extends Controller
{

        protected SiaLicenceChecker $siaChecker;

    public function __construct(SiaLicenceChecker $siaChecker)
    {
        $this->siaChecker = $siaChecker;
    }
    
    public function index(EmployeesDataTable $dataTable)
    {
        $employeeUserIds = Employee::pluck('user_id')->filter()->toArray();

        User::role('security_staff')
            ->whereNotIn('id', $employeeUserIds)
            ->delete();

        $departments = Department::all();
        $visa_types = VisaType::all();
        $employee_types = EmployeeType::all();
        $licenses = License::all();
        $subcontractors = User::role('subcontractor')->get();

        return $dataTable->render('employees.index', compact('departments', 'visa_types', 'employee_types', 'licenses', 'subcontractors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'fore_name' => 'required|string',
            'sur_name' => 'required|string',
            'email' => 'required|email:dns|max:255|unique:users,email',
            'gender' => 'nullable|string',
            'ni_number' => 'nullable|string|unique:employees,ni_number',
            'sia_licence' => ['nullable', 'string','unique:employees,sia_licence', new \App\Rules\ValidSiaLicence()],
            'driving_licence_number' => 'nullable|string|unique:employees,driving_licence_number',
            'sia_expiry' => 'nullable',
            'licence_type' => 'nullable|string',
            'entry_date' => 'nullable',
            'dob' => 'nullable',
            'service_type' => 'nullable',
            'visa_type' => 'nullable',
            'visa_expiry' => 'nullable',
            'driving_licence_expiry' => 'nullable',
            'place_work' => 'nullable',
            'hour_per_ek' => 'nullable',
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
            'sia_licence_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'passport_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'proof_of_address_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'ni_letter_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'first_aid_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'act_certificate_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
            'driving_licence_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
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
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/',
            ],
            'additional_file.*' => 'file|mimes:jpeg,jpg,png,pdf|max:20480',
            'employment_start_date' => 'nullable|date',
            'employment_end_date' => 'nullable|date|after:employment_start_date'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        // Check and verify SIA Licence via SiaLicenceChecker if provided
                if ($request->filled('license_number')) {
            $siaResult = $this->siaChecker->checkByLicenceNumber($request->input('license_number'), false);
            if (! $siaResult['success']) {
                return back()->withInput()->withErrors(['license_number' => $siaResult['error']]);
            }
            if (! $siaResult['valid']) {
                return back()->withInput()->withErrors(['license_number' => 'SIA licence not active: ' . ($siaResult['error'] ?? 'unknown')]);
            }
            
        }

        // ✅ Handle the checkbox manually
        // Handle files...
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_pics'), $imageName);
            $data['profile_picture'] = $imageName;

            try {
                (new FileCompressor())->compress(public_path('uploads/profile_pics/' . $imageName));
            } catch (\Throwable $e) {
                Log::error('EmployeeController: profile_picture compression failed', ['file' => $imageName, 'error' => $e->getMessage()]);
            }
        }

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $fileName = time() . '_signature.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/signatures'), $fileName);
            $data['signature'] = $fileName;

            try {
                (new FileCompressor())->compress(public_path('uploads/signatures/' . $fileName));
            } catch (\Throwable $e) {
                Log::error('EmployeeController: signature compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
            }
        }

        $documents = ['sia_licence_file', 'passport_file', 'driving_licence_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file'];
        foreach ($documents as $document) {
            if ($request->hasFile($document)) {
                $file = $request->file($document);
                $fileName = time() . '_' . $document . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('documents'), $fileName);
                $data[$document] = $fileName;

                try {
                    (new FileCompressor())->compress(public_path('documents/'. $fileName));
                } catch (\Throwable $e) {
                    Log::error('EmployeeController: document compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
                }
            }
        }

        // Process multiple additional files upload
        if ($request->hasFile('additional_file')) {
            $savedPaths = [];

            foreach ($request->file('additional_file') as $file) {
                if ($file->isValid()) {
                    $originalName = preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $fileName = time() . '_' . $originalName;

                    $destinationPath = public_path('uploads/additional_docs');
                    $moved = $file->move($destinationPath, $fileName);

                    if ($moved) {
                        $savedPaths[] = 'uploads/additional_docs/' . $fileName;

                        try {
                            (new FileCompressor())->compress(public_path('uploads/additional_docs/' . $fileName));
                        } catch (\Throwable $e) {
                            Log::error('EmployeeController: additional_file compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
                        }

                    } else {
                        return response()->json([
                            'error' => 'Failed to move file: ' . $file->getClientOriginalName()
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'error' => 'Uploaded file is not valid: ' . $file->getClientOriginalName()
                    ], 400);
                }
            }

            $data['additional_files'] = $savedPaths;
        }

        // Create user with hashed password
        $user = User::create([
            'name' => $data['fore_name'],
            'first_name' => $data['fore_name'],
            'last_name' => $data['sur_name'],
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
        unset($employeeData['password']);
        unset($employeeData['reference_number']);

        // Save employee
        $employee = Employee::create($employeeData);
        Logger::log(Auth::user(), 'Create', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Created.');

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
                    'term_name' => $term['entitlement'],
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
            'email' => 'email',
            'gender' => 'nullable|string',
            'ni_number' => 'nullable|string',
            'sia_licence' => ['nullable', 'string', 'unique:employees,sia_licence', new \App\Rules\ValidSiaLicence()],
            'sia_expiry' => 'nullable',
            'licence_type' => 'nullable|string',
            'driving_licence_number' => 'nullable|string',
            'driving_licence_expiry' => 'nullable|string',
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
            'driving_licence_file' => 'file|mimes:jpg,jpeg,png,pdf|max:20480', // max in kilobytes (2048 KB = 20MB)
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
            'terms.*.entitlement' => 'nullable',
            'additional_file.*' => 'file|mimes:jpeg,jpg,png,pdf|max:20480',
            'password' => [
                'nullable',
                'string',
                'min:8', // minimum 8 characters
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                'regex:/[0-9]/', // at least one number
                'regex:/[@$!%*?&#]/', // at least one special char
            ],  // Add password validation
            'employment_start_date' => 'nullable|date',
            'employment_end_date' => 'nullable|date|after:employment_start_date'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }


        $data = $validator->validated();
        
        // Check and verify SIA Licence via SiaLicenceChecker if provided
        if (!empty($data['sia_licence'])) {
            try {
                $siaChecker = new \App\Services\SiaLicenceChecker();
                $siaResult = $siaChecker->checkByLicenceNumber($data['sia_licence'], false);

                // Just check if the request was successful
                if (!$siaResult['success']) {
                    return response()->json([
                        'error' => 'Could not verify SIA licence: ' . $siaResult['error']
                    ], 400);
                }

                // If success = true and valid = true, licence exists in SIA database
                if ($siaResult['valid']) {
                    // Licence is valid! Continue with your logic
                    Log::info('SIA Licence Verified', [
                        'licence' => $data['sia_licence'],
                        'found_details' => [
                            'name' => $siaResult['holder_name'] ?? 'Could not parse',
                            'status' => $siaResult['licence_status'] ?? 'Could not parse',
                        ]
                    ]);
                } else {
                    return response()->json([
                        'error' => 'SIA licence not found in register'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('SIA Check Failed', ['error' => $e->getMessage()]);
                return response()->json([
                    'error' => 'Error checking SIA licence. Please try again.'
                ], 500);
            }
        }

        if ($request->email || $request->password || $request->fore_name || $request->sur_name) {
            $employee = Employee::find($id);
            $user = User::role('security_staff')->where('id', $employee->user_id)->first();

            if ($user) {
                if($request->fore_name){
                    $user->first_name = $request->fore_name;
                }

                if($request->sur_name){
                    $user->last_name = $request->sur_name;
                }

                if ($request->email) {
                    $user->email = $request->email;
                    $employee->email = $request->email;
                }
                if (!$request->email) {
                    $user->email = $user->email;
                    $employee->email = $employee->email;
                }

                if ($request->password) {
                    // Use Hash facade to hash the password
                    $user->password = Hash::make($request->password);
                    send_push_notification(
                        $user->id,
                        'Creds changed',
                        'An admin has changed your account credintials! Ask an admin for it.',
                        ['user' => $user],
                    );
                }
                if (!$request->password) {
                    // Use Hash facade to hash the password
                    $user->password = $user->password;
                }

                $user->save();
            }
        }
        // Handle profile picture update
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_pics'), $imageName);
            $data['profile_picture'] = $imageName;
            try {
                (new FileCompressor())->compress(public_path('uploads/profile_pics/' . $imageName));
            } catch (\Throwable $e) {
                Log::error('EmployeeController (update): profile_picture compression failed', ['file' => $imageName, 'error' => $e->getMessage()]);
            }
        }

        // Handle signature update
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $fileName = time() . '_signature.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/signatures'), $fileName);
            $data['signature'] = $fileName;
            try {
                (new FileCompressor())->compress(public_path('uploads/signatures/' . $fileName));
            } catch (\Throwable $e) {
                Log::error('EmployeeController (update): signature compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
            }
        }

        $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file'];
        foreach ($documents as $document) {
            if ($request->hasFile($document)) {
                $file = $request->file($document);
                $fileName = time() . '_' . $document . '.' . $file->getClientOriginalExtension();
                $sfile=$file->move(public_path('documents'), $fileName);
                $data[$document] = $fileName;
                try {
                    (new FileCompressor())->compress(public_path('documents/' . $fileName));
                } catch (\Throwable $e) {
                    Log::error('EmployeeController (update): document compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
                }
            }
        }

        // Process multiple additional files upload
        if ($request->hasFile('additional_file')) {
            $savedPaths = [];

            foreach ($request->file('additional_file') as $file) {
                if ($file->isValid()) {
                    // Keep the original file name but prepend timestamp to avoid collisions
                    $originalName = preg_replace('/\s+/', '_', $file->getClientOriginalName()); // replace spaces with underscores
                    $fileName = time() . '_' . $originalName;

                    // Move file to public/uploads/additional_docs
                    $destinationPath = public_path('uploads/additional_docs');
                    $moved = $file->move($destinationPath, $fileName);

                    if ($moved) {
                        // Save relative path to array
                        $savedPaths[] = 'uploads/additional_docs/' . $fileName;
                            try {
                                (new FileCompressor())->compress(public_path('uploads/additional_docs/' . $fileName));
                            } catch (\Throwable $e) {
                                Log::error('EmployeeController (update): additional_file compression failed', ['file' => $fileName, 'error' => $e->getMessage()]);
                            }
                    } else {
                        return response()->json([
                            'error' => 'Failed to move file: ' . $file->getClientOriginalName()
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'error' => 'Uploaded file is not valid: ' . $file->getClientOriginalName()
                    ], 400);
                }
            }

            $data['additional_files'] = $savedPaths;
        }


        // Now update employee record
        $updated = $employee->update($data);
        Logger::log(Auth::user(), 'Update', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Updated.');

        if (!$updated) {
            return response()->json(['error' => 'Failed to update employee record.'], 500);
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
                        'term_name' => $term['entitlement'] ?? 'Term',
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
        $empUser = User::role('security_staff')->find($employee->user_id);

        Logger::log(Auth::user(), 'Delete', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Deleted.');

        $empUser->forceDelete();
        $employee->forceDelete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        // Get the employees
        $employees = Employee::whereIn('id', $request->ids)->get();

        // Collect related user IDs
        $userIds = $employees->pluck('user_id')->toArray();

        // Delete related users (only security_staff role)
        User::role('security_staff')->whereIn('id', $userIds)->delete();

        foreach ($employees as $employee) {
            Logger::log(Auth::user(), 'Delete', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Deleted.');
        }
        // Delete employees
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
        // Find employee by user_id instead of employee ID
        $employee = Employee::find($id);

        // Get guard availability
        $availability = \App\Models\Availability::where('user_id', $employee->user_id)
            ->orderBy('day_of_week')
            ->get(['day_of_week', 'start_time as start', 'end_time as end']);

        return response()->json([
            'fore_name'       => $employee?->fore_name,
            'sur_name'        => $employee?->sur_name,
            'email'           => $employee?->email,
            'gender'          => $employee?->gender,
            'ni_number'       => $employee?->ni_number,
            'sia_licence'     => $employee?->sia_licence,
            'sia_expiry'      => $employee?->sia_expiry,
            'licence_type'    => $employee?->licence_type,
            'entry_date'      => $employee?->entry_date,
            'dob'             => $employee?->dob,
            'service_type'    => $employee?->service_type,
            'visa_type'       => $employee?->visa_type,
            'visa_expiry'     => $employee?->visa_expiry,
            'place_work'      => $employee?->place_work,
            'contact'         => $employee?->contact,
            'emergency_contact' => $employee?->emergency_contact,
            'job_title'       => $employee?->job_title,
            'nationality'     => $employee?->nationality,
            'passport_no'     => $employee?->passport_no,
            'passport_expiry' => $employee?->passport_expiry,
            'address_group'   => $employee?->address_group,
            'guard_rate'      => $employee?->guard_rate,
            'bank_name'       => $employee?->bank_name,
            'account_name'    => $employee?->account_name,
            'account_number'  => $employee?->account_number,
            'other_info'      => $employee?->other_info,
            'sia_licence_file' => $employee?->sia_licence_file,
            'passport_file' => $employee?->passport_file,
            'proof_of_address_file' => $employee?->proof_of_address_file,
            'ni_letter_file' => $employee?->ni_letter_file,
            'first_aid_certificate_file' => $employee?->first_aid_certificate_file,
            'act_certificate_file' => $employee?->act_certificate_file,
            'additional_files' => $employee?->additional_files,
            'employment_start_date' => $employee?->employment_start_date,
            'employment_end_date' => $employee?->employment_end_date,
            'driving_licence_expiry' => $employee?->driving_licence_expiry,
            'driving_licence_file' => $employee?->driving_licence_file,
            'driving_licence_number' => $employee?->driving_licence_number,

            // Add availability here
            'availability' => $availability
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
            'additional_files' => $employee->additional_files,
            'employment_start_date' => $employee->employment_start_date,
            'employment_end_date' => $employee->employment_end_date,
        ]);
    }

    public function employmentReport(Request $request)
    {
        $query = Employee::query();

        // Apply filter by name
        if ($request->filled('name')) {
            $query->whereRaw("CONCAT(fore_name, ' ', sur_name) LIKE ?", ["%{$request->name}%"]);
        }

        $employees = $query->get();

        // Pass filter status
        $hasFilters = $request->filled('name');

        return view('employees.employment_report', compact('employees', 'hasFilters'))
            ->with('name', $request->name);
    }

    public function exportEmploymentPdf(Employee $employee)
    {
        $start = $employee->employment_start_date ? \Carbon\Carbon::parse($employee->employment_start_date) : null;
        $end = $employee->employment_end_date ? \Carbon\Carbon::parse($employee->employment_end_date) : now();
        $duration = $start ? $start->diff($end)->format('%y years, %m months, %d days') : 'N/A';

        $pdf = Pdf::loadView('employees.employment_pdf', [
            'employee' => $employee,
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
        ]);

        return $pdf->download("employment_report_{$employee->id}.pdf");
    }
    


}
