<!DOCTYPE html>
@section('title')
@endsection

@include('docs.partial.style')

<style>
    .form-navigation {
        margin-bottom: 30px;
    }

    .open-picker {
        border-right: 0 !important;
        border-left: 0 !important;
        border-top: 0 !important;
    }

    input {
        border-right: 0 !important;
        border-left: 0 !important;
        border-top: 0 !important;
    }

    body {
        background-color: #fff9c4;
        font-family: Arial, sans-serif;
    }

    .header {
        background-color: #212121;
        padding: 35px;
        text-align: center;
        margin: 8px 0;
        border-radius: 8px;
    }

    .logo {
        width: 100px;
        height: 100px;
    }

    .form-container {
        max-width: 650px;
        margin: 0 auto;
    }

    .form-section {
        background-color: white;
        border-radius: 8px;
        padding: 17px;
        margin-bottom: 10px;
    }

    .form-title {
        font-weight: bold;
        margin-bottom: 5px;
        display: flex;
    }

    .required:after {
        content: " *";
        color: red;
    }

    .yellow-divider {
        height: 10px;
        /* background-color: #ffd600; */
        /* margin: 0; */
        background-color: rgb(221, 200, 0);
        color: rgba(0, 0, 0, 1);
        border-radius: 6px 6px 0px 0px;
    }

    .form-footer {
        font-size: 12px;
        color: #666;
        text-align: center;
        margin-top: 20px;
    }

    .next-button {
        background-color: #4285f4;
        color: white;
    }

    .clear-button {
        color: #4285f4;
        background-color: transparent;
        border: none;
    }

    .title {
        color: rgb(32, 33, 36);
    }

    .page-indicator {
        color: #666;
        font-size: 14px;
    }

    .form-section p {
        font-weight: 400;
        font-size: 16px;
    }

    .form-check-input[type=radio] {
        border-radius: 50%;
        width: 16px !important;
        height: 16px !important;
    }

    .form-check,
    .form-check-input,
    .form-check-label {
        margin-top: 3px !important;

    }

    .form-check-input.custom-radio-black {
        border: 2px solid #0000004d !important;
        width: 1.2em;
        height: 1.2em;
        appearance: none;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
    }

    .form-check-input.custom-radio-black:checked::before {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 8px;
        height: 8px;

        border-radius: 50%;
    }
</style>


