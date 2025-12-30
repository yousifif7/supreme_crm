@extends('layouts.app')
@section('title', 'SPL Connect - Employee')

@section('styles')
    <style>
        @media print {

            .header,
            .sidebar {
                display: none !important;
            }

            .table th,
            .table td {
                padding: 5px;
            }
        }
    </style>
@endsection

@section('contents')
    <div id="detail-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Employee {{ $fore_name ?? null }} {{ $sur_name ?? null }} Detail</h2>
                </div>

            </div>
            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>Full Name</th>
                                    <td id="full_name_detail">{{ $fore_name ?? null }} {{ $sur_name ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td id="email_detail">{{ $email ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td id="gender_detail">{{ $gender ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Employment start date</th>
                                    <td id="employment_start_date">{{ $employment_start_date ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Employment end date</th>
                                    <td id="employment_end_date">{{ $employment_end_date ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>NI Number</th>
                                    <td id="ni_number_detail">{{ $ni_number ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>SIA Licence</th>
                                    <td id="sia_licence_detail">{{ $sia_licence ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>SIA Expiry</th>
                                    <td id="sia_expiry_detail">{{ $sia_expiry ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Licence Type</th>
                                    <td id="licence_type_detail">{{ $licence_type ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Entry Date</th>
                                    <td id="entry_date_detail">{{ $entry_date ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td id="dob_detail">{{ $dob ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Service Type</th>
                                    <td id="service_type_detail">{{ $service_type ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Visa Type</th>
                                    <td id="visa_type_detail">{{ $visa_type ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Visa Expiry</th>
                                    <td id="visa_expiry_detail">{{ $visa_expiry ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Place of Work</th>
                                    <td id="place_work_detail">{{ $place_work ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td id="contact_detail">{{ $contact ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Emergency Contact</th>
                                    <td id="emergency_contact_detail">{{ $emergency_contact ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Job Title</th>
                                    <td id="job_title_detail">{{ $job_title ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Nationality</th>
                                    <td id="nationality_detail">{{ $nationality ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Passport No</th>
                                    <td id="passport_no_detail">{{ $passport_no ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Passport Expiry</th>
                                    <td id="passport_expiry_detail">{{ $passport_expiry ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Address Group</th>
                                    <td id="address_group_detail">{{ $address_group ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Manager</th>
                                    <td id="manager_detail"></td>
                                </tr>
                                <tr>
                                    <th>Guard Rate</th>
                                    <td id="guard_rate_detail">{{ $guard_rate ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Info</th>
                                    <td id="bank_info_detail">{{ $bank_name ?? null }} / {{ $account_name ?? null }} /
                                        {{ $account_number ?? null }}</td>
                                </tr>
                                <tr>
                                    <th>Other Info</th>
                                    <td id="other_info_detail">{{ $other_info ?? null }}</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>


                </div>

            </div>
        </div>
    </div>
@endsection
@section('scripts')

    <script>
        $(document).ready(function() {
            window.print()
        });
    </script>


@endsection
