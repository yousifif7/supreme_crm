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
                    <!-- Employee Status Filter -->
                    <div class="d-inline-block ms-2 d-flex align-items-center">
                        <select id="empStatusFilter" class="form-select form-select-sm">
                            <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Employees
                            </option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Terminated" {{ request('status') == 'Terminated' ? 'selected' : '' }}>Terminated</option>
                            <option value="need_approval" {{ request('status') == 'need_approval' ? 'selected' : '' }}>Need Approval</option>
                        </select>
                    </div>
                    <div class="d-inline-block ms-2 d-flex align-items-center">
                        <select id="siaStatusFilter" class="form-select form-select-sm">
                            <option value="" {{ request('sia_status') == '' ? 'selected' : '' }}>All SIA Statuses
                            </option>
                            <option value="Active" {{ request('sia_status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('sia_status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
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
    {{-- Employee logs partial (modal) --}}
    @include('employees.logs')
    <style>
        /* Ensure log modal tables wrap and scroll instead of overflowing */
        #logModal .table-responsive, #logModal .table-responsive table {
            max-height: 60vh;
            overflow: auto;
            width: 100%;
            table-layout: fixed !important;
        }
        #logModal .table-responsive td,
        #logModal .table-responsive th,
        #logModal table td, #logModal table th {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }
    </style>
    <!-- View Employee Detail Modal -->
    <div class="modal fade" id="viewEmployeeDetailModal" tabindex="-1" aria-labelledby="employeeDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="employeeDetailLabel">
                        Employee <span id="employee_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="container-fluid">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Full Name</div>
                                <div id="full_name_detail" class="fw-bold"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Email</div>
                                <div id="email_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Gender</div>
                                <div id="gender_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">NI Number</div>
                                <div id="ni_number_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">SIA Licence</div>
                                <div id="sia_licence_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">SIA Expiry</div>
                                <div id="sia_expiry_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Licence Type</div>
                                <div id="licence_type_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Entry Date</div>
                                <div id="entry_date_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Date of Birth</div>
                                <div id="dob_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Service Type</div>
                                <div id="service_type_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Visa Type</div>
                                <div id="visa_type_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Visa Expiry</div>
                                <div id="visa_expiry_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Place of Work</div>
                                <div id="place_work_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Contact Number</div>
                                <div id="contact_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Emergency Contact</div>
                                <div id="emergency_contact_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Job Title</div>
                                <div id="job_title_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Nationality</div>
                                <div id="nationality_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Passport No</div>
                                <div id="passport_no_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Passport Expiry</div>
                                <div id="passport_expiry_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Driving Licence</div>
                                <div id="driving_licence_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Driving Licence Expiry</div>
                                <div id="driving_licence_expiry_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Address Group</div>
                                <div id="address_group_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Manager</div>
                                <div id="manager_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Guard Rate</div>
                                <div id="guard_rate_detail"></div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">Bank Info</div>
                                <div id="bank_info_detail"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Other Info</div>
                                <div id="other_info_detail"></div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="text-muted small">Documents</div>
                                <div id="document_list_detail">
                                    <span class="text-muted">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
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

        // SIA status filter reload
        $(document).ready(function() {
            // Auto-apply when the select changes
            $('#siaStatusFilter').on('change', function() {
                const val = $(this).val();
                try {
                    const table = $('#employees-table').DataTable();
                    // Use draw(false) to keep the current paging where possible
                    table.draw(false);
                } catch (err) {
                    const url = new URL(window.location.href);
                    if (val) url.searchParams.set('sia_status', val);
                    else url.searchParams.delete('sia_status');
                    window.location.href = url.toString();
                }
            });

            // Employee status filter reload
            $('#empStatusFilter').on('change', function() {
                const val = $(this).val();
                try {
                    const table = $('#employees-table').DataTable();
                    table.draw(false);
                } catch (err) {
                    const url = new URL(window.location.href);
                    if (val) url.searchParams.set('status', val);
                    else url.searchParams.delete('status');
                    window.location.href = url.toString();
                }
            });

            // Ensure the selected SIA status is sent with every ajax request from DataTables
            // Use delegated event in case DataTables is (re)initialized by the package after DOM ready
            $(document).on('preXhr.dt', '#employees-table', function(e, settings, data) {
                try {
                    data.sia_status = $('#siaStatusFilter').val();
                    data.status = $('#empStatusFilter').val();
                } catch (err) {
                    // ignore
                }
            });
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
                $('#add_employee_loading').show();

                $.ajax({
                    url: `${baseUrl}/employees`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#add_employee_loading').hide();
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
                            
                            // Get the first error message for toast
                            let firstError = Object.values(errors)[0][0];
                            toast_danger(firstError);
                        } else {
                            let response = xhr.responseJSON;

                            if (response && response.error) {
                                // Show backend error (like invalid SIA licence)
                                toast_danger(response.error);
                            } else if (response && response.message) {
                                toast_danger(response.message);
                            } else if (response && response.errors) {
                                // Sometimes errors come as a flat object
                                let firstError = Object.values(response.errors)[0];
                                let errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                                toast_danger(errorMessage);
                            } else if (xhr.responseText) {
                                try {
                                    const parsedResponse = JSON.parse(xhr.responseText);
                                    toast_danger(parsedResponse.error || parsedResponse.message || 'An unexpected error occurred.');
                                } catch (e) {
                                    // fallback if backend returns plain text
                                    if (xhr.responseText.length < 200) {
                                        toast_danger(xhr.responseText);
                                    } else {
                                        toast_danger('An unexpected error occurred. Please try again.');
                                    }
                                }
                            } else {
                                toast_danger('An unexpected error occurred. Please try again.');
                            }
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        $('#add_employee_loading').hide();
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
            $('#edit_employee_form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='editerror_']").text('').addClass('d-none');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editEmployeeBtn'); // Correct button ID

                // Get the employee ID from a hidden input field
                let employeeId = $('#employee_id')
                    .val(); // Make sure you have <input type="hidden" id="employee_id" value="123">

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');
                $('#edit_employee_loading').show();

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
                        $('#edit_employee_loading').hide();
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
                            
                            // Get the first error message for toast
                            let firstError = Object.values(errors)[0][0];
                            toast_danger(firstError);
                        } else {
                            let response = xhr.responseJSON;

                            if (response && response.error) {
                                // Show backend error (like invalid SIA licence)
                                toast_danger(response.error);
                            } else if (response && response.message) {
                                toast_danger(response.message);
                            } else if (response && response.errors) {
                                // Sometimes errors come as a flat object
                                let firstError = Object.values(response.errors)[0];
                                let errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                                toast_danger(errorMessage);
                            } else if (xhr.responseText) {
                                try {
                                    const parsedResponse = JSON.parse(xhr.responseText);
                                    toast_danger(parsedResponse.error || parsedResponse.message || 'An unexpected error occurred.');
                                } catch (e) {
                                    // fallback if backend returns plain text
                                    if (xhr.responseText.length < 200) {
                                        toast_danger(xhr.responseText);
                                    } else {
                                        toast_danger('An unexpected error occurred. Please try again.');
                                    }
                                }
                            } else {
                                toast_danger('An unexpected error occurred. Please try again.');
                            }
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        $('#edit_employee_loading').hide();
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

        // Helper function to format date to YYYY-MM-DD for HTML date inputs
        function formatDate(dateStr) {
            if (!dateStr) return '';
            
            // If already in YYYY-MM-DD format, return as is
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                return dateStr;
            }
            
            // Try to parse and convert to YYYY-MM-DD
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return ''; // Invalid date
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return `${year}-${month}-${day}`;
        }

        function editEmployee(record_id) {
            $.get(`${baseUrl}/editemployee/` + record_id, function(data) {
                if (data.employee) {
                    // Fill all the form fields
                    $('#employee_id').val(data.employee.id);
                    $('#username').val(data.employee.username);
                    $('#status').val(data.employee.status);
                    $('#fore_name').val(data.employee.fore_name);
                    $('#sur_name').val(data.employee.sur_name);
                    $('#gender').val(data.employee.gender);
                    $('#email').val(data.employee.email);
                    $('#ni_number').val(data.employee.ni_number);
                    $('#sia_licence').val(data.employee.sia_licence);
                    
                    // Format date fields
                    $('#sia_expiry').val(formatDate(data.employee.sia_expiry));
                    $('#licence_type').val(data.employee.licence_type);
                    $('#entry_date').val(formatDate(data.employee.entry_date));
                    $('#dob').val(formatDate(data.employee.dob));
                    $('#service_type').val(data.employee.service_type);
                    $('#visa_type').val(data.employee.visa_type);
                    $('#visa_expiry').val(formatDate(data.employee.visa_expiry));
                    $('#place_work').val(data.employee.place_work);
                    $('#hour_per_week').val(data.employee.hour_per_week);
                    $('#passport_no').val(data.employee.passport_no);
                    $('#passport_expiry').val(formatDate(data.employee.passport_expiry));
                    $('#address_group').val(data.employee.address_group);
                    $('#contact').val(data.employee.contact);
                    $('#emergency_contact').val(data.employee.emergency_contact);
                    $('#job_title').val(data.employee.job_title);
                    $('#nationality').val(data.employee.nationality);
                    $('#reference_to_emp').val(data.employee.reference_number);
                    $('#next_kin').val(data.employee.next_kin);
                    $('#relation_with_kin').val(data.employee.relation_with_kin);
                    $('#kin_address').val(data.employee.kin_address);
                    $('#kin_number').val(data.employee.kin_number);
                    $('#kin_work_tel').val(data.employee.kin_work_tel);
                    $('#kin_mobile').val(data.employee.kin_mobile);
                    $('#share_code').val(data.employee.share_code);
                    $('#share_code_expiry').val(formatDate(data.employee.share_code_expiry));
                    $('#biometric_residence_permit').val(data.employee.biometric_residence_permit);
                    $('#biometric_residence_permit_expiry').val(formatDate(data.employee.biometric_residence_permit_expiry));
                    $('#settlement').val(data.employee.settlement);
                    $('#tags').val(data.employee.tags);
                    $('#department_id').val(data.employee.department_id);
                    $('#subcontractor').val(data.employee.subcontractor);
                    $('#additional_sia_number').val(data.employee.additional_sia_number);
                    $('#license_type').val(data.employee.license_type);
                    $('#license_expiry').val(formatDate(data.employee.license_expiry));
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
                    $('#employment_start_date').val(formatDate(data.employee.employment_start_date));
                    $('#employment_end_date').val(formatDate(data.employee.employment_end_date));

                    // Display existing documents under each upload field
                    const documentFields = {
                        'sia_licence_file': 'SIA Licence',
                        'passport_file': 'Passport',
                        'proof_of_address_file': 'Proof of Address',
                        'driving_licence_file': 'Driving Licence',
                        'ni_letter_file': 'NI Letter',
                        'first_aid_certificate_file': 'Right to Work',
                        'act_certificate_file': 'ACT Certificate'
                    };

                    // Clear existing previews
                    $('.document-preview').remove();

                    // Add document previews
                    Object.keys(documentFields).forEach(field => {
                        if (data.employee[field]) {
                            const fileName = data.employee[field];
                            const url = `${baseUrl}/documents/${fileName}`;
                            const previewHtml = `
                                <div class="document-preview mt-2">
                                    <small class="text-muted">Current: </small>
                                    <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-file"></i> View ${documentFields[field]}
                                    </a>
                                </div>
                            `;
                            $('#' + field).after(previewHtml);
                        }
                    });

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
            var modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            function esc(s){ if (s === null || s === undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

            // First fetch the employee to obtain their email, then call the getLogsByEmail route
            fetch(baseUrl + '/employees/' + employeeId + '/view')
                .then(function(resp){ return resp.json(); })
                .then(function(emp){
                    var email = emp && emp.email ? emp.email : null;
                    if (!email) {
                        modalBody.innerHTML = '<p class="text-muted">No email found for this client.</p>';
                        return Promise.reject(new Error('no-email'));
                    }

                    var encodedEmail = encodeURIComponent(email);
                    return fetch(baseUrl + '/employees/logs/' + encodedEmail);
                })
                .then(function(response){ if (!response) return; return response.json(); })
                .then(function(data){
                    if (!data || !data.logs || data.logs.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">No logs found for this client.</p>';
                        return;
                    }

                    var html = '<div class="table-responsive" style="max-height:60vh;overflow:auto;">';
                    html += '<table class="table table-bordered table-striped mb-0" style="min-width:100%;table-layout:fixed;">';
                    html += '<colgroup><col style="width:15%"><col style="width:15%"><col style="width:55%"><col style="width:15%"></colgroup>';
                    html += '<thead><tr><th>User</th><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>';
                    data.logs.forEach(function(log){
                        var desc = esc(log.description || '');
                        // Split long comma-separated lists into separate lines
                        desc = desc.replace(/,\s*/g, '<br>');
                        html += '<tr>' +
                            '<td>' + esc(log.user_name) + '</td>' +
                            '<td>' + esc(log.action) + '</td>' +
                            '<td>' + desc + '</td>' +
                            '<td>' + esc(log.time) + '</td>' +
                            '</tr>';
                    });
                    html += '</tbody></table></div>';
                    modalBody.innerHTML = html;

                    // Show the modal (logModal exists in the page)
                    try { $('#logModal').modal('show'); } catch(e){}
                })
                .catch(function(error){
                    if (error && error.message === 'no-email') return; // already handled
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
                        additionalDocs.forEach(item => {
                            // Support either simple string paths or objects { path: '', status: '' }
                            let filePath = '';
                            let status = '';
                            if (typeof item === 'string') {
                                filePath = item;
                            } else if (typeof item === 'object' && item !== null) {
                                filePath = item.path || item.file || item.file_path || '';
                                status = item.status || item.state || '';
                            }

                            if (!filePath) return;

                            // Extract the file name from the path
                            const fileName = filePath.split('/').pop();

                            additionalHtml += `<div class="mb-1 d-flex align-items-center" data-filepath="${filePath}">
                                <span class="ms-2">${fileName}</span>
                                <a href="${baseUrl}/${filePath}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1">View</a>`;

                            // If the document appears to be pending, show Approve / Reject buttons
                            const pendingStatuses = ['pending', 'awaiting', 'for_review', 'pending_admin'];
                            const isPending = status && pendingStatuses.includes(status.toString().toLowerCase());

                            // If caller provided a separate statuses map, prefer that
                            if (!isPending && data.employee && data.employee.additional_files_status) {
                                const map = data.employee.additional_files_status;
                                try {
                                    const s = map[filePath] || map[fileName] || '';
                                    if (s && pendingStatuses.includes(s.toString().toLowerCase())) {
                                        isPending = true;
                                    }
                                } catch (e) {
                                    // ignore
                                }
                            }

                            if (isPending) {
                                additionalHtml += `
                                    <button type="button" class="btn btn-sm btn-success ms-2 approve-doc-btn" data-file="${filePath}" data-employee="${id}">Approve</button>
                                    <button type="button" class="btn btn-sm btn-danger ms-1 reject-doc-btn" data-file="${filePath}" data-employee="${id}">Reject</button>`;
                            }

                            additionalHtml += `</div>`;
                        });
                    }
                }

                if (!hasAdditionalDocs) {
                    additionalHtml = '<span class="text-muted">No additional documents uploaded.</span>';
                }

                // Fetch documents from Documents table for this employee's user_id and render combined view
                const renderCombinedDocs = (dbDocsHtml) => {
                    $('#document_list_detail').html(`
            <h6>Documents (DB)</h6>
            ${dbDocsHtml}
        `);
                };

                if (data.user_id) {
                    $.get(`${baseUrl}/documents/user/${data.user_id}/ajax`, function(resp) {
                        // Separate approved and pending documents
                        const docs = resp.documents || [];
                        const approved = docs.filter(d => (d.status || '').toLowerCase() === 'approved');
                        const pending = docs.filter(d => (d.status || '').toLowerCase() !== 'approved');

                        // Build Main Documents HTML: prefer approved docs for known fields
                        let mainHtml = '';
                        let anyMain = false;
                        Object.entries(documentTypes).forEach(([field, label]) => {
                            // find approved doc by document_type matching the field
                            const approvedDoc = approved.find(d => d.document_type === field);
                            if (approvedDoc) {
                                anyMain = true;
                                const fileName = (approvedDoc.file_path || '').split('/').pop();
                                mainHtml += `<div class="mb-1"><strong>${label}:</strong> <a href="${baseUrl}/${approvedDoc.file_path}" target="_blank" class="btn btn-sm btn-outline-primary ms-1">${fileName}</a></div>`;
                            } else if (data[field]) {
                                // fallback to employee field (unverified)
                                anyMain = true;
                                const url = `${baseUrl}/documents/${data[field]}`;
                                mainHtml += `<div class="mb-1"><strong>${label}:</strong> <a href="${url}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1">View (unverified)</a></div>`;
                            }
                        });

                        // Include any other approved documents (not mapped) under 'Other approved documents'
                        const mappedTypes = Object.keys(documentTypes);
                        const otherApproved = approved.filter(d => !mappedTypes.includes(d.document_type));
                        if (otherApproved.length > 0) {
                            anyMain = true;
                            mainHtml += `<div class="mt-2"><strong>Other approved documents:</strong><ul class="list-unstyled mb-0">`;
                            otherApproved.forEach(d => {
                                const fileName = (d.file_path || '').split('/').pop();
                                mainHtml += `<li><a href="${baseUrl}/${d.file_path}" target="_blank">${fileName}</a></li>`;
                            });
                            mainHtml += `</ul></div>`;
                        }

                        if (!anyMain) mainHtml = '<span class="text-muted">Employee didn\'t upload any approved main document.</span>';

                        // Build Pending Documents HTML
                        let pendingHtml = '';
                        if (pending.length > 0) {
                            pendingHtml = '<ul class="list-unstyled mb-0">';
                            pending.forEach(d => {
                                const fileName = (d.file_path || '').split('/').pop();
                                const comment = d.admin_comments ? `<div class="small text-muted">${d.admin_comments}</div>` : '';
                                // Determine a human-friendly type label using documentTypes mapping
                                const typeLabel = (d.document_type && documentTypes[d.document_type]) ? documentTypes[d.document_type] : (d.document_type || 'Other');
                                const isRejected = (d.status || '').toString().toLowerCase() === 'rejected';

                                if (isRejected) {
                                    // Show rejected status and admin comment; hide action buttons
                                    pendingHtml += `<li class="mb-2 d-flex align-items-center">
                                        <span class="badge bg-secondary me-2 text-truncate" style="max-width:120px;">${typeLabel}</span>
                                        <a href="${baseUrl}/${d.file_path}" target="_blank" class="me-2">${fileName}</a>
                                        <span class="badge bg-danger ms-1">Rejected</span>
                                        ${comment}
                                    </li>`;
                                } else {
                                    pendingHtml += `<li class="mb-2 d-flex align-items-center">
                                        <span class="badge bg-secondary me-2 text-truncate" style="max-width:120px;">${typeLabel}</span>
                                        <a href="${baseUrl}/${d.file_path}" target="_blank" class="me-2">${fileName}</a>
                                        <span class="badge bg-warning text-dark ms-1">${d.status || 'pending'}</span>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm btn-success ms-2 approve-doc-btn" data-file="${d.file_path}" data-employee="${id}">Approve</button>
                                            <button class="btn btn-sm btn-danger ms-1 reject-doc-btn" data-file="${d.file_path}" data-employee="${id}">Reject</button>
                                        </div>
                                        ${comment}
                                    </li>`;
                                }
                            });
                            pendingHtml += '</ul>';
                        } else {
                            pendingHtml = '<span class="text-muted">No pending documents.</span>';
                        }

                        // Keep existing additional_files rendering as-is (but mark those that are already approved)
                        let additionalHtmlRendered = additionalHtml;

                        // Render combined sections: Main Documents (approved), Pending Documents, Additional Documents (employee fields)
                        renderCombinedDocs(`
                            <div id="main_docs_section">${mainHtml}</div>
                            <hr>
                            <h6>Pending Documents</h6>
                            <div id="pending_docs_section">${pendingHtml}</div>
                            <hr>
                            <h6>Additional Documents</h6>
                            ${additionalHtmlRendered}
                        `);
                    }).fail(function() {
                        // If documents endpoint fails, still render the rest
                        renderCombinedDocs('<span class="text-muted">Unable to load documents.</span>');
                    });
                } else {
                    // No user_id available; render without DB docs
                    renderCombinedDocs('<span class="text-muted">No user id available to load documents.</span>');
                }

                // Attach handlers for approve/reject buttons that were just injected
                $(document).off('click', '.approve-doc-btn').on('click', '.approve-doc-btn', function() {
                    const file = $(this).data('file');
                    const emp = $(this).data('employee');
                    $('#doc_action_employee_id').val(emp);
                    $('#doc_action_file').val(file);
                    $('#approveConfirmModal').modal('show');
                });

                $(document).off('click', '.reject-doc-btn').on('click', '.reject-doc-btn', function() {
                    const file = $(this).data('file');
                    const emp = $(this).data('employee');
                    $('#doc_action_employee_id').val(emp);
                    $('#doc_action_file').val(file);
                    $('#rejectModal textarea[name="admin_comment"]').val('');
                    $('#rejectModal').modal('show');
                });

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
    <!-- Approve Confirmation Modal -->
    <div class="modal fade" id="approveConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Approve Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this document?</p>
                    <input type="hidden" id="doc_action_employee_id" value="">
                    <input type="hidden" id="doc_action_file" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmApproveBtn" class="btn btn-success">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal (submit admin comment) -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectDocForm">
                        <div class="mb-3">
                            <label for="admin_comment" class="form-label">Admin Comment (required)</label>
                            <textarea name="admin_comment" class="form-control" rows="4" required></textarea>
                        </div>
                        <input type="hidden" id="doc_action_employee_id" value="">
                        <input type="hidden" id="doc_action_file" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="rejectDocForm" id="submitRejectBtn" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Approve document AJAX
        $('#confirmApproveBtn').on('click', function() {
            const employeeId = $('#doc_action_employee_id').val();
            const filePath = $('#doc_action_file').val();
            if (!filePath || !employeeId) {
                toast_danger('Missing information');
                return;
            }

            $(this).prop('disabled', true).text('Approving...');

            $.ajax({
                url: `${baseUrl}/employees/${employeeId}/documents/approve`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: { file_path: filePath },
                success: function(res) {
                    $('#approveConfirmModal').modal('hide');
                    toast_success(res.message || 'Document approved');
                    // Refresh the employee detail to update statuses
                    viewEmployeeDetail(employeeId);
                },
                error: function(xhr) {
                    const resp = xhr.responseJSON;
                    toast_danger((resp && resp.error) ? resp.error : 'Failed to approve document');
                },
                complete: function() {
                    $('#confirmApproveBtn').prop('disabled', false).text('Approve');
                }
            });
        });

        // Reject document AJAX
        $('#rejectDocForm').on('submit', function(e) {
            e.preventDefault();
            const employeeId = $('#doc_action_employee_id').val();
            const filePath = $('#doc_action_file').val();
            const comment = $(this).find('textarea[name="admin_comment"]').val();
            if (!comment || comment.trim().length === 0) {
                toast_danger('Please provide a comment for rejection');
                return;
            }

            $('#submitRejectBtn').prop('disabled', true).text('Rejecting...');

            $.ajax({
                url: `${baseUrl}/employees/${employeeId}/documents/reject`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: { file_path: filePath, admin_comment: comment },
                success: function(res) {
                    $('#rejectModal').modal('hide');
                    toast_success(res.message || 'Document rejected');
                    viewEmployeeDetail(employeeId);
                },
                error: function(xhr) {
                    const resp = xhr.responseJSON;
                    toast_danger((resp && resp.error) ? resp.error : 'Failed to reject document');
                },
                complete: function() {
                    $('#submitRejectBtn').prop('disabled', false).text('Reject');
                }
            });
        });
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
  <style>
      .modal-loading-overlay {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(255, 255, 255, 0.9);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1060;
          border-radius: 0.5rem;
      }
      .modal-loading-overlay .spinner-border {
          width: 3rem;
          height: 3rem;
      }
  </style>