<body>
    <form action="{{ route('application.form.incident.submit') }}" method="post" enctype="multipart/form-data"
        id="dynamicformsubmit" autocomplete="off">
        @csrf

        <div class="form-container">
            <div class="header">
                <img src="https://documents.voags.com/backend/websitedata/1742913121-sp-removebg-preview.png"
                    alt="Logo" class="logo">
            </div>
            <div class="yellow-divider"></div>

            <div class="form-section">
                <h1 class="title fw-bold">Accident, Incident, First Aid and Safeguarding, Reporting Form</h1>
                <p></p>
                <p style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">The
                    above also includes any occasions where a near miss is noted, so that measures can be put in place
                    in future to mitigate the risk of an actual occurrence happening.</p>
                <p style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                    <strong>NB</strong>&nbsp;All reports should be completed within the first&nbsp;<span
                        style="text-decoration-line: underline;">24 hours</span>&nbsp;following the accident/incident
                    taking place.
                </p>
                <p></p>
            </div>


            <div class="form-page" id="page-1" style="">

                <div class="form-section" style="padding:0">
                    <div class="card-header" style="background-color: rgb(223, 199, 0); padding: 13px 18px 4px 8px">
                        <h3 style="font-weight: 600;text-decoration: underline;font-size:15px">Name of person completing
                            the report</h3>
                    </div>

                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>First Name</p>
                    </label>


                    <input type="text" class="form-control" name="first_name" placeholder="">
                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>Last Name</p>
                    </label>


                    <input type="text" class="form-control" name="last_name" placeholder="">
                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>Job Title</p>
                    </label>


                    <input type="text" class="form-control" name="job_title" placeholder="">
                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>Email address of person completing the report</p>
                    </label>


                    <input type="email" class="form-control" name="email_address" placeholder="">
                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>Phone number of person completing the report</p>
                    </label>


                    <input type="text" class="form-control" name="phone_number" placeholder="">
                </div>

                <div class="form-section">
                    <label class="form-title " style="display:block;">
                        <p></p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                            <span style="font-size: 14pt;"><strong>Definitions</strong></span>
                        </p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px; ">
                            <strong>Accident:</strong>&nbsp;An unexpected event which results in serious injury or
                            illness of an employee and may also result in property damage.
                        </p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                            <strong>Incident (incl. Security incidents):</strong>&nbsp;An instance of something
                            happening, an unexpected event or occurrence that doesn’t result in serious injury or
                            illness but may result in property damage.
                        </p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                            <strong>Near Miss:</strong>&nbsp;An event not causing harm, but has the potential to cause
                            injury or ill health.
                        </p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                            <strong>InfoSec&nbsp;Incident:&nbsp;</strong>A single or a series of unwanted or unexpected
                            information security events that have a significant probability of compromising business
                            operations and threatening information security.&nbsp;<span
                                style="color: rgb(234, 50, 35);">DO NOT SELECT THIS FOR EVENT SECURITY
                                INCIDENTS.&nbsp;</span>
                        </p>
                        <p
                            style="margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;">
                            <strong>General:</strong>&nbsp;Feedback
                        </p>
                        <p></p>
                    </label>


                </div>

                <div class="form-section">
                    <label class="form-title  required ">
                        <p>I am reporting</p>
                    </label>


                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom-radio-black" type="radio" name="reportingType"
                            value="An Accident" id="flexCheckDefault5814">
                        <label class="form-check-label" for="flexCheckDefault5814">An Accident</label>
                    </div>


                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom-radio-black" type="radio" name="reportingType"
                            value="An Incident (incl. Security Incidents)" id="flexCheckDefault5815">
                        <label class="form-check-label" for="flexCheckDefault5815">An Incident (incl. Security
                            Incidents)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom-radio-black" type="radio" name="reportingType"
                            value="A Near Miss" id="flexCheckDefault5816">
                        <label class="form-check-label" for="flexCheckDefault5816">A Near Miss</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom-radio-black" type="radio" name="reportingType"
                            value="An InfoSec Incident" id="flexCheckDefault5817">
                        <label class="form-check-label" for="flexCheckDefault5817">An InfoSec Incident</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom-radio-black" type="radio" name="reportingType"
                            value="General Issue (LS Events Office use only)" id="flexCheckDefault5818">
                        <label class="form-check-label" for="flexCheckDefault5818">General Issue (LS Events Office use
                            only)</label>
                    </div>
                </div>
            </div>

            <div class="card" id="main-card" style="display:none">
                <div class="card-body">
                    <!-- Hidden Section (Initially Hidden) -->
                    <div id="incidentDetails" style="display:none; margin-top:15px;">

                        <!-- Project or Location -->
                        <label for="input_11">Select Project or Location</label>
                        <select class="form-dropdown form-control" id="input_11" name="project_location"
                            style="width:310px">
                            <option value="">Please Select</option>
                            <option value="All Points East">All Points East</option>
                            <option value="Barcode Festival">Barcode Festival</option>
                            <option value="Between the Bridges">Between the Bridges</option>
                            <option value="BST Hyde Park">BST Hyde Park</option>
                            <option value="Canada Day">Canada Day</option>
                            <option value="Diwali">Diwali</option>
                            <option value="Formula E">Formula E</option>
                            <option value="Japan Matsuri">Japan Matsuri</option>
                            <option value="Pride London">Pride London</option>
                            <option value="Office">Office</option>
                            <option value="Other">Other</option>
                        </select>

                        <div id="otherLocationSection" style="display:none; margin-top:10px;">
                            <label for="other_location" class="form-label mb-1">Please specify location</label>
                            <input type="text" id="other_location" name="location" class="form-control"
                                style="max-width:320px;" placeholder="Type location…">
                        </div>


                        <!-- Date and Time Inputs -->
                        <div style="margin-top:10px;">
                            <label for="date_occurrence">Date of Occurrence</label>
                            <input type="date" id="date_occurrence" name="date_occurrence" class="form-control"
                                style="width:200px;">

                            <label for="time_occurrence" style="margin-left:10px;">Time of Occurrence</label>
                            <input type="time" id="time_occurrence" name="time_occurrence"
                                class="form-control datetimepicker" style="width:150px; display:inline-block;">

                        </div>


                    </div>

                    <div style="display:none; margin-top:20px;" id="date_occurrence23">
                        <label for="date_occurrence2">Date of Occurrence</label>
                        <input type="date" id="date_occurrence2" name="date_occurrence2" class="form-control"
                            style="width:200px;">
                    </div>

                    <div style="display:none; margin-top:20px;" id="general_feedback_box">
                        <label for="general_feedback">General Feedback</label>
                        <textarea id="general_feedback" name="general_feedback" class="form-control" rows="4" style="width:400px;"></textarea>
                    </div>
                    <!-- Medical Section (Initially Hidden) -->
                    <div id="medicalSection" style="display:none; margin-top:20px;">
                        <h4>Medical</h4>

                        <label for="input_18" class="form-label">Was anyone involved injured?</label>
                        <select class="form-dropdown form-control" id="input_18" name="wasAnyone_injuired"
                            style="width:310px">
                            <option value="">Please Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div id="damageSection" style="display:none; margin-top:20px;">
                        <h5>Damage to Property</h5>
                        <p class="text-muted">
                            Physical damage to permanent infrastructure, facilities or wildlife.
                            These items include all Trees, Plants, Lamps, Drainage Systems, Benches, Gates, Roadways
                            etc.
                        </p>
                        <label for="damage_select" class="form-label">Was there any damage to property?</label>
                        <select class="form-select" id="damage_select" name="damage_property"
                            style="max-width:310px;">
                            <option value="">Please Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>


                        <!-- Shown when "Yes" -->
                        <div id="damageDetails" style="display:none; margin-top:15px;">
                            <div class="mb-3">
                                <label for="damage_caused" class="form-label">What damage was caused?</label>
                                <input type="text" id="damage_caused" name="damage_caused" class="form-control"
                                    placeholder="Describe the physical damage…" style="max-width:600px;">
                            </div>

                            <div class="mb-3">
                                <label for="damage_cause" class="form-label">What was the cause of the damage?</label>
                                <input type="text" id="damage_cause" name="damage_cause2" class="form-control"
                                    placeholder="E.g. storm, vehicle impact, vandalism…" style="max-width:600px;">
                            </div>
                        </div>


                        <!-- Reporting area: either select (for Yes) or input (for No) -->
                        <div id="damageReportWrap" style="display:none; margin-top:10px;">

                            <!-- Reporting select (shown when damage_select === 'Yes') -->
                            <div id="damageReportSelectWrap" style="display:none;">
                                <label for="damage_report_to" class="form-label">
                                    Does this damage need to be reported to the client, supplier, landowner or Other?
                                </label>
                                <select class="form-select" id="damage_report_to" name="damage_report_to"
                                    style="max-width:420px;">
                                    <option value="">Please Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>

                                <!-- If Other chosen inside the select -->
                                <div id="damage_report_other_wrap" style="display:none; margin-top:10px;">
                                    <label for="damage_report_other" class="form-label">Please specify who</label>
                                    <input type="text" id="damage_report_other" name="damage_report_other"
                                        class="form-control" style="max-width:600px;">
                                </div>
                            </div>

                            <!-- Reporting input (shown when damage_select === 'No') -->
                            <div id="damageReportInputWrap" style="display:none;">
                                <label for="damage_report_input" class="form-label">
                                    If this damage does not apply, you may add any comment or person who should still be
                                    informed:
                                </label>
                                <input type="text" id="damage_report_input" name="damage_report_input"
                                    class="form-control" style="max-width:600px;" placeholder="Enter details...">
                            </div>

                        </div>

                        <!-- If Other chosen -->
                        <div id="damage_report_other_wrap" style="display:none; margin-top:10px;">
                            <label for="damage_report_other" class="form-label">Name of
                                client/supplier/landowner/other to be informed</label>
                            <input type="text" id="damage_report_other" name="name_of_informed"
                                class="form-control" placeholder="Specify other (name/contact)..."
                                style="max-width:600px;">
                        </div>

                        <div id="report_name_group" style="display:none; margin-top:10px;">
                            <label for="damage_report_name" class="form-label">Name of client/supplier/landowner/other
                                to be informed</label>
                            <input type="text" id="damage_report_name" name="name_of_formed2"
                                class="form-control" style="max-width:600px;" placeholder="Enter name/contact...">
                        </div>

                    </div>



                    <!-- Hazardous Substances section (shown when damage_select === 'No') -->
                    <div id="hazardousSection" style="display:none; margin-top:20px;">
                        <h5>Hazardous Substances</h5>
                        <label for="hazardous_select" class="form-label">Were any hazardous substances
                            involved?</label>
                        <select class="form-select" id="hazardous_select" name="hazardous_involved"
                            style="max-width:310px;">
                            <option value="">Please Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>

                        <!-- Shown when hazardous_select === 'Yes' -->
                        <div id="hazardousDetails" style="display:none; margin-top:12px;">
                            <div class="mb-3">
                                <label for="hazard_substance" class="form-label">What substance was involved?</label>
                                <input type="text" id="hazard_substance" name="hazard_substance"
                                    class="form-control" style="max-width:600px;" placeholder="Name of substance">
                            </div>

                            <div class="mb-3">
                                <label for="hazard_actions" class="form-label">What actions were taken to contain the
                                    substance?</label>
                                <textarea id="hazard_actions" name="hazard_actions" class="form-control" rows="4"
                                    placeholder="Describe containment actions, cleanup, who attended…" style="max-width:800px;"></textarea>
                            </div>
                        </div>
                    </div>



                    <!-- If YES -->
                    <div id="hazardousYesWrap" style="display:none; margin-top:15px;">
                        <div class="mb-3">
                            <label class="form-label">What substance was involved?</label>
                            <input type="text" class="form-control" id="substance_involved"
                                name="substance_involved" style="max-width:400px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">What actions were taken to contain the substance?</label>
                            <textarea class="form-control" id="substance_actions" name="substance_actions" rows="3"
                                style="max-width:600px;"></textarea>
                        </div>
                    </div>



                    <!-- Date + Time Section (hidden by default) -->
                    <div style="display:none; margin-top:20px;" id="date_time_section">
                        <label for="date_occurrence_ino">Date of Occurrence</label>
                        <input type="date" id="date_occurrence_ino" name="date_occurrence3" class="form-control"
                            style="width:200px;">

                        <label for="time_occurrence_info" style="margin-top:10px;">Time of Occurrence</label>
                        <input type="time" id="time_occurrence_info" name="time_occurrence3"
                            class="form-control datetimepicker" style="width:200px;">
                    </div>

                    <!-- Information Security Section (hidden by default) -->
                    <div style="display:none; margin-top:20px;" id="info_security_section">
                        <h4>Information Security</h4>

                        <label for="incident_type_select">Was this an actual or potential incident?</label>
                        <select id="incident_type_select" name="incident_type_select" class="form-control"
                            style="width:250px;">
                            <option value="">-- Select --</option>
                            <option value="Actual">Actual</option>
                            <option value="Potential">Potential</option>
                        </select>
                    </div>

                    <div id="personalDataSection" style="display:none;">
                        <label>Did this incident involve personal data?</label>
                        <select id="personalDataSelect" class="form-control" name="incident_persona_data">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div id="affectedPartiesSection" style="display:none;">
                        <label>Have all affected parties been informed?</label>
                        <select id="affectedPartiesSelect" class="form-control" name="parties_informed">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="unknow">unknow</option>
                        </select>
                    </div>


                    <!-- If NO -->
                    <div id="hazardousNoWrap" style="display:none; margin-top:20px;">
                        <h5>Details of the Event</h5>

                        <div class="mb-3">
                            <label class="form-label">Who was directly involved?</label>
                            <input type="text" class="form-control" id="event_involved" name="event_involved"
                                style="max-width:400px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Please provide details of the event you are reporting. Give as
                                much detail as possible.</label>
                            <textarea class="form-control" id="event_details" name="event_details" rows="3" style="max-width:600px;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">What were your actions when responding to the
                                accident/incident?</label>
                            <textarea class="form-control" id="event_actions" name="event_actions" rows="3" style="max-width:600px;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">What was the outcome? What action was planned, implemented or
                                recommended?</label>
                            <textarea class="form-control" id="event_outcome" name="event_outcome" rows="3" style="max-width:600px;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Were there any witnesses to the event? Please provide names and
                                contact details.</label>
                            <textarea class="form-control" id="event_witnesses" name="event_witnesses" rows="3" style="max-width:600px;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Please attach any photos you may have to accompany your
                                report</label>
                            <input type="file" class="form-control" id="event_photos" name="event_photos[]"
                                multiple style="max-width:400px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resulting or Remedial Actions</label>
                            <textarea class="form-control" id="event_remedial" name="event_remedial" rows="3" style="max-width:600px;"></textarea>
                        </div>

                        <h5>Reporting and RIDDOR</h5>
                        <div class="mb-3">
                            <label class="form-label">Is the site/project reportable to the HSE under CDM Regs?</label>
                            <select class="form-select" id="event_riddor" name="event_riddor"
                                style="max-width:310px;">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>


                    <div id="damageDetails" style="display:none; margin-top:15px;">
                        <div class="mb-3">
                            <label for="damage_caused" class="form-label">What damage was caused?</label>
                            <input type="text" id="damage_caused" name="damage_caused2" class="form-control"
                                style="max-width:600px;">
                        </div>
                        <div class="mb-3">
                            <label for="damage_cause" class="form-label">What was the cause of the damage?</label>
                            <input type="text" id="damage_cause" name="damage_cause3" class="form-control"
                                style="max-width:600px;">
                        </div>
                    </div>



                    <div id="casualtySection" style="display:none; margin-top:20px;">

                        <h5>Casualty Details</h5>

                        <!-- Name -->
                        <div class="mb-3">
                            <label class="form-label">Name of casualty</label>
                            <div class="row">
                                <div class="col">
                                    <input type="text" class="form-control" placeholder="First Name"
                                        name="casuality_name">
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" placeholder="Last Name"
                                        name="casuality_last_name">
                                </div>
                            </div>
                        </div>

                        <!-- Casualty Type -->
                        <div class="mb-3">
                            <label class="form-label">Is the casualty:</label>
                            <select class="form-select" style="max-width:310px;" name="is_casuality">
                                <option value="">Please Select</option>
                                <option>LSE Employee</option>
                                <option>Contract Worker</option>
                                <option>Visitor</option>
                                <option>Audience Member</option>
                                <option>Guest</option>
                                <option>Member of the Public</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <!-- DOB -->
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control datetimepicker" style="max-width:310px;"
                                name="date_of_birth">
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control mb-2" placeholder="Street Address"
                                name="street_address">
                            <input type="text" class="form-control mb-2" placeholder="Street Address Line 2"
                                name="address_line_2">
                            <input type="text" class="form-control mb-2" placeholder="City" name="city">
                            <input type="text" class="form-control mb-2" placeholder="State / Province"
                                name="state_province">
                            <input type="text" class="form-control mb-2" placeholder="Postal / Zip Code"
                                name="zip">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email address of casualty</label>
                            <input type="email" class="form-control" placeholder="example@example.com"
                                style="max-width:310px;" name="email_address_casuality">
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label">Phone number of casualty</label>
                            <input type="tel" class="form-control" placeholder="00000 000 000"
                                style="max-width:310px;" name="phone_number_casuality">
                        </div>

                        <!-- Medical Required -->
                        <div class="mb-3">
                            <label for="input_23" class="form-label">Did the injury require medical?</label>
                            <select class="form-select" id="input_23" name="didThe_inquiry_medical"
                                style="max-width:310px;">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>


                        <div id="medicalActionsSection" style="display:none; margin-top:10px;">
                            <div class="mb-3">
                                <label for="actions_taken" class="form-label">Please detail the actions taken</label>
                                <textarea id="actions_taken" name="actions_taken" class="form-control" rows="4"
                                    placeholder="Describe first aid, who attended, medication, follow-up…"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="input_hosp" class="form-label">Did the injury require
                                    hospitalisation?</label>
                                <select class="form-select" id="input_hosp" name="hospitalisation"
                                    style="max-width:310px;">
                                    <option value="">Please Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Hospital details (shown when hospitalisation === 'Yes') -->
                        <div id="hospDetailsSection" style="display:none; margin-top:10px;">
                            <div class="mb-3">
                                <label for="hosp_details" class="form-label">
                                    Please provide any further details about the sustained illness or injury, including
                                    the name of the hospital (if known) if hospitalisation was required.
                                </label>
                                <textarea id="hosp_details" name="hosp_details" class="form-control" rows="4"
                                    placeholder="Hospital name, admission date, treatment given…"></textarea>
                            </div>
                        </div>

                        <!-- Cause input (shown when hospitalisation === 'No') -->
                        <div id="injuryCauseSection" style="display:none; margin-top:10px;">
                            <div class="mb-3">
                                <label for="injury_cause" class="form-label">What was the cause of the injury?</label>
                                <input type="text" id="injury_cause" name="injury_cause_4" class="form-control"
                                    placeholder="Describe cause of injury…">
                            </div>
                        </div>

                        <div id="injury_no_wrap" style="display:none;">
                            <div class="mb-3">
                                <label for="injury_cause" class="form-label">What was the cause of the injury?</label>
                                <input type="text" class="form-control" id="injury_cause" name="injury_cause5">
                            </div>
                        </div>


                        <!-- Actions + Hospitalisation (hidden by default) -->
                        <div id="medicalActionsSection" style="display:none;">
                            <div class="mb-3">
                                <label for="actions_taken" class="form-label">Please detail the actions taken</label>
                                <textarea id="actions_taken" name="actions_taken_2" class="form-control" rows="4"
                                    placeholder="Describe first aid, who attended, medications, follow-up steps…"></textarea>
                            </div>

                            <!-- Hospitalisation -->
                            <div class="mb-3">
                                <label for="input_hosp" class="form-label">Did the injury require
                                    hospitalisation?</label>
                                <select class="form-select" id="input_hosp" name="hospitalisation_2"
                                    style="max-width:310px;">
                                    <option value="">Please Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>

                            <!-- Further details (hidden initially) -->
                            <div id="hospDetailsSection" style="display:none;">
                                <div class="mb-3">
                                    <label for="hosp_details" class="form-label">
                                        Please provide any further details about the sustained illness or injury,
                                        including the name of the hospital (if known) if hospitalisation was required.
                                    </label>
                                    <textarea id="hosp_details" name="hosp_details_2" class="form-control" rows="4"
                                        placeholder="Add details about injury/illness, hospital name, dates, treatment…"></textarea>
                                </div>
                            </div>


                            <div id="injuryCauseSection" style="display:none;">
                                <div class="mb-3">
                                    <label for="injury_cause" class="form-label">What was the cause of the
                                        injury?</label>
                                    <input type="text" id="injury_cause" name="injury_cause_6"
                                        class="form-control" placeholder="Describe cause of injury…">
                                </div>
                            </div>



                        </div>

                    </div>


                    <div id="riddor_extra" style="display:none; margin-top:15px;">

                        <div class="mb-3" id="f10_numberdiv">
                            <label class="form-label">Please provide F10 notification number</label>
                            <input type="text" class="form-control" id="f10_number" name="notification_number"
                                style="max-width:310px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Has the Site Manager been informed?</label>
                            <select class="form-select" id="site_manager_informed" name="site_manager_informed"
                                style="max-width:310px;">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Has the Employer been informed?</label>
                            <select class="form-select" id="employer_informed" name="employer_informed"
                                style="max-width:310px;">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Is this accident reportable under RIDDOR?</label>
                            <select class="form-select" id="accident_riddor" name="accident_riddor"
                                style="max-width:310px;">
                                <option value="">Please Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="Not Sure">Not Sure</option>
                            </select>
                        </div>

                    </div>







                </div>
            </div>
            <div class="form-navigation text-center mt-3">
                <button type="submit" class="btn btn-success" id="submitBtn" disabled>Submit</button>
            </div>
        </div>
    </form>
    @include('docs.partial.script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- put this after jQuery is loaded and after your form -->
    <script>
        $(function() {
            const $form = $('#dynamicformsubmit');
            const $submitBtn = $form.find('#submitBtn');

            // Utility: check if element is visible to user (not only :visible, but has visible ancestor)
            function isTrulyVisible($el) {
                return $el.is(':visible') && $el.css('visibility') !== 'hidden' && $el.css('display') !== 'none';
            }

            // Validate radio groups: return true if every visible radio-name-group has one checked among visible radios
            function validateVisibleRadioGroups() {
                const radioNames = {};
                // find all visible radio inputs that have a name
                $form.find('input[type="radio"][name]').each(function() {
                    const $r = $(this);
                    if (!isTrulyVisible($r)) return;
                    radioNames[$r.attr('name')] = true;
                });

                for (const name in radioNames) {
                    // among radios with this name, consider only those that are visible
                    const $visibleGroup = $form.find('input[type="radio"][name="' + name + '"]').filter(function() {
                        return isTrulyVisible($(this));
                    });
                    // if there are visible radios, require at least one checked
                    if ($visibleGroup.length > 0) {
                        const anyChecked = $visibleGroup.is(':checked');
                        if (!anyChecked) return false;
                    }
                }
                return true;
            }

            // Validate all visible simple controls: input (not radio), textarea, select
            function validateVisibleControls() {
                let ok = true;
                // inputs except radio
                $form.find('input[name]').filter(function() {
                    return $(this).attr('type') !== 'radio';
                }).each(function() {
                    const $el = $(this);
                    if (!isTrulyVisible($el) || $el.prop('disabled')) return;
                    // For file inputs, if visible require at least one file if input has attribute data-required-if-visible="1"
                    if ($el.attr('type') === 'file') {
                        // if file visible and has files required (we treat all visible file inputs as optional unless they have special data attr)
                        if ($el.data('required-if-visible') === 1) {
                            if ($el[0].files.length === 0) {
                                ok = false;
                                return false;
                            }
                        }
                        return; // skip optional file inputs
                    }
                    // regular input: require not empty
                    const val = $el.val();
                    if (val === null) {
                        ok = false;
                        return false;
                    }
                    if (String(val).trim() === '') {
                        ok = false;
                        return false;
                    }
                });
                if (!ok) return false;

                // textareas
                $form.find('textarea[name]').each(function() {
                    const $el = $(this);
                    if (!isTrulyVisible($el) || $el.prop('disabled')) return;
                    const val = $el.val();
                    if (val === null || String(val).trim() === '') {
                        ok = false;
                        return false;
                    }
                });
                if (!ok) return false;

                // selects
                $form.find('select[name]').each(function() {
                    const $el = $(this);
                    if (!isTrulyVisible($el) || $el.prop('disabled')) return;
                    const val = $el.val();
                    // treat empty string or null as not selected
                    if (val === null || String(val).trim() === '') {
                        ok = false;
                        return false;
                    }
                });

                return ok;
            }

            // Full validation
            function validateForm() {
                // If there are no visible controls at all, keep disabled (safe)
                const visibleControls = $form.find('input[name], textarea[name], select[name]').filter(function() {
                    return isTrulyVisible($(this)) && !$(this).prop('disabled');
                });
                if (visibleControls.length === 0) {
                    $submitBtn.prop('disabled', true);
                    return false;
                }

                const controlsOk = validateVisibleControls();
                const radiosOk = validateVisibleRadioGroups();

                const result = controlsOk && radiosOk;
                $submitBtn.prop('disabled', !result);
                return result;
            }

            // Run validation on common events
            $form.on('input change keyup paste', 'input, textarea, select', function() {
                validateForm();
            });

            // Check radio clicks
            $form.on('click change', 'input[type="radio"]', function() {
                validateForm();
            });

            // Observe DOM mutations (style/display changes) to handle sections shown/hidden by other scripts
            const observer = new MutationObserver(function(mutations) {
                // cheap: just validate when something relevant changes
                let shouldCheck = false;
                mutations.forEach(m => {
                    // if attribute changed (like style, class), or childList changed
                    if (m.type === 'attributes' || m.type === 'childList' || m.type === 'subtree') {
                        shouldCheck = true;
                    }
                });
                if (shouldCheck) validateForm();
            });

            // Observe the form subtree for attribute changes and childList mutations
            observer.observe($form[0], {
                attributes: true,
                childList: true,
                subtree: true,
                attributeFilter: ['style', 'class', 'hidden']
            });

            // initial check on DOM ready
            validateForm();

            // AJAX submit handler
            $form.on('submit', function(e) {
                e.preventDefault();

                // final validation before sending
                if (!validateForm()) {
                    // optionally show a message
                    alert('Please complete all visible required fields before submitting.');
                    return;
                }

                // disable submit to prevent double submits
                $submitBtn.prop('disabled', true).text('Submitting...');

                const url = $form.attr('action');
                const method = ($form.attr('method') || 'POST').toUpperCase();

                // Prepare form data (with files)
                const formData = new FormData(this);

                // Add CSRF token header (Laravel) from hidden _token field
                const csrfToken = $form.find('input[name="_token"]').val() || $('meta[name="csrf-token"]')
                    .attr('content');

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Thank you for submitting this form!',
                            text: 'Click OK to submit another',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload(); // page reload
                            }
                        });
                    },

                    error: function(xhr, status, err) {
                        // handle error
                        let msg = 'Submission failed. ';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            msg += xhr.responseJSON.message;
                        } else if (xhr && xhr.responseText) {
                            msg += 'Server responded with: ' + xhr.status + ' ' + xhr
                            .statusText;
                        }
                        alert(msg);
                        $submitBtn.prop('disabled', false).text('Submit');
                    }
                });
            });

            // Optional: If you want any specific file input to be required when visible, add data-required-if-visible="1" to that input in markup.
            // Example in HTML: <input type="file" name="event_photos[]" data-required-if-visible="1" multiple>
        });
    </script>



    <script>
        function toggleOtherField(inputId, show) {
            let otherField = $('#' + inputId);
            if (show) {
                otherField.removeClass('d-none');
            } else {
                otherField.addClass('d-none').val(''); // Hide and clear input
            }
        }

        $(document).ready(function() {
            $('input[type="radio"]').on('change', function() {
                let otherFieldId = $(this).closest('.form-section').find('.other-input').attr('id');
                if ($(this).val() === 'other') {
                    toggleOtherField(otherFieldId, true);
                } else {
                    toggleOtherField(otherFieldId, false);
                }
            });
        });
    </script>


    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            $(".datetimepicker").each(function() {
                // prevent double initialization
                if (this._flatpickr) return;

                // create instance (no onClose calling instance.close())
                let picker = flatpickr(this, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    allowInput: true,
                    // optional: use onOpen / onReady if you need extra behavior
                    onOpen: function(selectedDates, dateStr, instance) {
                        // do something when opened (optional)
                    }
                    // DON'T call instance.close() inside onClose — it'll recursively trigger onClose again
                });

                // open button (ensure it's not triggering re-init)
                $(this).siblings(".open-picker").off('click.openPicker').on("click.openPicker", function(
                e) {
                    e.preventDefault();
                    // safe check before calling open
                    if (picker && typeof picker.open === 'function') picker.open();
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('input[type="file"]').on("change", function(event) {
                let fileInput = $(this)[0];
                let fileList = fileInput.files;
                let fileContainer = $(this).next(".file-preview-container");

                if (fileContainer.length === 0) {
                    fileContainer = $("<div class='file-preview-container'></div>");
                    $(this).after(fileContainer);
                } else {
                    fileContainer.html(""); // Purani files ka preview clear karna
                }

                $.each(fileList, function(index, file) {
                    let filePreview = $(`
                <div class="file-preview" data-index="${index}">
                    <span>${file.name}</span>
                    <button type="button" class="btn btn-sm remove-file" data-index="${index}"><i class="fas fa-times"></i></button>
                </div>
            `);
                    fileContainer.append(filePreview);
                });

                // Delete functionality
                fileContainer.off("click").on("click", ".remove-file", function() {
                    let index = $(this).data("index");
                    let dt = new DataTransfer();

                    $.each(fileInput.files, function(i, file) {
                        if (i != index) {
                            dt.items.add(file);
                        }
                    });

                    fileInput.files = dt.files; // Update file input
                    $(this).parent().remove(); // Sirf selected file remove karega
                });
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            // Format on blur (when user leaves input)
            $('.decimal-input').on('blur', function() {
                let val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    $(this).val(val.toFixed(2));
                }
            });

            // Also format all inputs before form submission
            $('form').on('submit', function() {
                $('.decimal-input').each(function() {
                    let val = parseFloat($(this).val());
                    if (!isNaN(val)) {
                        $(this).val(val.toFixed(2));
                    }
                });
            });
        });
    </script>

    <script>
        document.getElementById("flexCheckDefault5814").addEventListener("change", function() {
            let section = document.getElementById("incidentDetails");
            let section2 = document.getElementById("date_occurrence23");
            let hazardousNoWrap = document.getElementById("hazardousNoWrap");

            if (this.checked) {
                section.style.display = "block"; // show section
                section2.style.display = "none"; // show section
                hazardousNoWrap.style.display = "none"; // show section

            } else {
                section.style.display = "none"; // hide section if unchecked
                section2.style.display = "none"; // show section
                hazardousNoWrap.style.display = "none"; // show section
            }
        });
        document.getElementById("flexCheckDefault5815").addEventListener("change", function() {
            let section = document.getElementById("incidentDetails");
            let section2 = document.getElementById("date_occurrence23");
            let hazardousNoWrap = document.getElementById("hazardousNoWrap");
            if (this.checked) {
                section.style.display = "block"; // show section
                section2.style.display = "none"; // hide section if unchecked
                hazardousNoWrap.style.display = "none"; // show section        
            } else {
                section.style.display = "none"; // hide section if unchecked
                section2.style.display = "none"; // hide section if unchecked
                hazardousNoWrap.style.display = "none"; // show section

            }
        });

        document.getElementById("flexCheckDefault5816").addEventListener("change", function() {
            let section = document.getElementById("hazardousNoWrap");
            let section2 = document.getElementById("date_occurrence23");
            let medicalSection = document.getElementById("medicalSection");
            let incidentDetails = document.getElementById("incidentDetails");
            if (this.checked) {
                section.style.display = "none"; // show section
                section2.style.display = "none"; // hide section if unchecked
                medicalSection.style.display = "none"; // hide section if unchecked
                incidentDetails.style.display = "block"; // hide section if unchecked    
            } else {
                section.style.display = "none"; // hide section if unchecked
                section2.style.display = "none"; // hide section if unchecked
                medicalSection.style.display = "none"; // hide section if unchecked
                incidentDetails.style.display = "none"; // hide section if unchecked
            }
        });
        document.getElementById("flexCheckDefault5818").addEventListener("change", function() {
            let section2 = document.getElementById("date_occurrence23");
            let section = document.getElementById("incidentDetails");
            let hazardousNoWrap = document.getElementById("hazardousNoWrap");
            let date_time_section = document.getElementById("date_time_section");
            if (this.checked) {
                section2.style.display = "block"; // show section
                section.style.display = "none"; // show section
                hazardousNoWrap.style.display = "none"; // show section
                date_time_section.style.display = "none"; // show section

            } else {
                section2.style.display = "none"; // hide section if unchecked
                section.style.display = "none"; // hide section if unchecked
                hazardousNoWrap.style.display = "none"; // show section
                date_time_section.style.display = "none"; // show section
            }
        });
    </script>

    <script>
        document.getElementById("input_11").addEventListener("change", function() {
            let otherSection = document.getElementById("otherLocationSection");

            if (this.value !== "") {
                otherSection.style.display = "block"; // show input when any option selected
            } else {
                otherSection.style.display = "none"; // hide if reset to "Please Select"
            }
        });
    </script>

    <script>
        document.getElementById("time_occurrence").addEventListener("change", function() {
            let medicalSection = document.getElementById("medicalSection");
            let damageSection = document.getElementById("damageSection");
            let hazardousNoWrap = document.getElementById("hazardousNoWrap");

            let incidentRadio4 = document.getElementById("flexCheckDefault5814"); // An Accident
            let incidentRadio5 = document.getElementById("flexCheckDefault5815"); // An Incident...
            let incidentRadio6 = document.getElementById("flexCheckDefault5816"); // A Near Miss

            // Reset sabko hide karo pehle
            damageSection.style.display = "none";
            medicalSection.style.display = "none";
            hazardousNoWrap.style.display = "none";

            if (incidentRadio6.checked && incidentRadio6.value === "A Near Miss") {
                // ✅ A Near Miss -> hazardous
                hazardousNoWrap.style.display = "block";

            } else if (incidentRadio5.checked && incidentRadio5.value ===
                "An Incident (incl. Security Incidents)") {
                // ✅ An Incident -> medical
                medicalSection.style.display = "block";

            } else if (incidentRadio4.checked && incidentRadio4.value === "An Accident") {
                // ✅ An Accident -> medical
                medicalSection.style.display = "block";

            } else {
                // ✅ Agar koi select nahi hua to default damageSection
                damageSection.style.display = "block";
            }
        });
    </script>



    <script>
        document.getElementById("input_18").addEventListener("change", function() {
            let casualtySection = document.getElementById("casualtySection");
            let damageSection = document.getElementById("damageSection");
            if (this.value === "Yes") {
                casualtySection.style.display = "block"; // show details
                damageSection.style.display = "none"; // show details
            } else if (this.value === "No") {
                damageSection.style.display = "block"; // show details
                casualtySection.style.display = "none"; // show details
            } else {
                damageSection.style.display = "none"; // show details
                casualtySection.style.display = "none"; // show details
            }
        });
    </script>


    <script>
        (function() {
            // damage elements
            const damageSelect = document.getElementById('damage_select');
            const detailsWrap = document.getElementById('damageDetails');
            const causedField = document.getElementById('damage_caused');
            const causeField = document.getElementById('damage_cause');

            // report elements
            const reportWrap = document.getElementById('damageReportWrap');
            const reportSelectWrap = document.getElementById('damageReportSelectWrap');
            const reportSelect = document.getElementById('damage_report_to');
            const reportOtherWrap = document.getElementById('damage_report_other_wrap');
            const reportOtherField = document.getElementById('damage_report_other');
            const reportInputWrap = document.getElementById('damageReportInputWrap');
            const reportInputField = document.getElementById('damage_report_input');

            // hazardous elements
            const hazardousSection = document.getElementById('hazardousSection');
            const hazardousSelect = document.getElementById('hazardous_select');
            const hazardousDetails = document.getElementById('hazardousDetails');
            const hazardSubField = document.getElementById('hazard_substance');
            const hazardActions = document.getElementById('hazard_actions');

            function toggleDamageSections() {
                const val = damageSelect.value;
                if (val === 'Yes') {
                    // show damage details + reporting select
                    detailsWrap.style.display = 'block';
                    reportWrap.style.display = 'block';
                    reportSelectWrap.style.display = 'block';
                    reportInputWrap.style.display = 'none';
                    hazardousSection.style.display = 'none';

                    // required flags

                    causeField.required = false;
                    reportSelect.required = false;
                    reportInputField.required = false;

                    // reset hazardous fields
                    hazardousSelect.value = '';
                    hazardousDetails.style.display = 'none';
                    hazardSubField.value = '';
                    hazardActions.value = '';
                } else if (val === 'No') {
                    // hide damage details, show reporting input + hazardous section
                    detailsWrap.style.display = 'none';
                    reportWrap.style.display = 'none';
                    reportSelectWrap.style.display = 'none';
                    reportInputWrap.style.display = 'none';
                    hazardousSection.style.display = 'block';

                    // clear & un-required damage details
                    causedField.required = false;
                    causeField.required = false;
                    causedField.value = '';
                    causeField.value = '';

                    // make hazardous fields not required initially
                    hazardousSelect.required = false; // ask user whether hazardous involved
                } else {
                    // nothing selected -> hide everything
                    detailsWrap.style.display = 'none';
                    reportWrap.style.display = 'none';
                    reportSelectWrap.style.display = 'none';
                    reportInputWrap.style.display = 'none';
                    hazardousSection.style.display = 'none';

                    // reset requirements & values
                    causedField.required = false;
                    causeField.required = false;
                    reportSelect.required = false;
                    reportInputField.required = false;
                    hazardousSelect.required = false;

                    causedField.value = '';
                    causeField.value = '';
                    reportSelect.value = '';
                    reportOtherWrap.style.display = 'none';
                    reportOtherField.value = '';
                    reportInputField.value = '';

                    hazardousSelect.value = '';
                    hazardSubField.value = '';
                    hazardActions.value = '';
                    hazardousDetails.style.display = 'none';
                }
            }

            function toggleReportOther() {
                if (reportSelect && reportSelect.value === 'Other') {
                    reportOtherWrap.style.display = 'block';
                    reportOtherField.required = false;
                } else if (reportOtherWrap) {
                    reportOtherWrap.style.display = 'none';
                    reportOtherField.required = false;
                    reportOtherField.value = '';
                }
            }

            function toggleHazardousDetails() {
                const v = hazardousSelect.value;
                if (v === 'Yes') {
                    hazardousDetails.style.display = 'block';
                    hazardSubField.required = false;
                    hazardActions.required = false;
                } else if (v === 'No') {
                    hazardousDetails.style.display = 'none';
                    hazardSubField.required = false;
                    hazardActions.required = false;
                    hazardSubField.value = '';
                    hazardActions.value = '';
                } else {
                    hazardousDetails.style.display = 'none';
                    hazardSubField.required = false;
                    hazardActions.required = false;
                    hazardSubField.value = '';
                    hazardActions.value = '';
                }
            }

            // listeners
            if (damageSelect) damageSelect.addEventListener('change', toggleDamageSections);
            if (reportSelect) reportSelect.addEventListener('change', toggleReportOther);
            if (hazardousSelect) hazardousSelect.addEventListener('change', toggleHazardousDetails);

            // init (in case form is prefilled)
            toggleDamageSections();
            toggleReportOther();
            toggleHazardousDetails();
        })();
    </script>

    <script>
        (function() {
            const hazardousSelect = document.getElementById('hazardous_select');
            const yesWrap = document.getElementById('hazardousYesWrap');
            const noWrap = document.getElementById('hazardousNoWrap');
            const subInput = document.getElementById('substance_involved');
            const subActions = document.getElementById('substance_actions');

            function toggleHazardous() {
                const val = hazardousSelect.value;
                if (val === 'Yes') {
                    yesWrap.style.display = 'block';
                    noWrap.style.display = 'none';
                    subInput.required = false;
                    subActions.required = false;
                } else if (val === 'No') {
                    yesWrap.style.display = 'none';
                    noWrap.style.display = 'block';
                    subInput.required = false;
                    subActions.required = false;
                    subInput.value = '';
                    subActions.value = '';
                } else {
                    yesWrap.style.display = 'none';
                    noWrap.style.display = 'none';
                    subInput.required = false;
                    subActions.required = false;
                }
            }

            hazardousSelect.addEventListener('change', toggleHazardous);
            toggleHazardous(); // init
        })();
    </script>

    <script>
        (function() {
            const riddorSelect = document.getElementById('event_riddor');
            const riddorExtra = document.getElementById('riddor_extra');
            const f10Input = document.getElementById('f10_numberdiv');

            function toggleRiddorExtra() {
                if (riddorSelect.value === 'Yes') {
                    riddorExtra.style.display = 'block';
                    f10Input.style.display = 'block';
                } else if (riddorSelect.value === 'No') {
                    riddorExtra.style.display = 'block';
                    f10Input.style.display = 'none';
                } else {
                    riddorExtra.style.display = 'none';
                    f10Input.style.display = 'none';
                    // reset values if hidden
                    document.getElementById('f10_number').value = '';
                    document.getElementById('site_manager_informed').value = '';
                    document.getElementById('employer_informed').value = '';
                    document.getElementById('accident_riddor').value = '';
                }
            }

            riddorSelect.addEventListener('change', toggleRiddorExtra);
            toggleRiddorExtra(); // run on load in case prefilled
        })();
    </script>

    <script>
        (function() {
            const reportSelect = document.getElementById('damage_report_to');
            const reportNameGroup = document.getElementById('report_name_group');
            const reportNameInput = document.getElementById('damage_report_name');

            // Assuming hazardousSection exists on the page (from earlier code)
            const hazardousSection = document.getElementById('hazardousSection');

            function toggleReportName() {
                const val = reportSelect.value;

                if (val === 'Yes') {
                    // show name input, hide hazardous section
                    reportNameGroup.style.display = 'block';
                    if (hazardousSection) hazardousSection.style.display = 'none';

                    // set required if you want
                    reportNameInput.required = false;
                } else if (val === 'No') {
                    // hide name input, show hazardous section
                    reportNameGroup.style.display = 'none';
                    if (hazardousSection) hazardousSection.style.display = 'block';

                    // clear & remove required
                    reportNameInput.required = false;
                    reportNameInput.value = '';
                } else {
                    // nothing selected -> hide both
                    reportNameGroup.style.display = 'none';
                    if (hazardousSection) hazardousSection.style.display = 'none';
                    reportNameInput.required = false;
                    reportNameInput.value = '';
                }
            }

            // attach listener (guard in case select missing)
            if (reportSelect) {
                reportSelect.addEventListener('change', toggleReportName);
                // init
                toggleReportName();
            }
        })();
    </script>

    <script>
        (function() {
            const medSelect = document.getElementById('input_23');
            const actionsWrap = document.getElementById('medicalActionsSection');
            const actionsField = document.getElementById('actions_taken');

            const hospSelect = document.getElementById('input_hosp');
            const hospWrap = document.getElementById('hospDetailsSection');
            const hospField = document.getElementById('hosp_details');

            const causeWrap = document.getElementById('injuryCauseSection');
            const causeField = document.getElementById('injury_cause');

            const noWrap = document.getElementById('injury_no_wrap');

            const injurySelect = document.getElementById('input_23');
            const yesWrap = document.getElementById('injury_yes_wrap');
            const hospitalSelect = document.getElementById('injury_hospital');
            const hospitalWrap = document.getElementById('injury_hospital_wrap');


            function toggleMedicalActions() {
                if (!medSelect) return;
                if (medSelect.value === 'Yes') {
                    actionsWrap.style.display = 'block';
                    actionsField.required = false;

                    // reset hospital/cause to initial
                    hospSelect.value = '';
                    hospWrap.style.display = 'none';
                    hospField.required = false;
                    hospField.value = '';
                    noWrap.style.display = 'none';
                    causeWrap.style.display = 'none';
                    causeField.required = false;
                    causeField.value = '';
                } else if (medSelect.value === 'No') {
                    noWrap.style.display = 'block';
                    actionsWrap.style.display = 'none';
                } else {
                    actionsWrap.style.display = 'none';
                    actionsField.required = false;
                    actionsField.value = '';

                    // hide hospital & cause
                    hospWrap.style.display = 'none';
                    hospField.required = false;
                    hospField.value = '';

                    causeWrap.style.display = 'none';
                    causeField.required = false;
                    causeField.value = '';
                }
            }

            function toggleInjuryFields() {
                if (injurySelect.value === 'Yes') {
                    yesWrap.style.display = 'block';
                    noWrap.style.display = 'none';
                } else if (injurySelect.value === 'No') {
                    yesWrap.style.display = 'none';
                    hospitalWrap.style.display = 'none';
                    noWrap.style.display = 'block';
                } else {
                    yesWrap.style.display = 'none';
                    hospitalWrap.style.display = 'none';
                    noWrap.style.display = 'none';
                }
            }


            function toggleHospitalisation() {
                if (!hospSelect) return;
                if (hospSelect.value === 'Yes') {
                    hospWrap.style.display = 'block';
                    hospField.required = false;

                    // hide cause input
                    causeWrap.style.display = 'none';
                    causeField.required = false;
                    causeField.value = '';
                } else if (hospSelect.value === 'No') {
                    hospWrap.style.display = 'none';
                    hospField.required = false;
                    hospField.value = '';

                    causeWrap.style.display = 'block';
                    causeField.required = false;
                } else {
                    // empty
                    hospWrap.style.display = 'none';
                    hospField.required = false;
                    hospField.value = '';

                    causeWrap.style.display = 'none';
                    causeField.required = false;
                    causeField.value = '';
                }
            }

            // Attach listeners
            medSelect.addEventListener('change', toggleMedicalActions);
            hospSelect.addEventListener('change', toggleHospitalisation);

            // init (in case of pre-filled forms)
            toggleMedicalActions();
            toggleHospitalisation();
            toggleInjuryFields();
        })();
    </script>

    <script>
        $(function() {
            // list of radios jinko single select banana hai
            const $radios = $(
                '#flexCheckDefault5814, #flexCheckDefault5815, #flexCheckDefault5816, #flexCheckDefault5817, #flexCheckDefault5818'
            );

            $radios.on('change', function() {
                // sabko uncheck karo
                $radios.not(this).prop('checked', false);
            });
        });
        $(function() {
            // Jab date field change ho
            $("#date_occurrence2").on("change", function() {
                if ($(this).val()) {
                    $("#general_feedback_box").show(); // textarea show
                } else {
                    $("#general_feedback_box").hide(); // agar date remove kare to hide
                }
            });
        });
    </script>
    <script>
        $(function() {
            // Step 1: Show Date & Time inputs when InfoSec radio selected
            $("#flexCheckDefault5817").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#date_time_section").show();
                    $("#incidentDetails").hide();
                    $("#date_occurrence23").hide();
                } else {
                    $("#date_time_section").show();
                    $("#info_security_section").hide(); // hide info sec if radio deselected
                    $("#hazardousNoWrap").hide(); // hide hazardous section too
                }
            });

            // Step 2: When Time is filled AND InfoSec radio is checked, show Information Security section
            $("#time_occurrence_info").on("change", function() {
                // Check if InfoSec radio is checked
                if ($(this).val() && $("#flexCheckDefault5817").is(":checked")) {
                    $("#info_security_section").show();
                } else {
                    $("#info_security_section").hide();
                    // Only hide hazardous section if dropdown is not Potential
                    if ($("#incident_type_select").val() !== "Potential") {
                        $("#hazardousNoWrap").hide();
                    }
                }
            });

            // Step 3: Show #hazardousNoWrap when Potential is selected
            $("#incident_type_select").on("change", function() {
                if ($(this).val() === "Potential") {
                    $("#hazardousNoWrap").show();
                    $("#date_time_section").show(); // ensure date/time stays visible
                    $("#personalDataSection").hide();
                    $("#affectedPartiesSection").hide();
                } else if ($(this).val() === "Actual") {
                    $("#personalDataSection").show();
                    $("#date_time_section").hide(); // ensure date/time stays visible
                    $("#hazardousNoWrap").hide();

                } else {
                    $("#hazardousNoWrap").hide();
                }
            });
            $("#personalDataSelect").on("change", function() {
                if ($(this).val() === "Yes") {
                    $('#affectedPartiesSection').show(); // use # for id

                } else if ($(this).val() === "No") {
                    $('#hazardousNoWrap').show(); // use # for id
                    $('#affectedPartiesSection').hide(); // use # for id        
                } else {
                    $('#affectedPartiesSection').hide(); // hide if No or empty
                }
            });

            $("#affectedPartiesSelect").on("change", function() {
                if ($(this).val() === "Yes") {
                    $('#hazardousNoWrap').show(); // use # for id

                } else if ($(this).val() === "No") {
                    $('#hazardousNoWrap').show(); // use # for id

                } else if ($(this).val() === "unknow") {
                    $('#hazardousNoWrap').show(); // use # for id

                } else {
                    $('#hazardousNoWrap').hide(); // hide if No or empty
                }
            });

        });
    </script>
    <script>
        $(document).ready(function() {
            $('input[name="reportingType"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#main-card').show(); // card show
                }
            });
        });
    </script>

</body>

</html>
