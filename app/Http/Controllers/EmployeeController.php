<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Logger;
use App\Models\Holiday;
use App\Models\License;
use App\Models\Employee;
use App\Models\Document;
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
use App\Jobs\RunSiaCheck;
use App\DataTables\EmployeesDataTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Log as ActivityLog;
use App\Services\FileCompressor;
use App\Models\Subcontractor;
use App\Models\PendingDelete;
use Illuminate\Support\Arr;

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
            'email' => 'nullable|email:dns|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'gender' => 'nullable|string',
            'ni_number' => 'nullable|string|unique:employees,ni_number',
            // 'sia_licence' => ['nullable', 'string','unique:employees,sia_licence', new \App\Rules\ValidSiaLicence()],
            'sia_licence' => ['nullable', 'string','unique:employees,sia_licence'],
            'driving_licence_number' => 'nullable|string|unique:employees,driving_licence_number',
            'sia_expiry' => 'nullable|date',
            'licence_type' => 'nullable|string',
            'entry_date' => 'nullable|date',
            'dob' => 'nullable|date',
            'service_type' => 'nullable',
            'visa_type' => 'nullable',
            'visa_expiry' => 'nullable|date',
            'driving_licence_expiry' => 'nullable|date',
            'place_work' => 'nullable',
            'hour_per_ek' => 'nullable',
            'passport_no' => 'nullable|string|max:50',
            'passport_expiry' => 'nullable|date',
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
            'share_code_expiry' => 'nullable|date',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable|date',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'nullable|array',
            'subcontractor.*' => 'nullable|integer|exists:users,id',
            'tags' => 'nullable',
            'additional_sia_number' => 'nullable',
            'license_expiry' => 'nullable|date',
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
            'employment_end_date' => 'nullable|date|after:employment_start_date',
            'address' => 'nullable|string',
        ]);
        
        
