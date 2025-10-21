<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Supreme Protection - Online Application</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <style>
 <style>
 

    body {
      margin: 0;
      background: #f0f0f0;
      font-family: "Roboto", sans-serif;
      font-size: 12px;
    }
@media only screen and (max-width:600px){
  .a4-page{
    overflow: auto;
  }
}
textarea{
    border:0!important;
        resize: none;

}


    .preview-wrapper {
      display: flex;
      justify-content: center;
      padding: 20px 0;
    }

    input{
      display: block;
    width: 100%;
    border: 0;
}

    .a4-page {
      width: 794px;
      min-height: 1123px;
      background: white;
      padding: 30px;
      box-sizing: border-box;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
      position: relative;
    }

    h1 {
      text-align: center;
      font-size: 18px;
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    td, th {
      border: 1px solid #000;
      padding: 4px 6px;
      vertical-align: top;
    }

    .section-title {
      /* background: #e6e6e6; */
      font-weight: bold;
      font-size: 16px;
      text-align: left;
      padding: 6px;
      font-family: "Times New Roman";
      margin-top: 10px;
    }

    .photo-placeholder {
      width: 120px;
      height: 120px;
      border: 1px solid #000;
      /* margin: 10px auto; */
    }

    .footer-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .footer-table td {
      font-size: 10px;
      border: 1px solid #000;
      padding: 4px;
    }
 .education-table {
    width: 100%;
    border-collapse: collapse;
  }

  .education-table td {
    padding: 0;
    height: 100px; /* set td height so input is tall */
  }

  .education-table input.form-control {
    width: 100%;
    height: 100%;
    padding: 30px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 18px;
  }

  .education-table input.form-control:focus {
    outline: none;
    border: 2px solid #007bff;
  }
    .education-table  th{
        padding: 30px;
    }
    .img-parent{
        display: flex;
        justify-content: end;

    }
    .img-parent img{
        width: auto;
        height: 100px;
    }
    .previous-employment-table td ,th{
padding: 15px;
    }
    .employee-form li{
      line-height: 30px;
  
    }
    .non-disclosure-taking-two p , .non-disclosure-taking-two li{
      line-height: 19px;
    }
    .non-disclosure-taking-three p , .non-disclosure-taking-three li , .non-disclosure-taking-three strong{
      line-height: 19px;
    }
  </style>
</head>
<body>
<form action="{{ route('application.form.store') }}" method="post" enctype="multipart/form-data">
@csrf
<div class="preview-wrapper">
  <div class="a4-page">
    <div class="img-parent">
                                <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="" height="40px">
    </div>
    <h1 style="margin-bottom: 25px;">Application Form</h1>

    <table>
      <tr style="display: flex;justify-content: space-between;">
       <td style="width:50%">
        <div style="border-bottom: 1px solid black; padding-bottom: 10px;margin-bottom: 10px;">
            <strong>Position Applied for:<input readonly type="text" class="form-control" name="position_applied" value="{{$id->position_applied}}"></strong>
        </div>
        
          <strong>Attached Documents:
            <input readonly type="file" class="form-control" name="attached_doc"></strong>
            <img src="{{asset('public/'.$id->attached_doc)}}" width="40px" height="40px">
            <br><br>
          <strong>Passport – BRP – SIA License – Proof of Address × 2</strong>
          <input readonly type="text" class="form-control" name="passport_brp_others" value="{{$id->passport_brp_others}}"><br><br>
         <strong>NIN Letter – Proof of RTW – Supporting Documents
            <input readonly type="text" class="form-control" name="min_letter_proof_other" value="{{$id->min_letter_proof_other}}"></strong> 
        </td>
        <td style="width:30%; text-align:center; border: none; display: flex; justify-content: end;">
            
            <input readonly type="file" id="photoInput" accept="image/*" style="display: none;" name="profile_image">
            
            <div class="photo-placeholder" onclick="document.getElementById('photoInput').click();" 
                 style="width: 100px; height: 100px; background-color: #f0f0f0; border: 1px dashed #ccc; cursor: pointer; background-size: cover; background-position: center;">
                <!-- Placeholder text -->
            <img src="{{asset('public/'.$id->profile_image)}}" width="100px" height="100px">
                <span style="line-height: 100px; display: inline-block; width: 100%; color: #888;">Click to upload</span>
            </div>
        </td>
      </tr>
    </table>

    <div class="section-title">Personal Information:</div>
    <table>
      <tr>
        <td>Title (Mr. / Mrs. / Miss): <input readonly type="text" class="form-control" name="name" value="{{$id->name}}"></td>
        <td>Surname:<input readonly type="text" class="form-control" name="surname" value="{{$id->surname}}"></td>
      </tr>
      <tr>
        <td>Forename:<input readonly type="text" class="form-control" name="forename" value="{{$id->forename}}"></td>
        <td>Surname at Birth:<input readonly type="text" class="form-control" name="surname_of_birth" value="{{$id->surname_of_birth}}"></td>
      </tr>
      <tr>
        <td>Date of Birth:<input readonly type="text" class="form-control" name="date_of_birth" value="{{$id->date_of_birth}}"></td>
        
      </tr>
    </table>
    

    <div class="section-title">Please Provide Last 5 Years Address History</div>
    <table>
      <tr>
        <td>Current Address: <textarea class="form-control" name="current_address" id="" cols="15" rows="2">{{$id->current_address}}</textarea></td>
        <td>Post Code:<input readonly type="number" class="form-control" name="post_code">{{$id->post_code}}</td>
        <td>From:<textarea class="form-control" name="from" id="" cols="20" rows="2">{{$id->from}}</textarea></td>
        <td>To:<textarea class="form-control" name="to" id="" cols="20" rows="2">{{$id->to}}</textarea></td>
      </tr>
      <tr>
        <td>Previous Address:<<textarea class="form-control" name="previous_address" id="" cols="20" rows="2">{{$id->previous_address}}</textarea></td>
        <td>Post Code:<textarea class="form-control" name="post_code_prev" id="" cols="20" rows="2">{{$id->post_code_prev}}</textarea></td>
        <td>From:<textarea class="form-control" name="from_prev" id="" cols="20" rows="2">{{$id->from_prev}}</textarea></td>
        <td>To:<textarea class="form-control" name="to_prev" id="" cols="20" rows="2">{{$id->to_prev}}</textarea></td>
      </tr>
      <tr>
        <td colspan="2">From:<input readonly type="date" class="form-control" name="from_last" value="{{$id->from_last}}"></td>
        <td colspan="2">To:<input readonly type="date" class="form-control" name="to_last" value="{{$id->to_last}}"></td>
      </tr>
    </table>

    <table>
      <tr>
        <td>Email:<input readonly type="text" class="form-control" name="email" value="{{$id->email}}"></td>
        <td>Mobile:<input readonly type="text" class="form-control" name="mobile" value="{{$id->mobile}}"></td>
        <td>Telephone:<input readonly type="text" class="form-control" name="telephone" value="{{$id->telephone}}"></td>
      </tr>
      <tr>
        <td>Nationality:<input readonly type="text" class="form-control" name="nationality" value="{{$id->nationality}}"></td>
        <td colspan="2">Date and Place of entry into UK:<input readonly type="text" class="form-control" name="date_and_place_enter_in_uk" value="{{$id->date_and_place_enter_in_uk}}"></td>
      </tr>
      <tr>
        <td>Visa Type:<input readonly type="text" class="form-control" name="visa_type" value="{{$id->visa_type}}"></td>
        <td colspan="2">National Insurance Number:<input readonly type="text" class="form-control" name="national_insurance_no" value="{{$id->national_insurance_no}}"></td>
      </tr>
      <tr>
        <td>Passport Number:<input readonly type="text" class="form-control" name="passport_number" value="{{$id->passport_number}}"></td>
        <td>SIA License Sector:<input readonly type="text" class="form-control" name="sia_license_sect" value="{{$id->sia_license_sect}}"></td>
      </tr>
      <tr>
        <td>SIA License Number:<input readonly type="text" class="form-control" name="sia_license_no" value="{{$id->sia_license_no}}"></td>
        <td>SIA License Expiry:<input readonly type="text" class="form-control" name="sia_license_expiry" value="{{$id->sia_license_expiry}}"></td>
      </tr>
    </table>

    <div class="section-title">Driving:</div>
    <table>
      <tr>
        <td>Type of Driving License:<input readonly type="text" class="form-control" name="type_driving_license" value="{{$id->type_driving_license}}"></td>
        <td>Own Transport:<input readonly type="text" class="form-control" name="own_passport" value="{{$id->own_passport}}"></td>
      </tr>
      <tr>
        <td>Driving License Number:<input readonly type="text" class="form-control" name="driving_license_no" value="{{$id->driving_license_no}}"></td>
        <td>DVLA License Check Code:<input readonly type="text" class="form-control" name="dvla_license_check_code" value="{{$id->dvla_license_check_code}}"></td>
      </tr>
      <tr>
        <td>Have you ever been disqualified?<input readonly type="text" class="form-control" name="disquilifed" value="{{$id->disquilifed}}"></td>
        <td>Any Motoring offences/convictions?<input readonly type="text" class="form-control" name="motoring_" value="{{$id->motoring_}}"></td>
      </tr>
      <tr>
        <td colspan="2">If yes, please provide description of your offence's below.
          <input readonly type="text" class="form-control" name="offence" value="{{$id->offence}}">
        </td></tr>
    </table>

    <div class="section-title">Criminal Convictions:</div>
    <table>
      <tr><td colspan="2">Have you, ever been fined, cautioned, sentenced to imprisonment or placed on probation for a criminal act (subject to the Rehabilitation of Offenders Act)?</td></tr>
      <tr><td colspan="2">Are there any alleged offences outstanding against you?</td></tr>
      <tr><td colspan="2">Have you, ever been made bankrupt or have any Court Judgements against you, whether satisfied or not, within the last 6 years?</td></tr>
      <tr><td colspan="2">Has any order been made against you by a Civil or Military Court or Public Authority?</td></tr>
      <tr><td colspan="2">If yes to either question give details:</td></tr>
    </table>

    <!-- Footer in table format -->
    <table class="footer-table" style="margin-top: 100px;">
      <tr>
        <td style="width:33%">Reference No: F-SP-02-R3-01-p1</td>
        <td style="width:34%; text-align:center;">Page 1 of 4</td>
        <td style="width:33%; text-align:right;">Issue No: 1</td>
      </tr>
      <tr>
        <td colspan="2">Address: 150 Chingford Rd., London, England, E17 4PL.</td>
        <td style="text-align:right;">Issue Date: 02/01/2015</td>
      </tr>
    </table>
    <div style="margin-top: 100px;">

    </div>
    <div class="section-title">Education:</div>
   <table class="education-table">
  <tr>
    <th>Type of Institute</th>
    <th>Name of Institute</th>
    <th>Address of Institute</th>
    <th>From</th>
    <th>To</th>
    <th>Grades</th>
  </tr>
  @php
  $education=App\Models\Docs\Education::where('application_id',$id->id)->get();
  @endphp
  @foreach($education as $show)
  <tr>

    
    <td><textarea name="types_of_institute" id="" cols="13" rows="6" class="form-control">{{$show->types_of_institute}}</textarea></td>
    <td><textarea name="name_of_institute" id="" cols="13" rows="6" class="form-control">{{$show->name_of_institute}}</textarea></td>
    <td><textarea name="address_institute" id="" cols="13" rows="6" class="form-control">{{$show->address_institute}}</textarea></td>

    <td><input readonly type="date" name="from" class="form-control" value="{{$show->from}}"></td>
    <td><input readonly type="date" name="to" class="form-control" value="{{$show->to}}"></td>
    <td><input readonly type="text" name="grade" class="form-control" value="{{$show->grade}}"></td>
  </tr>
  @endforeach
</table>

  
    <div class="section-title">Equal Opportunities:</div>
    <p style="font-weight: 600;line-height: 18px;"><em>This section is voluntary and will NOT be used in assessing your application. Supreme Protection Ltd is an equal opportunities employer. If you decide to complete this section, it will help us to monitor the effectiveness of our Equal Opportunities Policy. Please tick the appropriate box below.</em></p>
  
<table style="width: 200px;margin-top: 20px;" class="once-checkbpx-">
  <tr>
    <td>British</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="British" {{ $id->equal_opportunities == 'British' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>African</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="African" {{ $id->equal_opportunities == 'African' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>Asian</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="Asian" {{ $id->equal_opportunities == 'Asian' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>Caribbean</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="Caribbean" {{ $id->equal_opportunities == 'Caribbean' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>Chinese</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="Chinese" {{ $id->equal_opportunities == 'Chinese' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>White</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="White" {{ $id->equal_opportunities == 'White' ? 'checked' : '' }}></td>
  </tr>
  <tr>
    <td>Other</td>
    <td><input readonly type="checkbox" class="form-control" name="equal_opportunities" value="Other" {{ $id->equal_opportunities == 'Other' ? 'checked' : '' }}></td>
  </tr>
</table>


    <p>My ethnic origin is:<input readonly type="text" class="form-control" name="ethnic" value="{{$id->ethnic}}"></p>
    <div style="margin-top: 50px;"></div>
    <p><strong>If other, please specify</strong></p>
    <div style="margin-top: 100px;"></div>
    <table class="footer-table">
      <tr>
        <td style="width:33%">Reference No: F-SP-02-R3-01-p1</td>
        <td style="width:34%; text-align:center;">Page 2 of 4</td>
        <td style="width:33%; text-align:right;">Issue No: 1</td>
      </tr>
      <tr>
        <td colspan="2">Address: 150 Chingford Rd., London, England, E17 4PL.</td>
        <td style="text-align:right;">Issue Date: 02/01/2015</td>
      </tr>
    </table>

<div style="margin-top: 100px;"></div>
    <div class="section-title" style="margin-bottom: 0px;">Previous Employment:</div>
    <p style="margin: 0px; margin-bottom: 20px;"><em>
      State all periods of employment, unemployment and self-employment for the last 5 years or since leaving school. For any periods of unemployment, state the address of the Unemployment Benefit Office at which you reported. Start with present situation.
    </em></p>
@php
    $employments = App\Models\Docs\Previous_Employment::where('application_id', $id->id)->get();
@endphp

@foreach ($employments as $index => $emp)
<table class="previous-employment-table" border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
    <tr>
        <th colspan="2">Employers Details:</th>
        <th colspan="2">Employment Details:</th>
        <th>From:</th>
        <th>To:</th>
        <th>Reason for leaving:</th>
    </tr>
    <tr>
        <td style="width: 15%;">Name:</td>
        <td style="width: 20%;">
            <textarea name="previous_employment[{{ $index }}][name]" cols="10" rows="3" class="form-control">{{ $emp->name }}</textarea>
        </td>
        <td style="width: 15%;">Position:</td>
        <td style="width: 20%;">
            <textarea name="previous_employment[{{ $index }}][position]" cols="10" rows="3" class="form-control">{{ $emp->position }}</textarea>
        </td>
        <td style="width: 8%;" rowspan="3">
            <input readonly type="date" name="previous_employment[{{ $index }}][from]" class="form-control" value="{{ $emp->from }}">
        </td>
        <td style="width: 8%;" rowspan="3">
            <input readonly type="date" name="previous_employment[{{ $index }}][to]" class="form-control" value="{{ $emp->to }}">
        </td>
        <td style="width: 14%;" rowspan="3">
            <textarea name="previous_employment[{{ $index }}][reason_leaving]" cols="10" rows="12" class="form-control">{{ $emp->reason_leaving }}</textarea>
        </td>
    </tr>
    <tr>
        <td>Address & Postcode:</td>
        <td>
            <textarea name="previous_employment[{{ $index }}][address_postcode]" cols="10" rows="3" class="form-control">{{ $emp->address_postcode }}</textarea>
        </td>
        <td>Manager:</td>
        <td>
            <textarea name="previous_employment[{{ $index }}][manager]" cols="10" rows="3" class="form-control">{{ $emp->manager }}</textarea>
        </td>
    </tr>
    <tr>
        <td>Tel No:</td>
        <td>
            <textarea name="previous_employment[{{ $index }}][tel_no]" cols="10" rows="3" class="form-control">{{ $emp->tel_no }}</textarea>
        </td>
        <td>Salary:</td>
        <td>
            <textarea name="previous_employment[{{ $index }}][Salary]" cols="10" rows="3" class="form-control">{{ $emp->Salary }}</textarea>
        </td>
    </tr>
</table>
@endforeach


      <div style="margin-top: 100px;"></div>
    <table class="footer-table">
      <tr>
        <td style="width:33%">Reference No: F-SP-02-R3-01-p1</td>
        <td style="width:34%; text-align:center;">Page 3 of 4</td>
        <td style="width:33%; text-align:right;">Issue No: 1</td>
      </tr>
      <tr>
        <td colspan="2">Address: 150 Chingford Rd., London, England, E17 4PL.</td>
        <td style="text-align:right;">Issue Date: 02/01/2025</td>
      </tr>
    </table>

<div style="margin-top: 100px;"></div>
    <div style="font-size: 12px; line-height: 1.5;">
      <h2 class="section-title" style="text-align: center; text-decoration: underline;">Declarations</h2>
      
      <p>I certify that to the best of my knowledge, the information that I have given in my application for employment is true and complete and understand that any false statement or omission to Supreme Protection Ltd or its representatives may render lead to termination of employment without notice.</p>
      
      <p>I confirm that the information I have provided on my application is true and complete to the best of my knowledge. I Understand and agree that I will be subject to any or all of the following checks: Address check, Financial Probity check which company will retain on file, Id verification check, Academic / Professional qualification check, Employment history, including any periods of unemployment/self-employment and any gaps, Criminal background check. I understand and agree that if so required I will make a Statutory Declaration in accordance with the provisions of the Statutory Declarations Act 1835 in confirmation of previous unemployment or unemployment.</p>
      
      <p>I authorise Supreme Protection Ltd or its nominated agents to carry out financial history check /credit check and approach Government agencies, former employers, educational establishments, for information relating to or verification of my employment/unemployment record. I authorise Supreme Protection Ltd to make a consumer information search and ID checks with a credit reference agency, which will keep a record of that search and may share that information with other credit reference agencies.</p>
      
      <p>I consent to Supreme Protection Ltd reasonable processing of any personal information obtained for the purposes of establishing my medical condition and future fitness to perform my duties. I accept that I may be required to undergo a medical examination where requested by Supreme Protection Ltd Subject to the Access to Medical Reports Act 1988, I consent to the results of such examinations to be given to Supreme Protection Ltd.</p>
      
      <p>I further declare that any documents that I provide as proof of my identity, proof of address, proof of right to work and any other documents that I provide are genuine and give my consent for these documents to be examined under a UV scanner or similar device. I acknowledge that any falsified documents may be reported to the appropriate authority. I understand that it may be a criminal offence to attempt to obtain employment by deception and that any misrepresentation, omission of a material fact or deception will be cause for immediate withdrawal of any offer of employment made.</p>
    
      <p><strong>General Data Protection Regulations (GDPR)</strong> - Supreme Protection Ltd will use the information you have given on your application form (together with any information which we obtain with your consent from third parties) for assessing your suitability for employment. It may be necessary to disclose your information to our agents and other service providers. By returning this form to Supreme Protection Ltd you consent to our processing personal data about you where this is necessary, for example information about your credit status, ethnic origin or criminal offences. You also consent to the transfer of your information to your current and future potential employers where this is necessary (this may be to companies operating abroad if you apply for work outside of the United Kingdom). Your information will be held on our computer database and/or in our paper filing systems. By signing below, you agree to this process and confirm that you do not have a criminal record subject to the current Rehabilitation of Offenders Act and any amendments. You have the right to apply for a copy of your information and to have any inaccuracies corrected.</p>
    
      <p><strong>Disclosure</strong> - You are applying for a position of trust and in the event of being offered employment by Supreme Protection Ltd we may apply for a Disclosure. However, having a criminal record does not necessarily bar you from employment. For more information, ask a member of staff for a copy of the CRB Code of Practice/Disclosure Scotland and/or Company our policy statement regarding ex-offenders. Disclosure information is treated in a sensitive way and is restricted to those who need to see it to make a recruitment decision. By signing this document, you allow Supreme Protection Ltd to see a copy of the Disclosure. The Disclosure information is not retained i.e. it is disposed of within the timescales recommended in the CRB Code of Practice. By signing below, you agree to this process.</p>
    
      <p><strong>Screening</strong> - Any offer of employment is subject to satisfactory screening, that the applicant consents to being screened in accordance with BS7858 and will provide information as required. That the information provided is correct, and the applicant acknowledges that any false statements or omissions could lead to termination of employment.</p>
    
      <p style="font-weight: bold;display: flex;"><input readonly type="radio" class="form-control" style="width: 2%;" name="purpose_job_accept" {{ $id->purpose_job_accept == 'on' ? 'checked' : '' }}>I confirm that my consent is explicit, fully informed and freely given for the purposes of this job.</p>
    
      <br><br>
    
      <table style="width: 100%; border: 1px solid #000; border-collapse: collapse;" cellpadding="10">
        <tr>
          <td style="width: 33%; border: 1px solid #000; padding: 12px;">Applicant Name:<input readonly type="text" class="form-control" name="application_name" value="{{$id->application_name}}"></td>
          <td style="width: 33%; border: 1px solid #000;padding: 12px;">NI Number:<input readonly type="text" class="form-control" name="ni_number" value="{{$id->ni_number}}"></td>
         
        </tr>
        <tr>
          <td style="border: 1px solid #000;padding: 12px;">Applicant Signature:<input readonly type="text" class="form-control" name="applicant_signature" value="{{$id->applicant_signature}}"></td>
          <td style="width: 34%; border: 1px solid #000;padding: 12px;">Date:<input readonly type="date" class="form-control" name="appli_date" value="{{$id->appli_date}}"></td>
      
        </tr>
      </table>
    
      <br><br><br>
    
      <div style="font-size: 10px; text-align: left;">
        <table style="width: 100%;">
          <tr>
            <td>Reference No: F-SOP-HR-01-01</td>
            <td style="text-align: right;">Page 4 of 4</td>
          </tr>
          <tr>
            <td>Issue No: 1</td>
            <td style="text-align: right;">Issue Date: 02/01/2025</td>
          </tr>
          <tr>
            <td colspan="2">Address: 150 Chingford Rd, , London, England, E17 4PL</td>
          </tr>
        </table>
      </div>
    </div>
    

<div style="margin-top: 100px;"></div>

    <div style="font-size: 13px; line-height: 1.6; width: 100%; " class="employee-form">
      <p style="display: flex;"><strong>Employee Name:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;" name="employee_name" value="{{$id->employee_name}}"></p>
      <p style="display: flex;"><strong>Job Title:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;" name="job_title" value="{{$id->job_title}}"></p>
      <p style="display: flex;"><strong>Employee ID (if applicable):</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;" name="employee_id" value="{{ $id->employee_id}}"></p>
    
      <p><strong>Consent for Use of Photographs</strong></p>
      <p>
        I, <input readonly type="text" class="form-control" name="i_name" style="border-bottom: 1px solid;width: 30%;display: inline-flex;" value="{{$id->i_name}}">, hereby give my full and voluntary consent to Supreme Protection Ltd to capture, use, and publish my photographs and/or video recordings taken in the course of my employment for promotional and marketing purposes. I understand that these images may be used on the company’s website, social media platforms, printed materials, or other marketing and advertising channels.
      </p>
    
      <p>I acknowledge that:</p>
      <ul style="margin-top: -10px;">
        <li>My participation is voluntary, and I am not entitled to any compensation or royalties for the use of these images.</li>
        <li>Supreme Protection Ltd has the right to edit, modify, and distribute the images as needed for marketing and promotional activities.</li>
        <li>The images will not be used for any unlawful, misleading, or defamatory purposes.</li>
        <li>This consent remains in effect until I provide written notice requesting its withdrawal. However, I understand that withdrawing consent will not affect materials already published.</li>
      </ul>
    
      <p>
        By signing below, I confirm that I have read and understood this consent form and agree to the terms outlined above.
      </p>
    
      <br>
    
      <p><strong>Employee Signature:</strong><input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="employee_signature" value="{{$id->employee_signature}}"></p>
      <p><strong>Date:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="employee_date" value="{{$id->employee_date}}"></p>
    </div>
    
<div style="margin-top: 100px;"></div>


    <div style=" font-size: 13px; line-height: 1.6; width: 100%; text-align: left;">

      <p style="text-align: center;margin-bottom: 40px;">This Agreement is made between</p>
    
      <p style="text-align: center;">
        <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="agreement_company" value="{{$id->agreement_company}}"> ("The Company")
      </p>
    
      <p style="text-align: center;">And</p>
    
      <p style="text-align: center;">
<input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="agreement_employee" value="{{$id->agreement_employee}}">("The Employee")
      </p>
    
      <p style="margin-top: 30px;margin-bottom: 50px;">
        The Working Time Regulations 1998 provide that the average working week, including overtime, shall not exceed 48 hours. The Company and the worker agree that this limit shall not apply to the worker. This Agreement will remain in force indefinitely. The worker, or the Company, may terminate this Agreement at any time by giving not less than three months' written notice to the other.
      </p>
    
      <p><strong>Staff Member</strong></p>
      <p>Employee Name:<input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="staff_name" value="{{$id->staff_name}}">.</p>
      <p>
        Signed:<input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="staff_sign" value="{{$id->staff_sign}}">
        Date: <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="staff_date" value="{{$id->staff_date}}">.
      </p>
    
      <p style="margin-top: 50px;"><strong>For and on behalf of the Company</strong></p>
      <p>Company Representative Name:  <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="company_represent_name" value="{{$id->company_represent_name}}">
        <p>
        Signed:<input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="company_represent_sign" value="{{$id->company_represent_sign}}">
        Date: <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="stacompany_represent_date" value="{{$id->stacompany_represent_date}}">.
      </p>
    
    </div>
    
<div style="margin-top: 100px;"></div>

<div class="non-disclosure-taking">
  <!-- Header Logo -->
  <div class="img-parent">
                                <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="" height="40px">
</div>

  <!-- Header Text -->
  <div style="text-align: center; font-weight: bold; z-index: 1; position: relative; margin-top: 30px;">
    <h2 style="margin: 0; padding: 0;font-weight: 800;font-family: 'Times New Roman';font-size: 18px !important;" >NON-DISCLOSURE UNDERTAKING</h2>
    <div style="font-size: 14px; margin-top: -5px; font-weight: 600;margin-top: 10px;">
      FOR SUPREME PROTECTION LTD DISCLOSURE FOR EXTERNAL CONSULTANTS / SUB-<br>/CONTRACTORS’ & EMPLOYEES
    </div>
  </div>

  <!-- Form Section -->
  <div style="margin-top: 50px; z-index: 1; position: relative;">
    <p> Please write in clear and legible letters: </p>
  @php
  $apllication_form_undertaking=App\Models\Docs\Apllication_Form_Undertaking::where('application_id',$id->id)->first();
  @endphp
  
    <p style="margin: 0 0 15px 0;"><strong>Full Name:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="full_name" value="{{$apllication_form_undertaking->full_name}}"></p>
    <p style="margin: 0 0 15px 0;"><strong>Job Title:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="job_title" value="{{$apllication_form_undertaking->job_title}}"></p>
    <p style="margin: 0 0 15px 0;"><strong>Date:</strong> <input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="date" value="{{$apllication_form_undertaking->date}}"></p>
    <p style="margin: 10px 0 0 0;">(Hereinafter referred to as “<strong>You</strong>”)</p>
  </div>

  <!-- Agreement Intro -->
  <div style="margin-top: 30px; z-index: 1; position: relative;">
    <p style="margin-bottom: 10px;">
      hereby agrees to the following non-disclosure undertaking (the “<strong>Undertaking</strong>”) in favour of:
    </p>

    <div style="margin-left: 30px;margin-top: 14px;">
      <p style="margin: 0;margin-bottom: 10px;"><strong>Supreme Protection LTD</strong></p>
      <p style="margin: 0;margin-bottom: 10px;"><strong>150 Chingford Road</strong></p>
      <p style="margin: 0;margin-bottom: 10px;"><strong>London</strong></p>
      <p style="margin: 0;margin-bottom: 10px;"><strong>E17 4PL</strong></p>
      <p style="margin: 10px 0 0 0;margin-bottom: 10px;">Business reg. no. 08422367</p>
      <p style="margin: 0;">(Hereinafter referred to as “<strong>Supreme Protection LTD</strong>”)</p>
    </div>

    <p style="margin-top: 20px; font-size: 13px;line-height: 22px;">
      in connection with the <strong>provision of manned guarding (BS 7499) and/or door supervisor services (BS 7960)</strong>, during which process Supreme Protection LTD will share with You strictly confidential information as necessary (hereinafter referred to as the “<strong>Purpose</strong>”).
    </p>
  </div>

  <!-- Section 1: Confidential Information -->
  <div style="margin-top: 30px; z-index: 1; position: relative;">
    <p style="margin-bottom: 10px;"><strong>1. Confidential Information</strong></p>
    <p style="margin: 0 0 10px 0;line-height: 19px;">
      When using the term “<strong>Confidential Information</strong>” in this Undertaking, it shall mean i) any proprietary, confidential or otherwise sensitive commercial or technical information or ii) any personal data on Supreme Protection LTD’ or Supreme Protection LTD’ clients’ employees, disclosed by Supreme Protection LTD or by other members of the Supreme Protection LTD Group in relation to the Purpose.
    </p>
    <p style="margin: 0;line-height: 19px;">
      Such information shall be regarded as Confidential Information whether it is disclosed by act or by omission and irrespective of the form of communication. It remains Confidential Information whether it is retained in the form in which you originally received it or whether you later reflected it in copies, notes or other documents made by You. Information relating to the existence of this Undertaking as well as the discussions related to the above project shall also be considered Confidential Information.
    </p>
  </div>

  <!-- Footer -->
  <div style="margin-top: 100px; font-size: 10px; display: flex; justify-content: space-between; z-index: 2;">
    <div>Supreme Protection LTD – Internal Confidentiality Undertaking<br>Confidential</div>
    <div>06 January 2023<br>Page 1 of 3</div>
  </div>  



<!-- page two -->
<div style="margin-top: 100px;"></div>
<div class="img-parent">
                                <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="" height="40px">
</div>
<h3 style="margin-bottom: 20px;">2. Your obligations</h3>

<p>When signing this Confidentiality Undertaking, you agree to the following:</p>

<div style="margin-top: 10px;" class="non-disclosure-taking-two">
  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">a)</span>
    <span style="display: inline-block;">Direct contact made for the purpose of direct employment to the clients or partners of Supreme Protection LTD is strictly prohibited. You are not entitled to communicate with the clients or partners of Supreme Protection LTD for the purposes of any of the following (not limited to): job references, work experience, full time/part time or temporary based employment, any internships or scholarships or any such work-based placements either paid or unpaid. A breach of this condition will result in immediate termination of employment and further legal or financial action being taken against you. Should you come across any opportunities within any partners or clients of Supreme Protection LTD which may be of interest, you are to discuss with the management team of Supreme Protection LTD who will advise you of how best to approach the situation.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">b)</span>
    <span style="display: inline-block;"><strong>You agree not to share or discuss</strong> the Confidential Information with any colleagues – even within your own organisation, unless you have ensured that they have also signed a Confidentiality Undertaking or similar appropriate undertaking.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">c)</span>
    <span style="display: inline-block;">If You copy the Confidential Information (or if You share or discuss it if allowed under a) above), You will only do so to the extent strictly necessary to fulfil the Purpose and only with a confidentiality reference.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">d)</span>
    <span style="display: inline-block;">Additionally, You agree to keep the Confidential Information in <strong>strict confidence</strong> in your handling and storage of the information, including:</span>
  </p>

  <ul style="margin-left: 45px; margin-top: 0;">
    <li style="margin-bottom: 10px;">
      by generally protecting the Confidential Information with at least the <strong>same degree of care and discretion</strong> that You would use for any other strictly confidential Supreme Protection LTD information – and always at least with a reasonable degree of care.
    </li>
    <li style="margin-bottom: 10px;">
      by specifically taking the practical and technical steps in <strong>handling and storing the Confidential Information</strong> that are necessary to prevent that the information may accidentally be disclosed or misused – e.g. by storing the Confidential Information in a locked cabinet, not saving the information on shared disk drives, not leaving the information on your desk and by using safe printing methods; and
    </li>
    <li>
      for Confidential Information that contains personal data not copy, share or otherwise transfer such personal data outside the country in which You received the personal data.
    </li>
  </ul>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">e)</span>
    <span style="display: inline-block;">You agree to <strong>notify</strong> Supreme Protection LTD Legal immediately and no later than 12 hours, if You become aware of any unauthorised use or disclosure of the Confidential Information; and</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">f)</span>
    <span style="display: inline-block;">You will immediately <strong>delete and/or return</strong> all Confidential Information to Supreme Protection LTD, if You are requested to do so or when the Confidential Information is no longer necessary for the Purpose.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">g)</span>
    <span style="display: inline-block;">When working under an assignment booked by Supreme Protection LTD, you are required to state you are working for Supreme Protection LTD. No other Companies are to be mentioned or working history.</span>
  </p>
</div>
<div  style="margin-top: 100px; font-size: 10px; display: flex; justify-content: space-between; z-index: 2;">
  <div>Supreme Protection LTD – Internal Confidentiality Undertaking<br>Confidential</div>
  <div>06 January 2023<br>Page 2 of 3</div>
</div>  



<!-- page-3 -->
<div style="margin-top: 100px;"></div>
<div class="img-parent">
                                <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="" height="40px">
</div>

<div style="margin-top: 30px;" class="non-disclosure-taking-three">
  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">h)</span>
    <span>You are <strong>not to disclose</strong> any information’s around personnel payments or your own payments from the Company with any clients, visitors, customers or anyone in relation to the assignment or its customers.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">i)</span>
    <span>No photos or videos to be taken of any sites/ events / activities / customers / clients whilst undertaking an assignment from Supreme Protection LTD.</span>
  </p>

  <p style="display: flex; align-items: flex-start; margin: 10px 0;">
    <span style="min-width: 25px;">j)</span>
    <span>No information’s to be placed on any social media platforms around any assignments which you may be undertaking whilst working for or undertaking an assignment for Supreme Protection LTD.</span>
  </p>
</div>

<h3 style="margin-top: 30px; margin-bottom: 10px;">3. Duration and Termination</h3>
<p style="line-height: 19px;">The obligations under this Undertaking are binding for You from its signing, however, always at least five (5) years from the latest disclosure or for a longer period if required by law. For the avoidance of doubt, the obligations herein shall continue to apply even after your assignment with Supreme Protection LTD has ended.</p>

<h3 style="margin-top: 25px; margin-bottom: 10px;">4. Breach of confidentiality</h3>
<p style="line-height: 19px;">By signing below, You acknowledge that any unauthorised disclosure in breach of this Undertaking may have significant adverse consequences for Supreme Protection LTD and may cause Supreme Protection LTD to incur liability or fines towards clients, potential clients, public authorities or force Supreme Protection LTD to make a public announcement to the stock market in general.</p>

<p style="line-height: 19px;"><strong>You have been advised that the Confidential Information may also constitute insider information, and You will therefore have to comply with those instructions applicable to insider information and breach of such instructions may lead to personal liability according to applicable local law.</strong></p>

<h3 style="margin-top: 25px; margin-bottom: 10px;">5. Governing law and dispute resolution</h3>
<p style="line-height: 19px;">This Undertaking shall be governed by and construed in accordance with the laws of England without reference to its choice of law rules. Any dispute arising in connection with this Undertaking or a breach hereof, shall be finally and exclusively settled by the English courts.</p>
<p style="line-height: 19px;">Notwithstanding the above, Supreme Protection LTD shall be entitled to seek injunctive relief, or to taking any other immediate action, that may only be sought from another competent court.</p>

<h3 style="margin-top: 25px; margin-bottom: 10px;">6. Signatures</h3>
<p style="line-height: 19px;"><strong>Your signature:</strong></p>

<p style="margin-top: 60px;">
<input readonly type="text" class="form-control" style="border-bottom: 1px solid;width: 30%;display: inline-block;" name="date_sign" value="{{$apllication_form_undertaking->date_sign}}"><br>
  <span style="font-style: italic;">Date and signature</span>
</p>

<p style="margin-top: 30px; font-style: italic; line-height: 20px;"><strong>Please ensure that your name, title and the company is written in legible text at the top of this document.</strong></p>
<div  style="margin-top: 100px; font-size: 10px; display: flex; justify-content: space-between; z-index: 2;">
  <div>Supreme Protection LTD – Internal Confidentiality Undertaking<br>Confidential</div>
  <div>06 January 2023<br>Page 3 of 3</div>
</div>  

</div>




  </div>
  
</div>
<button type="submit">Submit</button>
</form>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function(){
    $('.once-checkbpx- input[type="checkbox"]').on('change', function(){
      $('.once-checkbpx- input[type="checkbox"]').not(this).prop('checked', false);
    });
  });
</script>

<script>
document.getElementById('photoInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const placeholder = document.querySelector('.photo-placeholder');
            placeholder.style.backgroundImage = `url(${e.target.result})`;
            placeholder.innerHTML = ''; // remove "Click to upload" text
        };
        
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
