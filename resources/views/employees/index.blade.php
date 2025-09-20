@extends('layouts.app')
@section('title', 'CRM - Employee')
@section('contents')
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Security Staff</h2>
                </div>

            </div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- show validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('employees.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('employees.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_employee"
                        class=" add_btn btn btn-white d-inline-flex align-items-center" id="openAddModal">
                        <i class="ti ti-plus me-2"></i>Staff
                    </a>


                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                        <!-- /Search -->
                    </div>
                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        {{ $dataTable->setTableHeadClass('thead-light')->table(['class' => 'table datatable table-striped table-hover']) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- /Page Wrapper -->
        <!-- Add Employee -->
        @include('employees.create')
        <!-- /Add Employee -->
        @include('employees.edit')

        <!-- Generate Employee Payroll -->
        <div class="modal fade" id="generate_payroll">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Generate Employee Payroll</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="generate_payroll-form">
                        @csrf
                        <input type="hidden" name="employee_id" id="payroll_employee_id">
                        <input type="hidden" name="type" value="security_staff">
                        <div class="tab-content" id="myTabContentPayroll">
                            <div class="tab-pane fade show active" id="payroll-basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Employee Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="employee_name"
                                                            id="payroll_employee_name" readonly style="background: #eee;"
                                                            class="form-control" placeholder="Enter Employee Name">
                                                        <span class="text-danger form-error"
                                                            id="payrollerror_employee_name"></span>
                                                    </div>


                                                    <div class="mb-3">
                                                        <label class="form-label">Employee Site <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="site_id" id="payroll_site_id">
                                                            <option value="">-- choose --</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="payrollerror_site_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date From: </label>
                                                                <input type="date" name="date_from"
                                                                    id="payroll_date_from" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="payrollerror_date_from"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date To: </label>
                                                                <input type="date" name="date_to" id="payroll_date_to"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="payrollerror_date_to"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Notes </label>
                                                        <textarea class="form-control" name="notes" id="payroll_notes" rows="3"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="payrollerror_notes"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="generate_payroll-form" id="generatepayroll"
                                        class="btn btn-primary">Generate </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Generate Employee Payroll-->

        <!-- Edit Employee -->

        <!-- /Edit Employee -->

        <!-- Add Employee Success -->
        <div class="modal fade" id="success_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center p-3">
                            <span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
                                    class="ti ti-check fs-24"></i></span>
                            <h5 class="mb-2" id="success_message"></h5>

                            </p>
                            <div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="{{ url('employees') }}" class="btn btn-dark w-100">Back to
                                            List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add employee Success -->

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                            <i class="ti ti-trash-x fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Delete</h4>
                        <p class="mb-3">This action cannot be undone. Are you sure you want to delete?</p>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Delete Modal -->
        <!-- Import modal -->
        <div class="modal fade" id="import_modal">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Import Employees</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-info">
                                                <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:
                                                </h6>
                                                <ul class="mb-0 small">
                                                    <li>Headers should be in Row 1 starting from Column A</li>
                                                    <li>Data should start from Row 2, Column A onwards</li>
                                                    <li><strong>Required:</strong> Full Name</li>
                                                    <li><strong>Optional:</strong> Date of Registration, Subcontractor, Pay
                                                        Rate, Contact, SIA Number, Service Type, SIA Expiry, DOB, Email,
                                                        Username, Address with Post Code, Address Group, Account Name, Sort
                                                        Code, Account Number, NI Number, Visa Status, Visa Expiry Date</li>
                                                    <li>Full Name will be split into First and Last name automatically</li>
                                                    <li>If Subcontractor name is provided but doesn't exist, a new
                                                        subcontractor will be created</li>
                                                    <li><strong>User Account Creation:</strong> If Username is provided, a
                                                        user account will be created with default password "password123".
                                                        Username must be a valid email address.</li>
                                                    <li>Date formats supported: "02-Aug-24", "02-Aug-2024", standard date
                                                        formats</li>
                                                    <li>Remaining SIA/VISA days are calculated automatically if expiry dates
                                                        are provided</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="import_file" class="form-control" required
                                                    accept=".xlsx,.xls,.csv">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('employees.export.excel', ['template' => 1]) }}"
                                                class="btn btn-outline-primary w-100">
                                                <i class="ti ti-download"></i> Download Template
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>

                                    <button class="btn btn-primary" type="submit">Import</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Logs Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Client Logs Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- View Employee Detail Modal -->
    <div class="modal fade" id="viewEmployeeDetailModal" tabindex="-1" aria-labelledby="employeeDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="employeeDetailLabel">
                        Employee <span id="employee_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Full Name</th>
                                <td id="full_name_detail"></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td id="email_detail"></td>
                            </tr>
                            {{-- <tr>
                                <th>Employment Start Date</th>
                                <td id="employment_start_date"></td>
                            </tr>
                            <tr>
                                <th>Employment Start Date</th>
                                <td id="employment_end_date"></td>
                            </tr> --}}
                            <tr>
                                <th>Gender</th>
                                <td id="gender_detail"></td>
                            </tr>
                            <tr>
                                <th>NI Number</th>
                                <td id="ni_number_detail"></td>
                            </tr>
                            <tr>
                                <th>SIA Licence</th>
                                <td id="sia_licence_detail"></td>
                            </tr>
                            <tr>
                                <th>SIA Expiry</th>
                                <td id="sia_expiry_detail"></td>
                            </tr>
                            <tr>
                                <th>Licence Type</th>
                                <td id="licence_type_detail"></td>
                            </tr>
                            <tr>
                                <th>Entry Date</th>
                                <td id="entry_date_detail"></td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td id="dob_detail"></td>
                            </tr>
                            <tr>
                                <th>Service Type</th>
                                <td id="service_type_detail"></td>
                            </tr>
                            <tr>
                                <th>Visa Type</th>
                                <td id="visa_type_detail"></td>
                            </tr>
                            <tr>
                                <th>Visa Expiry</th>
                                <td id="visa_expiry_detail"></td>
                            </tr>
                            <tr>
                                <th>Place of Work</th>
                                <td id="place_work_detail"></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td id="contact_detail"></td>
                            </tr>
                            <tr>
                                <th>Emergency Contact</th>
                                <td id="emergency_contact_detail"></td>
                            </tr>
                            <tr>
                                <th>Job Title</th>
                                <td id="job_title_detail"></td>
                            </tr>
                            <tr>
                                <th>Nationality</th>
                                <td id="nationality_detail"></td>
                            </tr>
                            <tr>
                                <th>Passport No</th>
                                <td id="passport_no_detail"></td>
                            </tr>
                            <tr>
                                <th>Passport Expiry</th>
                                <td id="passport_expiry_detail"></td>
                            </tr>
                            <tr>
                                <th>Driving Licence</th>
                                <td id="driving_licence_detail"></td>
                            </tr>
                            <tr>
                                <th>Driving Licence Expiry</th>
                                <td id="driving_licence_expiry_detail"></td>
                            </tr>
                            <tr>
                                <th>Address Group</th>
                                <td id="address_group_detail"></td>
                            </tr>
                            <tr>
                                <th>Manager</th>
                                <td id="manager_detail"></td>
                            </tr>
                            <tr>
                                <th>Guard Rate</th>
                                <td id="guard_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Bank Info</th>
                                <td id="bank_info_detail"></td>
                            </tr>
                            <tr>
                                <th>Other Info</th>
                                <td id="other_info_detail"></td>
                            </tr>
                            <tr>
                                <th>Documents</th>
                                <td id="document_list_detail">
                                    <span class="text-muted">Loading...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="container modal-body mt-4">
                    <h6>Weekly Availability</h6>
                    <table class="table table-bordered" id="availability_table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('scripts')
    <script>
        $('#openAddModal').on('click', function() {
            $('#add_worker-form1')[0].reset();
            // $('#add_employee-form')[0].reset();
            $('.form-error').text('');
        });
    </script>
    <script>
        $('.visa_type').on('change', function() {
            const form = $(this).closest('form');
            const showTerms = $(this).val() === 'Student';

            form.find('.terms-section, .terms-section-edit').each(function() {
                $(this).toggle(showTerms);
            });

            form.find('#term-rows, #editterm-rows').each(function() {
                $(this).empty();
            });
        });
        $(document).ready(function() {
            $('#add_worker-form1').on('submit', function(e) {
                e.preventDefault();

                $("[id^='error_']").addClass('d-none').text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveemployee'); // Add an ID to your submit button

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#add_employee');
                        toast_success('Employee Added Successfully');
                        reloadDatatable('#employees-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]).removeClass('d-none');
                            });

                            // scroll to the first error visible
                            $('#add_employee .modal-body').scrollTop(
                                $('#add_employee .form-error:visible').first().siblings(
                                    'label').offset().top - $('#add_employee .modal-body')
                                .offset().top + $('#add_employee .modal-body').scrollTop()
                            );
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
            $('#edit_employee_form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='editerror_']").text('').addClass('d-none');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editemployee'); // Your submit button should have this ID

                // Get the employee ID from a hidden input field
                let employeeId = $('#employee_id')
                    .val(); // Make sure you have <input type="hidden" id="employee_id" value="123">

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updateemployee/${employeeId}`, // OR use Laravel Blade: `{{ url('employees') }}/` + employeeId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_employee');
                        toast_success('Employee Updated Successfully');
                        reloadDatatable('#employees-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]).removeClass(
                                    'd-none');
                            });

                            // scroll to the first error visible
                            $('#edit_employee .modal-body').scrollTop(
                                $('#edit_employee .form-error:visible').first().siblings(
                                    'label').offset().top - $('#edit_employee .modal-body')
                                .offset().top + $('#edit_employee .modal-body').scrollTop()
                            );
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });
            $('#generate_payroll-form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='payrollerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#generatepayroll'); // Your submit button should have this ID

                // Get the employee ID from a hidden input field
                let employeeId = $('#payroll_employee_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/generatepayroll/${employeeId}`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#generate_payroll');
                        toast_success('Payroll Created Successfully!');
                        reloadDatatable('#employees-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#payrollerror_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Generate');
                    }
                });
            });
        });

        function generatePayroll(record_id) {
            $.get(`${baseUrl}/generatepayroll/` + record_id, function(data) {
                if (data.employee) {
                    $('#payroll_employee_id').val(record_id);
                    $.each(data.sites, function(index, item) {
                        $('#payroll_site_id').append(
                            $('<option>', {
                                value: item.site.id,
                                text: item.site.site_name
                            })
                        );
                    });
                    $('#payroll_employee_name').val(`${data.employee.fore_name} ${data.employee.sur_name}`);
                    $('#generate_payroll').modal('show');
                }
            });
        }

        var editholiday = 0;
        var editterm = 0;

        function editEmployee(record_id) {
            $.get(`${baseUrl}/editemployee/` + record_id, function(data) {
                if (data.employee) {
                    // Fill all the form fields (same as your code)
                    $('#employee_id').val(data.employee.id);
                    $('#username').val(data.employee.username);
                    $('#status').val(data.employee.status);
                    $('#fore_name').val(data.employee.fore_name);
                    $('#sur_name').val(data.employee.sur_name);
                    $('#gender').val(data.employee.gender);
                    $('#email').val(data.employee.email);
                    $('#ni_number').val(data.employee.ni_number);
                    $('#sia_licence').val(data.employee.sia_licence);
                    $('#sia_expiry').val(data.employee.sia_expiry);
                    $('#licence_type').val(data.employee.licence_type);
                    $('#entry_date').val(data.employee.entry_date);
                    $('#dob').val(data.employee.dob);
                    $('#service_type').val(data.employee.service_type);
                    $('#visa_type').val(data.employee.visa_type);
                    $('#visa_expiry').val(data.employee.visa_expiry);
                    $('#place_work').val(data.employee.place_work);
                    $('#hour_per_week').val(data.employee.hour_per_week);
                    $('#passport_no').val(data.employee.passport_no);
                    $('#passport_expiry').val(data.employee.passport_expiry);
                    $('#address_group').val(data.employee.address_group);
                    $('#contact').val(data.employee.contact);
                    $('#emergency_contact').val(data.employee.emergency_contact);
                    $('#job_title').val(data.employee.job_title);
                    $('#nationality').val(data.employee.nationality);
                    $('#pin').val(data.employee.pin);
                    $('#reference_to_emp').val(data.employee.reference_to_emp);
                    $('#next_kin').val(data.employee.next_kin);
                    $('#relation_with_kin').val(data.employee.relation_with_kin);
                    $('#kin_address').val(data.employee.kin_address);
                    $('#kin_number').val(data.employee.kin_number);
                    $('#kin_work_tel').val(data.employee.kin_work_tel);
                    $('#kin_mobile').val(data.employee.kin_mobile);
                    $('#share_code').val(data.employee.share_code);
                    $('#share_code_expiry').val(data.employee.share_code_expiry);
                    $('#biometric_residence_permit').val(data.employee.biometric_residence_permit);
                    $('#biometric_residence_permit_expiry').val(data.employee.biometric_residence_permit_expiry);
                    $('#brp_status1').val(data.employee.brp_status);
                    $('#settlement').val(data.employee.settlement);
                    $('#tags').val(data.employee.tags);
                    $('#department_id').val(data.employee.department_id);
                    $('#subcontractor').val(data.employee.subcontractor);
                    $('#additional_sia_number').val(data.employee.additional_sia_number);
                    $('#license_type').val(data.employee.license_type);
                    $('#license_expiry').val(data.employee.license_expiry);
                    $('#dbs_confirmed').val(data.employee.dbs_confirmed);
                    $('#license_number1').val(data.employee.license_number);
                    $('#address_group_additional').val(data.employee.address_group_additional);
                    $('#employee_type').val(data.employee.employee_type);
                    $('#visa_to_work_yes').prop('checked', data.employee.visa_to_work == 1);
                    $('#visa_to_work_no').prop('checked', data.employee.visa_to_work == 0);
                    $('#driving_license_yes').prop('checked', data.employee.driving_license == 1);
                    $('#driving_license_no').prop('checked', data.employee.driving_license == 0);
                    $('#vehicle_in_use_yes').prop('checked', data.employee.vehicle_in_use == 1);
                    $('#vehicle_in_use_no').prop('checked', data.employee.vehicle_in_use == 0);
                    $('#collar').val(data.employee.collar);
                    $('#waist').val(data.employee.waist);
                    $('#jacket').val(data.employee.jacket);
                    $('#shoe').val(data.employee.shoe);
                    $('#inseam').val(data.employee.inseam);
                    $('#guard_rate1').val(data.employee.guard_rate);
                    $('#payment_period').val(data.employee.payment_period);
                    $('#fixed_pay').val(data.employee.fixed_pay);
                    $('#account_name').val(data.employee.account_name);
                    $('#account_number').val(data.employee.account_number);
                    $('#sort_code').val(data.employee.sort_code);
                    $('#bank_name').val(data.employee.bank_name);
                    $('#bank_branch').val(data.employee.bank_branch);
                    $('#other_info').val(data.employee.other_info);
                    $('#current_endorsement').val(data.employee.current_endorsement);
                    $('#employment_start_date').val(data.employment_start_date);
                    $('#employment_end_date').val(data.employment_end_date);

                    // Handle Holidays
                    $('#editholiday-rows').empty();
                    if (data.holidays && data.holidays.length > 0) {
                        data.holidays.forEach((holiday) => {
                            editholiday++;
                            const holidayRow = `
                        <div class="row holiday-row mb-3 align-items-center" data-index="${editholiday}">
                            <div class="col-md-3">
                                <label>Holiday Entitlement</label>
                                <input type="text" name="holidays[${editholiday}][entitlement]" class="form-control" value="${holiday.holidays_entitement}">
                            </div>
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" name="holidays[${editholiday}][from]" class="form-control" value="${holiday.from_date}">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" name="holidays[${editholiday}][to]" class="form-control" value="${holiday.to_date}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm removeHolidayRow">Remove</button>
                            </div>
                        </div>`;
                            $('#editholiday-rows').append(holidayRow);
                        });
                    }

                    // Handle Terms if visa_type is Student
                    $('#editterm-rows').empty();
                    if (data.employee.visa_type == 'Student' && data.terms && data.terms.length > 0) {
                        $('.terms-section-edit').show();
                        data.terms.forEach((term) => {
                            editterm++;
                            const termRow = `
                        <div class="row term-row mb-3 align-items-center" data-index="${editterm}">
                            <div class="col-md-3">
                                <label>Term Name</label>
                                <input type="text" name="terms[${editterm}][term_name]" class="form-control" value="${term.term_name}">
                            </div>
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" name="terms[${editterm}][from]" class="form-control" value="${term.from_date}">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" name="terms[${editterm}][to]" class="form-control" value="${term.to_date}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm removeTermRow">Remove</button>
                            </div>
                        </div>`;
                            $('#editterm-rows').append(termRow);
                        });
                    }

                    // Handle Additional Documents
                    const additionalDocsContainer = $('#edit-additional-documents');
                    additionalDocsContainer.empty();

                    let additionalFiles = [];
                    if (Array.isArray(data.employee.additional_files)) {
                        additionalFiles = data.employee.additional_files;
                    } else if (typeof data.employee.additional_files === 'string') {
                        try {
                            additionalFiles = JSON.parse(data.employee.additional_files);
                            if (!Array.isArray(additionalFiles)) additionalFiles = [];
                        } catch (e) {
                            additionalFiles = [];
                        }
                    }

                    if (additionalFiles.length > 0) {
                        additionalFiles.forEach((fileName, index) => {
                            const url = `${baseUrl}/uploads/additional_docs/${fileName}`;
                            additionalDocsContainer.append(`
                        <div class="d-flex align-items-center mb-2" data-index="${index}">
                            <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary me-2">${fileName}</a>
                            <button type="button" class="btn btn-sm btn-danger remove-additional-file-btn">Remove</button>
                        </div>
                    `);
                        });
                    } else {
                        additionalDocsContainer.html(
                            '<span class="text-muted">No additional documents uploaded.</span>');
                    }

                    $('#edit_employee').modal('show');
                }
            });
        }


        let selectedId = null;

        function deleteEmployee(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteemployee/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Employee Deleted Successfully');
                        reloadDatatable('#employees-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });

        // Bulk delete button
        $('#bulkDeleteBtn').on('click', function() {
            const selected = $('.dT-row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                toast_danger('Please select at least one client to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected employee?')) return;

            $.ajax({
                url: '{{ route('employee.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected employees deleted successfully!');
                    reloadDatatable('#employees-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });
    </script>
    <script>
        let holidayIndex = 0;
        let termIndex = 0;
        let holidayIndex1 = 0;

        function addHolidayRow() {
            holidayIndex++;

            const holidayRow = `
                <div class="row holiday-row mb-3 align-items-center" data-index="${holidayIndex}">
                    <div class="col-md-3">
                        <label>Holiday Entitlement</label>
                        <input type="text" name="holidays[${holidayIndex}][entitlement]" class="form-control" placeholder="Entitlement">
                    </div>
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="holidays[${holidayIndex}][from]" class="form-control" placeholder="From Date">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="holidays[${holidayIndex}][to]" class="form-control" placeholder="To Date">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removeHolidayRow">Remove</button>
                    </div>
                </div>
            `;

            $('#holiday-rows').append(holidayRow);
        }

        function addTermRow() {
            termIndex++;

            const termRow = `
                <div class="row term-row mb-3 align-items-center" data-index="${termIndex}">
                    <div class="col-md-3">
                        <label>Term Name</label>
                        <input type="text" name="terms[${termIndex}][entitlement]" class="form-control" placeholder="Term Name">
                    </div>
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="terms[${termIndex}][from]" class="form-control" placeholder="From Date">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="terms[${termIndex}][to]" class="form-control" placeholder="To Date">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removeTermRow">Remove</button>
                    </div>
                </div>
            `;

            $('#term-rows').append(termRow);
        }

        // For Edit Employee
        function addEditHolidayRow() {
            editholiday++;
            const editholidayRow = `
            <div class="row holiday-row mb-3 align-items-center" data-index="${editholiday}">
                <div class="col-md-3"><label>Entitlement</label>
                    <input type="text" name="holidays[${editholiday}][entitlement]" class="form-control">
                </div>
                <div class="col-md-3"><label>From Date</label>
                    <input type="date" name="holidays[${editholiday}][from]" class="form-control">
                </div>
                <div class="col-md-3"><label>To Date</label>
                    <input type="date" name="holidays[${editholiday}][to]" class="form-control">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm removeHolidayRow">Remove</button>
                </div>
            </div>`;
            $('#editholiday-rows').append(editholidayRow);
        }

        function addEditTermRow() {
            editterm++;
            const edittermRow = `
            <div class="row term-row mb-3 align-items-center" data-index="${editterm}">
                <div class="col-md-3"><label>Term Name</label>
                    <input type="text" name="terms[${editterm}][term_name]" class="form-control">
                </div>
                <div class="col-md-3"><label>From Date</label>
                    <input type="date" name="terms[${editterm}][from]" class="form-control">
                </div>
                <div class="col-md-3"><label>To Date</label>
                    <input type="date" name="terms[${editterm}][to]" class="form-control">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm removeTermRow">Remove</button>
                </div>
            </div>`;
            $('#editterm-rows').append(edittermRow);
        }

        $(document).on('click', '#addHolidayRow', function() {
            addHolidayRow();
        });

        $(document).on('click', '#addTermRow', function() {
            addTermRow();
        });
        $(document).on('click', '#editHolidayRow', function() {
            addEditHolidayRow();
        });
        $(document).on('click', '#editTermRow', function() {
            addEditTermRow();
        });

        $(document).on('click', '.removeHolidayRow', function() {
            $(this).closest('.holiday-row').remove();
        });
        $(document).on('click', '.removeTermRow', function() {
            $(this).closest('.term-row').remove();
        });

        $(document).ready(function() {
            $('#isaCheck1').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#additional-license-section').slideDown();
                } else {
                    $('#additional-license-section').slideUp();
                }
            });
        });

        function viewLogs(employeeId) {
            // Clear existing content
            const modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            fetch(`${baseUrl}/employees/${employeeId}/logs/ajax`)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">No logs found for this client.</p>';
                    } else {
                        let html = '<table class="table table-bordered table-striped">';
                        html +=
                            '<thead><tr><th>User</th><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>';
                        data.logs.forEach(log => {
                            html += `<tr>
                                    <td>${log.user_name}</td>
                                    <td>${log.action}</td>
                                    <td>${log.description}</td>
                                    <td>${log.time}</td>
                                </tr>`;
                        });
                        html += '</tbody></table>';
                        modalBody.innerHTML = html;
                    }

                    // Show the modal
                    $('#logModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                    modalBody.innerHTML = '<p class="text-danger">Error loading logs.</p>';
                });
        }

        function viewEmployeeDetail(id) {
            $.get(`${baseUrl}/employees/${id}/view`, function(data) {
                $('#employee_name_heading').text(`${data.fore_name} ${data.sur_name}`);
                $('#full_name_detail').text(`${data.fore_name} ${data.sur_name}`);
                $('#email_detail').text(data.email);
                $('#gender_detail').text(data.gender);
                $('#ni_number_detail').text(data.ni_number);
                $('#sia_licence_detail').text(data.sia_licence);
                $('#sia_expiry_detail').text(data.sia_expiry);
                $('#licence_type_detail').text(data.licence_type);
                $('#entry_date_detail').text(data.entry_date);
                $('#dob_detail').text(data.dob);
                $('#service_type_detail').text(data.service_type);
                $('#visa_type_detail').text(data.visa_type);
                $('#visa_expiry_detail').text(data.visa_expiry);
                $('#place_work_detail').text(data.place_work);
                $('#contact_detail').text(data.contact);
                $('#emergency_contact_detail').text(data.emergency_contact);
                $('#job_title_detail').text(data.job_title);
                $('#nationality_detail').text(data.nationality);
                $('#passport_no_detail').text(data.passport_no);
                $('#passport_expiry_detail').text(data.passport_expiry);
                $('#driving_licence_detail').text(data.driving_licence_number ?? 'N/A');
                $('#driving_licence_expiry_detail').text(data.driving_licence_expiry ?? 'N/A');
                $('#address_group_detail').text(data.address_group);
                $('#guard_rate_detail').text(`$${data.guard_rate ?? 0}`);
                $('#bank_info_detail').text(
                    `${data.bank_name ?? 'N/A'} / ${data.account_name ?? 'N/A'} / ${data.account_number ?? 'N/A'}`
                );
                $('#other_info_detail').text(data.other_info ?? '');

                // Main documents mapping
                const documentTypes = {
                    sia_licence_file: "SIA Licence",
                    passport_file: "Passport",
                    proof_of_address_file: "Proof of Address",
                    ni_letter_file: "NI Letter",
                    first_aid_certificate_file: "Right to Work",
                    act_certificate_file: "ACT Certificate",
                    driving_licence_file: "Driving Licence"
                };

                let documentHtml = "";
                let hasDocs = false;

                Object.entries(documentTypes).forEach(([field, label]) => {
                    const fileName = data[field];
                    if (fileName) {
                        hasDocs = true;
                        const url = `${baseUrl}/documents/${fileName}`;
                        documentHtml += `<div class="mb-1">
                    <strong>${label}:</strong> 
                    <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary ms-1">View</a>
                </div>`;
                    }
                });

                if (!hasDocs) {
                    documentHtml = '<span class="text-muted">Employee didn\'t upload any main document.</span>';
                }

                // Additional documents (stored as JSON array)
                let additionalHtml = "";
                let hasAdditionalDocs = false;

                if (data.additional_files) {
                    let additionalDocs = data.additional_files;

                    // If it's a string, parse it
                    if (typeof additionalDocs === 'string') {
                        try {
                            additionalDocs = JSON.parse(additionalDocs);
                        } catch (e) {
                            additionalDocs = [];
                        }
                    }
                    if (Array.isArray(additionalDocs) && additionalDocs.length > 0) {
                        hasAdditionalDocs = true;
                        additionalDocs.forEach(filePath => {
                            // Extract the file name from the path
                            const fileName = filePath.split('/').pop();

                            additionalHtml += `<div class="mb-1">
                                <span class="ms-2">${fileName}</span>
            <a href="${baseUrl}/${filePath}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1">View</a>
        </div>`;
                        });
                    }
                }

                if (!hasAdditionalDocs) {
                    additionalHtml = '<span class="text-muted">No additional documents uploaded.</span>';
                }

                // Set the combined documents HTML inside modal element
                $('#document_list_detail').html(`
            <h6>Main Documents</h6>
            ${documentHtml}
            <hr>
            <h6>Additional Documents</h6>
            ${additionalHtml}
        `);

                // Days of week mapping
                const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                let availabilityHtml = '';
                if (Array.isArray(data.availability) && data.availability.length === 7) {
                    data.availability.forEach(day => {
                        availabilityHtml += `<tr>
            <td>${daysOfWeek[day.day_of_week]}</td>
            <td>${day.start}</td>
            <td>${day.end}</td>
        </tr>`;
                    });
                } else {
                    availabilityHtml = `<tr><td colspan="3" class="text-muted">No availability set.</td></tr>`;
                }

                $('#availability_table tbody').html(availabilityHtml);

                // Show the Bootstrap modal
                let modal = new bootstrap.Modal(document.getElementById('viewEmployeeDetailModal'));
                modal.show();
            }).fail(function() {
                toast_danger('Failed to fetch employee detail.');
            });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Get hash from URL (without the '#' symbol)
            let hashId = window.location.hash.substring(1);
            // If the hash is a number, call the function
            if (hashId && !isNaN(hashId)) {
                viewEmployeeDetail(hashId);
            }
        });
    </script>

    {!! $dataTable->scripts() !!}
@endsection