$validator->after(function ($validator) use ($request) {

    // If subcontractor is empty -> email must be provided
    if (empty($request->subcontractor) && empty($request->email)) {
        $validator->errors()->add('email', 'The email field is required when subcontractor is empty.');
    }

});

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }
        // Conditional email validation


        $data = $validator->validated();
        
        // Auto-generate email if subcontractor is selected but no email provided
        if (!empty($data['subcontractor']) && empty($data['email'])) {
            // subcontractor may be an array (multiple) — use first as primary for email generation
            $primarySub = is_array($data['subcontractor']) ? ($data['subcontractor'][0] ?? null) : $data['subcontractor'];

            $sub = Subcontractor::where('user_id', $primarySub)->first();

            if ($sub && $sub->email) {
                // Split main email
                $emailParts = explode('@', $sub->email);
                $name = $emailParts[0];  // before @
                $domain = $emailParts[1] ?? 'example.com'; // fallback domain

                // Start generating email
                $counter = 1;
                $generatedEmail = "{$name}+{$counter}@{$domain}";

                // Ensure uniqueness
                while (User::where('email', $generatedEmail)->exists()) {
                    $counter++;
                    $generatedEmail = "{$name}+{$counter}@{$domain}";
                }

                $data['email'] = $generatedEmail;
            }
        }


        // Check and verify SIA Licence via SiaLicenceChecker if provided
        //         if ($request->filled('license_number')) {
        //     $siaResult = $this->siaChecker->checkByLicenceNumber($request->input('license_number'), false);
        //     if (! $siaResult['success']) {
        //         return back()->withInput()->withErrors(['license_number' => $siaResult['error']]);
        //     }
        //     if (! $siaResult['valid']) {
        //         return back()->withInput()->withErrors(['license_number' => 'SIA licence not active: ' . ($siaResult['error'] ?? 'unknown')]);
        //     }
            
        // }

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

        // Wrap DB writes in a transaction so if anything fails we don't leave a
        // partially-created user without an employee record.
        try {
            \DB::beginTransaction();

            // Prevent MySQL AUTO_INCREMENT recycling from colliding with existing
            // employee records. If MySQL restarted and reset AUTO_INCREMENT below
            // the highest user_id still referenced by an employee, the new user
            // would get a recycled ID that already has an employee attached to it.
            // Bumping AUTO_INCREMENT here ensures the new user always gets a fresh ID.
            $maxUsedId = Employee::withTrashed()->max('user_id') ?? 0;
            $autoInc = \DB::selectOne(
                "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
            )->AUTO_INCREMENT ?? 1;
            if ((int)$autoInc <= (int)$maxUsedId) {
                \DB::statement('ALTER TABLE users AUTO_INCREMENT = ' . ((int)$maxUsedId + 1));
            }

            // Create user with hashed password
            $user = User::create([
                'name' => $data['fore_name'],
                'first_name' => $data['fore_name'],
                'last_name' => $data['sur_name'],
                'username' => $data['email'],
                'email' => $data['email'],
                'plaintext_password' => $data['password'],
                'password' => Hash::make($data['password']),
            ]);

            // Check if 'security_staff' role exists, if not create it
            $role = Role::firstOrCreate(['name' => 'security_staff']);

            // Assign role to user (DB pivot will be inside same transaction)
            $user->assignRole($role);
            $data['user_id'] = $user->id;

            // Prepare employee data by excluding user-related fields
            $employeeData = $data;
            unset($employeeData['password']);
            unset($employeeData['reference_number']);

            // Save employee
            $employee = Employee::create($employeeData);
            Logger::log(Auth::user(), 'Create', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Created.');

            // Create Document records for files uploaded via admin UI so other parts
            // of the app (which rely on the documents table) see these uploads.
            try {
            $docExpiryMap = [
                'sia_licence_file' => 'sia_expiry',
                'passport_file' => 'passport_expiry',
                'act_certificate_file' => 'license_expiry',
                'driving_licence_file' => 'driving_licence_expiry',
            ];
                foreach ($documents as $document) {
                if (empty($data[$document])) continue;

                $fileVal = $data[$document];
                $basename = basename($fileVal);
                $candidates = [];
                if (strpos($fileVal, '/') === false) {
                    $candidates[] = 'documents/' . $fileVal;
                    $candidates[] = 'uploads/' . $document . '/' . $fileVal;
                } else {
                    $candidates[] = $fileVal;
                }

                $expiry = $docExpiryMap[$document] ?? null;

                // Try to find an existing document by exact path or basename
                $existing = Document::where('user_id', $user->id)
                    ->where('document_type', $document)
                    ->where(function ($q) use ($candidates, $basename) {
                        foreach ($candidates as $p) {
                            $q->orWhere('file_path', $p);
                        }
                        $q->orWhere('file_path', 'like', "%{$basename}%");
                    })->first();

                $normalizedPath = (strpos($fileVal, '/') === false) ? ('documents/' . $fileVal) : $fileVal;

                if ($existing) {
                    $existing->file_path = $normalizedPath;
                    $existing->expiry_date = $expiry ? ($data[$expiry] ?? null) : null;
                    $existing->status = 'approved';
                    $existing->save();
                } else {
                    Document::create([
                        'user_id' => $user->id,
                        'document_type' => $document,
                        'file_path' => $normalizedPath,
                        'expiry_date' => $expiry ? ($data[$expiry] ?? null) : null,
                        'status' => 'approved',
                    ]);
                }
            }

            // Additional files
            if (!empty($data['additional_files']) && is_array($data['additional_files'])) {
                foreach ($data['additional_files'] as $path) {
                    $basename = basename($path);
                    $existing = Document::where('user_id', $user->id)
                        ->where('document_type', 'other')
                        ->where('file_path', 'like', "%{$basename}%")
                        ->first();
                    if ($existing) {
                        $existing->file_path = $path;
                        $existing->status = 'approved';
                        $existing->save();
                    } else {
                        Document::create([
                            'user_id' => $user->id,
                            'document_type' => 'other',
                            'file_path' => $path,
                            'status' => 'approved',
                        ]);
                    }
                }
            }
            } catch (\Throwable $e) {
                Log::error('Failed to create Document records for employee upload: ' . $e->getMessage());
                throw $e; // bubble up to outer transaction handler
            }

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
            \DB::commit();

            return response()->json(['message' => 'Employee created successfully']);

        } catch (\Throwable $e) {
            // rollback DB changes and remove any partially created user if present
            try {
                \DB::rollBack();
            } catch (\Throwable $__) {
                // ignore
            }

            Log::error('EmployeeController@store failed, rolling back: ' . $e->getMessage());

            if (!empty($user) && isset($user->id)) {
                try {
                    // Force-delete (hard delete) the user so the ID and email are fully freed.
                    // A soft-delete would leave the record in place and cause duplicate-email
                    // errors on the next attempt because the DB unique index ignores deleted_at.
                    User::where('id', $user->id)->forceDelete();
                } catch (\Throwable $__) {
                    Log::warning('Failed to delete partially created user: ' . ($user->id ?? 'unknown'));
                }
            }

            return response()->json(['error' => 'Failed to create employee: ' . $e->getMessage()], 500);
        }

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
            'sia_licence' => ['nullable', 'string'],
            'sia_expiry' => 'nullable|date',
            'licence_type' => 'nullable|string',
            'driving_licence_number' => 'nullable|string',
            'driving_licence_expiry' => 'nullable|date',
            'entry_date' => 'nullable|date',
            'dob' => 'nullable|date',
            'service_type' => 'nullable',
            'visa_type' => 'nullable',
            'visa_expiry' => 'nullable|date',
            'place_work' => 'nullable',
            'hour_per_week' => 'nullable',
            'passport_no' => 'nullable|string|max:50',
            'passport_expiry' => 'nullable|date',
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
            'share_code_expiry' => 'nullable|date',
            'settlement' => 'nullable',
            'biometric_residence_permit' => 'nullable',
            'biometric_residence_permit_expiry' => 'nullable|date',
            'brp_status' => 'nullable',
            'gourd_rate' => 'nullable|numeric',
            'department_id' => 'nullable',
            'subcontractor' => 'nullable|array',
            'subcontractor.*' => 'nullable|integer|exists:users,id',
            'tags' => 'nullable',
            'additional_sia_number' => 'nullable',
            'license_expiry' => 'nullable|date',
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
            'employment_end_date' => 'nullable|date|after:employment_start_date',
            'address' => 'nullable|string',
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
       /** if (!empty($data['sia_licence'])) {
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
        }*/

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
                    if ($request->filled('password') && $request->password !== ($user->plaintext_password ?? '')) {
                        send_push_notification(
                            $user->id,
                            'Creds changed',
                            'An admin has changed your account credentials! You have been logged out from other devices.',
                            ['type' => 'profile']
                        );
                        $user->plaintext_password = $request->password;
                        $user->password = Hash::make($request->password);
                        try {
                            if (method_exists($user, 'tokens')) {
                                $user->tokens()->delete();
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to delete user tokens for user '.$user->id.': '.$e->getMessage());
                        }
                        try {
                            if (\Schema::hasTable('sessions')) {
                                \DB::table('sessions')->where('user_id', $user->id)->delete();
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to clear sessions for user '.$user->id.': '.$e->getMessage());
                        }
                    }
                    $user->save();
                }
                if (!$request->password) {
                    // Use Hash facade to hash the password
                    $user->password = $user->password;
                }

                $user->save();
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

        $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file', 'driving_licence_file'];
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

        // Ensure uploaded files are represented in the documents table as well
        try {
            $docExpiryMap = [
                'sia_licence_file' => 'sia_expiry',
                'passport_file' => 'passport_expiry',
                'act_certificate_file' => 'license_expiry',
                'driving_licence_file' => 'driving_licence_expiry',
            ];

            foreach ($documents as $document) {
                if (!empty($data[$document])) {
                    $expiry = $docExpiryMap[$document] ?? null;
                    Document::updateOrCreate(
                        ['user_id' => $employee->user_id, 'document_type' => $document],
                        [
                            'file_path' => (strpos($data[$document], '/') === false) ? 'documents/' . $data[$document] : $data[$document],
                            'expiry_date' => $expiry ? ($data[$expiry] ?? null) : null,
                            'status' => 'approved',
                        ]
                    );
                }
            }

            if (!empty($data['additional_files']) && is_array($data['additional_files'])) {
                foreach ($data['additional_files'] as $path) {
                    Document::create([
                        'user_id' => $employee->user_id,
                        'document_type' => 'other',
                        'file_path' => $path,
                        'status' => 'approved',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to sync uploaded files to documents table (update): ' . $e->getMessage());
        }

        return response()->json(['message' => 'Employee updated successfully']);
    }

    public function edit($id)
    {
        $employee = Employee::with(['holidays', 'terms', 'user'])->find($id);
        
        // Get plaintext password from related user if exists
        $plaintext_password = null;
        if ($employee->user) {
            $plaintext_password = $employee->user->plaintext_password;
        }

        return response()->json([
            'employee' => array_merge($employee->toArray(), [
                'plaintext_password' => $plaintext_password
            ]),
            'holidays' => $employee->holidays,
            'terms' => $employee->terms,
        ]);
    }
    public function delete($id)
    {
        $employee = Employee::findOrFail($id);
        $empUser = User::role('security_staff')->find($employee->user_id);

        // If current user is superadmin, perform immediate delete
        if (Auth::user() && Auth::user()->hasRole('superadmin')) {
            Logger::log(Auth::user(), 'Delete', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Deleted.');
            if ($empUser) $empUser->delete();
            $employee->forceDelete();
            return response()->json(['success' => true]);
        }

        // Otherwise create a pending delete request
        PendingDelete::create([
            'requester_id' => Auth::id(),
            'target_type' => Employee::class,
            'target_id' => $employee->id,
            'target_user_id' => $employee->user_id,
            'reason' => request()->input('reason') ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Deletion request submitted and is pending approval.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        $employees = Employee::whereIn('id', $request->ids)->get();

        // If superadmin: delete directly
        if (Auth::user() && Auth::user()->hasRole('superadmin')) {
            $userIds = $employees->pluck('user_id')->toArray();
            User::role('security_staff')->whereIn('id', $userIds)->delete();
            foreach ($employees as $employee) {
                Logger::log(Auth::user(), 'Delete', 'Staff ' . $employee->fore_name . ' ' . $employee->sur_name . ' Deleted.');
            }
            Employee::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => 'Selected employees deleted.']);
        }

        // Otherwise create pending requests for each employee
        foreach ($employees as $employee) {
            PendingDelete::create([
                'requester_id' => Auth::id(),
                'target_type' => Employee::class,
                'target_id' => $employee->id,
                'target_user_id' => $employee->user_id,
                'status' => 'pending',
            ]);
        }

        return response()->json(['message' => 'Deletion requests submitted and are pending approval.']);
    }

    /** List pending delete requests (admins) */
    public function pendingDeletes()
    {

        $list = PendingDelete::with(['requester'])->orderBy('created_at', 'desc')->get();

        // Enrich with a human-readable target label when possible
        $payload = $list->map(function ($item) {
            $targetLabel = null;
            if ($item->target_type === Employee::class) {
                $emp = Employee::find($item->target_id);
                if ($emp) {
                    $targetLabel = trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) ?: "Employee #{$item->target_id}";
                }
            }

            if (! $targetLabel) {
                // fallback to generic label
                $targetLabel = class_basename($item->target_type) . " #{$item->target_id}";
            }

            return array_merge($item->toArray(), ['target_label' => $targetLabel]);
        });

        return response()->json(['data' => $payload]);
    }

    /** Approve a pending delete (superadmin only) */
    public function approvePendingDelete($id)
    {
        $pd = PendingDelete::findOrFail($id);
        if (! (Auth::user() && Auth::user()->hasRole('superadmin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // perform deletion depending on target_type
        if ($pd->target_type === Employee::class) {
            $employee = Employee::find($pd->target_id);
            if ($employee) {
                $empUser = User::role('security_staff')->find($employee->user_id);
                if ($empUser) $empUser->delete();
                $employee->forceDelete();
                Logger::log(Auth::user(), 'Delete', 'Approved delete: Staff ' . ($employee->fore_name ?? '') . ' ' . ($employee->sur_name ?? ''));
            }
        }

        $pd->status = 'approved';
        $pd->approved_by = Auth::id();
        $pd->save();

        return response()->json(['message' => 'Pending delete approved and processed.']);
    }

    /** Reject a pending delete (superadmin only) */
    public function rejectPendingDelete($id)
    {
        $pd = PendingDelete::findOrFail($id);
        if (! (Auth::user() && Auth::user()->hasRole('superadmin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $pd->status = 'rejected';
        $pd->approved_by = Auth::id();
        $pd->save();
        return response()->json(['message' => 'Pending delete rejected.']);
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

    public function getLogsByEmail($email)
    {
        $email = urldecode($email);

        $employee = Employee::where('email', $email)->first();

        $query = ActivityLog::where('user_name', $email);

        if ($employee) {
            $query = $query->orWhere(function ($q) use ($employee) {
                $q->where('loggable_type', Employee::class)->where('loggable_id', $employee->id);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'user_name' => $log->user_name,
                    'action' => $log->action,
                    'description' => $log->description,
                    'time' => $log->created_at->diffForHumans(),
                ];
            }),
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
            'id' => $employee?->id,
            'user_id' => $employee?->user_id,
            'fore_name'       => $employee?->fore_name,
            'sur_name'        => $employee?->sur_name,
            'email'           => $employee?->email,
            'gender'          => $employee?->gender,
            'ni_number'       => $employee?->ni_number,
            'sia_licence'     => $employee?->sia_licence,
            'sia_expiry'      => $employee?->sia_expiry,
            'licence_type'    => $employee?->licence_type,
            'license_number'  => $employee?->license_number,
            'license_expiry'  => $employee?->license_expiry,
            'entry_date'      => $employee?->entry_date,
            'dob'             => $employee?->dob,
            'service_type'    => $employee?->service_type,
            'visa_type'       => $employee?->visa_type,
            'visa_expiry'     => $employee?->visa_expiry,
            'share_code'      => $employee?->share_code,
            'share_code_expiry' => $employee?->share_code_expiry,
            'biometric_residence_permit' => $employee?->biometric_residence_permit,
            'biometric_residence_permit_expiry' => $employee?->biometric_residence_permit_expiry,
            'place_work'      => $employee?->place_work,
            'contact'         => $employee?->contact,
            'emergency_contact' => $employee?->emergency_contact,
            'job_title'       => $employee?->job_title,
            'nationality'     => $employee?->nationality,
            'passport_no'     => $employee?->passport_no,
            'passport_expiry' => $employee?->passport_expiry,
            'address_group'   => $employee?->address_group,
            'address'   => $employee?->address,
            'guard_rate'      => $employee?->guard_rate,
            'bank_name'       => $employee?->bank_name,
            'bank_branch'     => $employee?->bank_branch,
            'sort_code'       => $employee?->sort_code,
            'account_name'    => $employee?->account_name,
            'account_number'  => $employee?->account_number,
            'other_info'      => $employee?->other_info,
            'employment_start_date' => $employee?->employment_start_date,
            'employment_end_date' => $employee?->employment_end_date,
            
            // Document files
            'profile_picture' => $employee?->profile_picture,
            'signature'       => $employee?->signature,
            'sia_licence_file' => $employee?->sia_licence_file,
            'passport_file' => $employee?->passport_file,
            'proof_of_address_file' => $employee?->proof_of_address_file,
            'ni_letter_file' => $employee?->ni_letter_file,
            'first_aid_certificate_file' => $employee?->first_aid_certificate_file,
            'act_certificate_file' => $employee?->act_certificate_file,
            'driving_licence_file' => $employee?->driving_licence_file,
            'driving_licence_number' => $employee?->driving_licence_number,
            'driving_licence_expiry' => $employee?->driving_licence_expiry,
            'additional_files' => $employee?->additional_files,

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
    

    public function processSia(Request $request)
    {
        $user = auth()->user();
        \Log::info('EmployeeController: processSia web trigger started', ['by' => $user->id]);

        $siaChecker = app(SiaLicenceChecker::class);
        $processed = 0;

        try {
            // Process employees in chunks to avoid memory/time spikes
            \App\Models\Employee::whereNotNull('sia_licence')->chunk(100, function($employees) use ($siaChecker, &$processed) {
                foreach ($employees as $employee) {
                    try {
                        $result = $siaChecker->checkByLicenceNumber($employee->sia_licence, true);
                        $newStatus = (!empty($result) && !empty($result['valid'])) ? 'Active' : 'Inactive';
                        if ($employee->sia_status !== $newStatus) {
                            $employee->sia_status = $newStatus;
                            $employee->save();
                        }
                        $processed++;
                    } catch (\Throwable $e) {
                        \Log::error('processSia: failed for employee ' . ($employee->id ?? 'unknown') . ': ' . $e->getMessage());
                    }
                }
            });

            \Log::info('EmployeeController: processSia completed', ['processed' => $processed]);
            return response()->json(['processed' => $processed]);
        } catch (\Throwable $e) {
            \Log::error('EmployeeController: processSia failed: ' . $e->getMessage());
            return response()->json(['error' => 'processing failed'], 500);
        }
    }


}
