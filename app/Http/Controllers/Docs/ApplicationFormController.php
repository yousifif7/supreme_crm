<?php

namespace App\Http\Controllers\Docs;

use App\Http\Requests\Docs\StoreApplicationFormRequest;
use App\Http\Requests\Docs\UpdatAapplicationFormRequest;
use App\Models\Docs\Application_Form;
use App\Models\Docs\Education;
use Illuminate\Http\Request;
use App\Models\Docs\Previous_Employment;
use App\Models\Docs\Apllication_Form_Undertaking;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;


class ApplicationFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
                return view('docs.application_form.admin_applicationform');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('docs.application_form.application_create');
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // Validation rules (all REQUIRED as you asked)
        $rules = [
            'attached_doc' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',

            'position_applied' => 'required|string|max:255',
            'passport_brp_others' => 'required|string|max:255',
            'min_letter_proof_other' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'forename' => 'required|string|max:255',
            'surname_of_birth' => 'required|string|max:255',

            'date_of_birth' => 'required|date',
            'current_address' => 'required|string|max:1000',
            'post_code' => 'required|string|max:20',
            'from' => 'required|date',
            'to' => 'required|date',
            'previous_address' => 'required|string|max:1000',
            'post_code_prev' => 'required|string|max:20',
            'from_prev' => 'required|date',
            'to_prev' => 'required|date',
            'from_last' => 'required|date',
            'to_last' => 'required|date',

            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:50',
            'telephone' => 'required|string|max:50',

            'nationality' => 'required|string|max:100',
            'date_and_place_enter_in_uk' => 'required|string|max:255',
            'visa_type' => 'required|string|max:255',
            'national_insurance_no' => 'required|string|max:100',
            'passport_number' => 'required|string|max:100',

            'sia_license_sect' => 'required|string|max:255',
            'sia_license_no' => 'required|string|max:255',
            'sia_license_expiry' => 'required|date',
            'type_driving_license' => 'required|string|max:255',
            'own_passport' => 'required|string|max:255',
            'driving_license_no' => 'required|string|max:255',
            'dvla_license_check_code' => 'required|string|max:255',

            'disquilifed' => 'required|string|max:255',
            'motoring_' => 'required|string|max:255',
            'offence' => 'required|string|max:2000',
            'equal_opportunities' => 'required|string|max:2000',
            'purpose_job_accept' => 'required|string|max:2000',

            'application_name' => 'required|string|max:255',
            'ni_number' => 'required|string|max:100',
            'applicant_signature' => 'required|string|max:10000',
            'appli_date' => 'required|date',

            'employee_name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'employee_id' => 'required|string|max:255',
            'i_name' => 'required|string|max:255',
            'employee_signature' => 'required|string|max:10000',
            'employee_date' => 'required|date',
            'agreement_company' => 'required|string|max:1000',
            'agreement_employee' => 'required|string|max:1000',

            'staff_name' => 'required|string|max:255',
            'staff_sign' => 'required|string|max:10000',
            'staff_date' => 'required|date',
            'company_represent_name' => 'required|string|max:255',
            'company_represent_sign' => 'required|string|max:10000',
            'stacompany_represent_date' => 'required|date',

            'ethnic' => 'required|string|max:255',

            'full_name' => 'required|string|max:255',
            'date' => 'required|date',
            'date_sign' => 'required|date',

            // arrays - require at least one entry
            'education' => 'required|array|min:1',
            'education.*.types_of_institute' => 'required|string|max:255',
            'education.*.name_of_institute' => 'required|string|max:255',
            'education.*.address_institute' => 'required|string|max:1000',
            'education.*.from' => 'required|date',
            'education.*.to' => 'required|date',
            'education.*.grade' => 'required|string|max:255',

            'previous_employment' => 'required|array|min:1',
            'previous_employment.*.name' => 'required|string|max:255',
            'previous_employment.*.position' => 'required|string|max:255',
            'previous_employment.*.from' => 'required|date',
            'previous_employment.*.to' => 'required|date',
            'previous_employment.*.reason_leaving' => 'required|string|max:1000',
            'previous_employment.*.address_postcode' => 'required|string|max:255',
            'previous_employment.*.manager' => 'required|string|max:255',
            'previous_employment.*.tel_no' => 'required|string|max:100',
            'previous_employment.*.Salary' => 'required|string|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            // If AJAX request, return JSON with 422
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // otherwise redirect back with errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If validation passed, proceed to handle files and save
        try {
            // Prepare file names
            $attachedDocOriginal = $request->file('attached_doc')->getClientOriginalName();
            $attachedDocOriginal = str_replace(' ', '-', $attachedDocOriginal);
            $attachedDocName = time() . '_' . $attachedDocOriginal;

            $profileImageOriginal = $request->file('profile_image')->getClientOriginalName();
            $profileImageOriginal = str_replace(' ', '-', $profileImageOriginal);
            $profileImageName = (time()+1) . '_' . $profileImageOriginal;

            // Paths (public)
            $attachedDocPath = public_path('application_form/documents');
            $profileImagePath = public_path('application_form/profile');

            if (!file_exists($attachedDocPath)) {
                mkdir($attachedDocPath, 0777, true);
            }
            if (!file_exists($profileImagePath)) {
                mkdir($profileImagePath, 0777, true);
            }

            // Move files
            $request->file('attached_doc')->move($attachedDocPath, $attachedDocName);
            $request->file('profile_image')->move($profileImagePath, $profileImageName);

            // Create main application_form record
            $store = new application_form();

            $store->attached_doc = 'application_form/documents/' . $attachedDocName;
            $store->profile_image = 'application_form/profile/' . $profileImageName;

            // fill text fields (use fillable or assign individually)
            $store->position_applied = $request->position_applied;
            $store->passport_brp_others = $request->passport_brp_others;
            $store->min_letter_proof_other = $request->min_letter_proof_other;
            $store->name = $request->name;
            $store->surname = $request->surname;
            $store->forename = $request->forename;
            $store->surname_of_birth = $request->surname_of_birth;
            $store->date_of_birth = $request->date_of_birth;
            $store->current_address = $request->current_address;
            $store->post_code = $request->post_code;
            $store->from = $request->from;
            $store->to = $request->to;
            $store->previous_address = $request->previous_address;
            $store->post_code_prev = $request->post_code_prev;
            $store->from_prev = $request->from_prev;
            $store->to_prev = $request->to_prev;
            $store->from_last = $request->from_last;
            $store->to_last = $request->to_last;
            $store->email = $request->email;
            $store->mobile = $request->mobile;
            $store->telephone = $request->telephone;
            $store->nationality = $request->nationality;
            $store->date_and_place_enter_in_uk = $request->date_and_place_enter_in_uk;
            $store->visa_type = $request->visa_type;
            $store->national_insurance_no = $request->national_insurance_no;
            $store->passport_number = $request->passport_number;
            $store->sia_license_sect = $request->sia_license_sect;
            $store->sia_license_no = $request->sia_license_no;
            $store->sia_license_expiry = $request->sia_license_expiry;
            $store->type_driving_license = $request->type_driving_license;
            $store->own_passport = $request->own_passport;
            $store->driving_license_no = $request->driving_license_no;
            $store->dvla_license_check_code = $request->dvla_license_check_code;
            $store->disquilifed = $request->disquilifed;
            $store->motoring_ = $request->motoring_;
            $store->offence = $request->offence;
            $store->equal_opportunities = $request->equal_opportunities;
            $store->purpose_job_accept = $request->purpose_job_accept;

            $store->application_name = $request->application_name;
            $store->ni_number = $request->ni_number;
            $store->applicant_signature = $request->applicant_signature;
            $store->appli_date = $request->appli_date;

            $store->employee_name = $request->employee_name;
            $store->job_title = $request->job_title;
            $store->employee_id = $request->employee_id;
            $store->i_name = $request->i_name;
            $store->employee_signature = $request->employee_signature;
            $store->employee_date = $request->employee_date;
            $store->agreement_company = $request->agreement_company;
            $store->agreement_employee = $request->agreement_employee;

            $store->staff_name = $request->staff_name;
            $store->staff_sign = $request->staff_sign;
            $store->staff_date = $request->staff_date;
            $store->company_represent_name = $request->company_represent_name;
            $store->company_represent_sign = $request->company_represent_sign;
            $store->stacompany_represent_date = $request->stacompany_represent_date;

            $store->ethnic = $request->ethnic;

            $store->save();

            // Save Education entries
            if ($request->has('education') && is_array($request->education)) {
                foreach ($request->education as $edu) {
                    Education::create([
                        'application_id' => $store->id,
                        'types_of_institute' => $edu['types_of_institute'],
                        'name_of_institute' => $edu['name_of_institute'],
                        'address_institute' => $edu['address_institute'],
                        'from' => $edu['from'],
                        'to' => $edu['to'],
                        'grade' => $edu['grade'],
                    ]);
                }
            }

            // Save Previous Employment entries
            if ($request->has('previous_employment') && is_array($request->previous_employment)) {
                foreach ($request->previous_employment as $employment) {
                    Previous_employment::create([
                        'application_id'     => $store->id,
                        'name'               => $employment['name'],
                        'position'           => $employment['position'],
                        'from'               => $employment['from'],
                        'to'                 => $employment['to'],
                        'reason_leaving'     => $employment['reason_leaving'],
                        'address_postcode'   => $employment['address_postcode'],
                        'manager'            => $employment['manager'],
                        'tel_no'             => $employment['tel_no'],
                        'Salary'             => $employment['Salary'],
                    ]);
                }
            }

            // Undertaking
            $undertaking = new apllication_form_undertaking();
            $undertaking->application_id = $store->id;
            $undertaking->full_name = $request->full_name;
            $undertaking->job_title = $request->job_title; // if you want different field, change
            $undertaking->date = $request->date;
            $undertaking->date_sign = $request->date_sign;
            $undertaking->save();

            // If AJAX -> JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Form submitted and files uploaded successfully!'], 200);
            }

            // otherwise redirect back with success
            return back()->with('success', 'Form submitted and files uploaded successfully!');
        } catch (\Exception $e) {
            // log error if you want: \Log::error($e);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Server error. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(application_form $application_form)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $req)
    {
        $id=application_form::find($req->id);
                return view('docs.application_form.show_application',compact('id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updateapplication_formRequest $request, application_form $application_form)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(application_form $application_form)
    {
        //
    }
}
