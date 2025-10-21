<?php $page="userlist";?>
@extends('layputs.app')
@section('contents')
@section('title') Incident Form View @endsection

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-wrapper">
    <div class="content">

<div class="card">
    <div class="card-body">
        <div class="row">
        @foreach([
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'job_title' => 'Job Title',
            'email_address' => 'Email Address',
            'phone_number' => 'Phone Number',
            'reportingType' => 'Reporting Type',
            'project_location' => 'Project / Location',
            'location' => 'Other Location',
            'date_occurrence' => 'Date of Occurrence',
            'time_occurrence' => 'Time of Occurrence',
            'date_occurrence2' => 'Date of Occurrence 2',
            'general_feedback' => 'General Feedback',
            'wasAnyone_injuired' => 'Was Anyone Injured?',
            'damage_property' => 'Damage Property',
            'damage_caused' => 'Damage Caused',
            'damage_cause2' => 'Damage Cause',
            'damage_report_to' => 'Damage Report To',
            'damage_report_other' => 'Damage Report Other',
            'damage_report_input' => 'Damage Report Input',
            'name_of_informed' => 'Name of Informed',
            'name_of_formed2' => 'Name of Formed2',
            'hazardous_involved' => 'Hazardous Involved',
            'hazard_substance' => 'Hazard Substance',
            'hazard_actions' => 'Hazard Actions',
            'substance_involved' => 'Substance Involved',
            'substance_actions' => 'Substance Actions',
            'date_occurrence3' => 'Date of Occurrence 3',
            'time_occurrence3' => 'Time of Occurrence 3',
            'incident_type_select' => 'Incident Type',
            'incident_persona_data' => 'Incident Personal Data',
            'parties_informed' => 'Parties Informed',
            'event_involved' => 'Event Involved',
            'event_details' => 'Event Details',
            'event_actions' => 'Event Actions',
            'event_outcome' => 'Event Outcome',
            'event_witnesses' => 'Event Witnesses',
            'event_remedial' => 'Event Remedial',
            'event_riddor' => 'Event RIDDOR',
            'damage_caused2' => 'Damage Caused 2',
            'damage_cause3' => 'Damage Cause 3',
            'casuality_name' => 'Casualty First Name',
            'casuality_last_name' => 'Casualty Last Name',
            'is_casuality' => 'Is Casualty',
            'date_of_birth' => 'Date of Birth',
            'street_address' => 'Street Address',
            'address_line_2' => 'Address Line 2',
            'city' => 'City',
            'state_province' => 'State / Province',
            'zip' => 'Postal / Zip',
            'email_address_casuality' => 'Email Address Casualty',
            'phone_number_casuality' => 'Phone Number Casualty',
            'didThe_inquiry_medical' => 'Did the Injury Require Medical?',
            'actions_taken' => 'Actions Taken',
            'hospitalisation' => 'Hospitalisation',
            'hosp_details' => 'Hospital Details',
            'injury_cause_4' => 'Injury Cause 4',
            'injury_cause5' => 'Injury Cause 5',
            'actions_taken_2' => 'Actions Taken 2',
            'hospitalisation_2' => 'Hospitalisation 2',
            'hosp_details_2' => 'Hospital Details 2',
            'injury_cause_6' => 'Injury Cause 6',
            'notification_number' => 'Notification Number',
            'site_manager_informed' => 'Site Manager Informed',
            'employer_informed' => 'Employer Informed',
            'accident_riddor' => 'Accident RIDDOR',
            'event_photos' => 'Event Photos'
        ] as $field => $label)
            @if(!empty($edit->$field))
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ $label }}</label>
                @if($field === 'event_photos')
                    <input type="file" name="event_photos[]" multiple class="form-control" readonly>
                @elseif(str_contains($field, 'date'))
                    <input type="date" name="{{ $field }}" value="{{ $edit->$field }}" class="form-control" readonly>
                @elseif(str_contains($field, 'time'))
                    <input type="time" name="{{ $field }}" value="{{ $edit->$field }}" class="form-control" readonly>
                @elseif(str_contains($field, 'actions') || str_contains($field, 'details') || str_contains($field, 'outcome') || str_contains($field, 'witnesses'))
                    <textarea name="{{ $field }}" class="form-control" rows="3" readonly>{{ $edit->$field }}</textarea>
                @else
                    <input type="text" name="{{ $field }}" value="{{ $edit->$field }}" class="form-control" readonly>
                @endif
            </div>
            @endif
        @endforeach
        </div>
    </div>
</div>

        <!-- /product list -->
    </div>
</div>

@endsection