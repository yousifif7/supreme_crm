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
                    <div class="custom-datatable-filter table-responsive datatable">
                        <table class="table table-striped table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>SIA</th>
                                    <th>EXPIRY</th>
                                    <th>VISA EXPIRY</th>
                                    <th>IMMIGRATION STATUS</th>
                                    <th>CONTACT NO</th>
                                    <th>SUBCONTRACTOR</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr id="employee_row_{{ $employee->id }}">
                                        <td><input type="checkbox" class="dT-row-checkbox" value="{{ $employee->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center file-name-icon">
                                                <div class="ms-2">
                                                    <h6 class="fw-medium">
                                                        {{ $employee->fore_name }} {{ $employee->middle_name }}
                                                        {{ $employee->sur_name }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <p class="mb-0 fw-semibold">{{ $employee->sia_licence }}</p>
                                            <span class="text-primary fw-bold">Active</span>
                                        </td>
                                        <td>{{ $employee->sia_expiry }}</td>
                                        <td>{{ $employee->visa_expiry }}</td>
                                        <td>{{ $employee->visa_type }}</td>
                                        <td>{{ $employee->contact }}</td>
                                        <td>{{ $employee->subcontractor }}</td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <button class="sites_action-btn"
                                                    onclick="viewLogs({{ $employee->id }})">Logs</button>
                                                <a href="#" class="me-2"
                                                    onclick="viewEmployeeDetail({{ $employee->id }})"><i
                                                        class="ti ti-eye"></i></a>
                                                <a class="me-2" onclick="editEmployee({{ $employee->id }})">
                                                    <i class="ti ti-edit"></i>
                                                </a>

                                                <a href="#" class="me-2"
                                                    onclick="generatePayroll({{ $employee->id }})"><i
                                                        class="ti ti-receipt"></i></a>

                                                <a onclick="deleteEmployee({{ $employee->id }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                                <a href="{{ route('employees.print', $employee->id) }}" target="_blank">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No employees found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>


                </div>

            </div>
            <div class="card-footer d-flex justify-content-center">
                {{ $employees->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>

        <!-- /Page Wrapper -->
        <!-- Add Employee -->
        <div class="modal fade" id="add_employee">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Security Staff</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body pb-0 ">
                        <form method="POST" id="add_worker-form1">
                            @csrf
                                    <div class="row part-1">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div id="map"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Username <span
                                                        class="text-danger">*</span></label>
                                                <input type="email" name="username" class="form-control"
                                                    placeholder="Enter Username">
                                                <span class="text-danger form-error" id="error_username"></span>

                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Password <span
                                                        class="text-danger">*</span></label>
                                                <input type="password" name="password" class="form-control"
                                                    placeholder="Enter Password">
                                                <span class="text-danger form-error" id="error_password"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="Active" selected>Active</option>
                                                    <option value="Terminated">Terminated</option>
                                                    <option value="Need Approval">Need Approval</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_status"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row part-2">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Forename <span class="text-danger">*</span></label>
                                            <input type="text" name="fore_name" class="form-control bg-yellow"
                                                placeholder="Enter Forename">
                                            <span class="text-danger form-error" id="error_fore_name"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Surname <span class="text-danger">*</span></label>
                                            <input type="text" name="sur_name" class="form-control bg-yellow"
                                                placeholder="Enter Surname">
                                            <span class="text-danger form-error" id="error_sur_name"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                                            <select class="form-select bg-yellow" name="gender">
                                                <option value="Male" selected>Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_gender"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label ">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control bg-yellow"
                                                placeholder="Enter Email">
                                            <span class="text-danger form-error" id="error_email"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">N.I. Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="ni_number" class="form-control bg-yellow"
                                                placeholder="Enter N.I. Number">
                                            <span class="text-danger form-error" id="error_ni_number"></span>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isaCheck">
                                                <label class="form-check-label text-danger" for="isaCheck">SIA not
                                                    required</label>

                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SIA Licence <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="sia_licence" class="form-control bg-yellow"
                                                placeholder="Enter SIA Licence"> <span class="text-danger form-error"
                                                id="error_sia_licence"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SIA Expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="sia_expiry" class="form-control bg-yellow"
                                                placeholder="Enter SIA Expiry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Licence Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select bg-yellow" name="licence_type">
                                                <option value="">--choose--</option>
                                                @foreach ($licenses as $license)
                                                    <option value="{{ $license->name }}">{{ $license->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Date of Entry / Re-entry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="entry_date" class="form-control"
                                                placeholder="Enter Date of Entry / Re-entry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">D.O.B <span class="text-danger">*</span></label>
                                            <input type="date" name="dob" class="form-control"
                                                placeholder="D.O.B">
                                            <span class="text-danger form-error" id="error_dob"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Service Type</label>
                                            <select class="form-select" name="service_type">
                                                <option value="">--choose--</option>
                                                @foreach ($employee_types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Visa Type <span class="text-danger">*</span></label>
                                            <select class="form-select visa_type" name="visa_type">
                                                <option value="">-- choose --</option>
                                                @foreach ($visa_types as $visa)
                                                    <option value="{{ $visa->name }}">{{ $visa->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="error_visa_type"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Visa Expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="visa_expiry" class="form-control"
                                                placeholder="Enter Visa Expiry">
                                            <span class="text-danger form-error" id="error_visa_expiry"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Place of Work <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="place_work" class="form-control"
                                                placeholder="Place of Work">
                                            <span class="text-danger form-error" id="error_place_work"></span>
                                        </div>
                                        <div class="terms-section" style="display: none;">
                                            <h5>Employee Terms</h5>
                                            <div id="term-rows">
                                                <!-- Dynamic rows will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary my-3" id="addTermRow">+ Add Terms</button>
                                        </div>
                                        <div class="clear-fix"></div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">No of hours per week</label>
                                            <input type="text" name="hour_per_week" class="form-control"
                                                placeholder="Enter Hours"><span class="text-danger form-error"
                                                id="error_hour_per_week"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Passport no. <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="passport_no" class="form-control"
                                                placeholder="Enter Passport no.">
                                            <span class="text-danger form-error" id="error_passport_no"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Passport expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="passport_expiry" class="form-control"
                                                placeholder="Enter Passport expiry">
                                            <span class="text-danger form-error" id="error_passport_expiry"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Address group</label>
                                            <select class="form-select" name="address_group">
                                                <option selected value="">-- choose --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Contact No: <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="contact" class="form-control"
                                                placeholder="Enter Contact No">
                                            <span class="text-danger form-error" id="error_contact_no"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Emergency contact</label>
                                            <input type="text" name="emergency_contact" class="form-control"
                                                placeholder="Enter emergency contact no.">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Job Title</label>
                                            <input type="text" name="job_title" class="form-control"
                                                placeholder="Enter Job Title">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Nationality <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="nationality" class="form-control"
                                                placeholder="Enter nationality">
                                            <span class="text-danger form-error" id="error_nationality"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">P.I.N <span class="text-danger">*</span></label>
                                            <input type="text" name="pin" class="form-control"
                                                placeholder="Enter PIN">
                                            <span class="text-danger form-error" id="error_pin"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Reference</label>
                                            <input type="text" name="reference_to_emp" class="form-control"
                                                placeholder="Enter Reference">
                                            <span class="text-danger form-error" id="error_reference"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Next of Kin</label>
                                            <input type="text" name="next_kin" class="form-control"
                                                placeholder="Enter next of kin">
                                            <span class="text-danger form-error" id="error_next_kin"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Relationship</label>
                                            <input type="text" name="relation_with_kin" class="form-control"
                                                placeholder="Enter Relationship">
                                            <span class="text-danger form-error" id="error_relation_with_kin"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kin address</label>
                                            <input type="text" name="kin_address" class="form-control"
                                                placeholder="Enter kin address">
                                            <span class="text-danger form-error" id="error_kin_address"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Next of Kin Contact No.</label>
                                            <input type="text" name="kin_number" class="form-control"
                                                placeholder="Enter Next of Kin Contact No.">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kin Work tel</label>
                                            <input type="text" name="kin_work_tel" class="form-control"
                                                placeholder="Enter Work Tel">
                                            <span class="text-danger form-error" id="error_kin_work_tel"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kin Mobile</label>
                                            <input type="text" name="kin_mobile" class="form-control"
                                                placeholder="Enter Kin Mobile">
                                            <span class="text-danger form-error" id="error_kin_mobile"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Share code <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="share_code" class="form-control"
                                                placeholder="Enter share code">
                                                <span class="text-danger form-error" id="error_share_code"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Share Code Expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="share_code_expiry" class="form-control">
                                            <span class="text-danger form-error" id="error_share_code_expiry"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Biometric residence permit</label>
                                            <input type="text" name="biometric_residence_permit" class="form-control"
                                                placeholder="Enter biometric residence permit">
                                            <span class="text-danger form-error"
                                                id="error_biometric_residence_permit"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Biometric residence permit expiry</label>
                                            <input type="date" name="biometric_residence_permit_expiry"
                                                class="form-control"
                                                placeholder="Enter biometric residence permit expiry">
                                            <span class="text-danger form-error"
                                                id="error_biometric_residence_permit_expiry"></span>
                                        </div>
                                        {{--<div class="col-md-4 mb-3">
                                            <label class="form-label">BRP status</label>
                                            <select class="form-select" name="brp_status">
                                                <option value="Student Visa" selected>Student Visa</option>
                                                <option value="Dependent Visa">Dependent Visa</option>
                                                <option value="Refugee Status">Refugee Status</option>
                                                <option value="Applied For A New Visa">Applied For A New Visa</option>
                                                <option value="Skilled Worker Visa">Skilled Worker Visa</option>
                                                <option value="Other Visa">Other Visa</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_brp_status"></span>
                                        </div>--}}
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Settlement</label>
                                            <input type="text" name="settlement" class="form-control"
                                                placeholder="Settlement">
                                            <span class="text-danger form-error" id="error_settlement"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Tags</label>
                                            <textarea name="tags" id="" cols="30" rows="4" class="form-control">QA54ER</textarea>
                                            <span class="text-danger form-error" id="error_tags"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Department</label>
                                            <select class="form-select" name="department_id">
                                                <option value="" selected>--choose--</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="error_department_id"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Subcontractor <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="subcontractor">
                                                <option value="AWS SERVICES LTD">AWS SERVICES LTD</option>
                                                <option value="GOOD HANDS LTD">GOOD HANDS LTD</option>
                                                <option value="TOTAL PROTECTION SERVICES LTD">TOTAL PROTECTION SERVICES LTD
                                                </option>
                                                <option value="MASSEC PROTECT LTD">MASSEC PROTECT LTD</option>
                                                <option value="XL STRATEGY SERVICES LTD">XL STRATEGY SERVICES LTD</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_subcontractor"></span>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="mb-0 form-check-input" type="checkbox" id="isaCheck1">
                                                <label class="form-check-label " for="isaCheck">Additional License
                                                </label>
                                            </div>
                                        </div>
                                        <div id="additional-license-section" style="display: none;" class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">S.I.A License</label>
                                                <input type="text" name="additional_sia_number" class="form-control"
                                                    placeholder="Enter S.I.A License">
                                                <span class="text-danger form-error"
                                                    id="error_additional_sia_number"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">License Type</label>
                                                <select class="form-select" name="license_type">
                                                    <option value="">--choose--</option>
                                                    @foreach ($licenses as $license)
                                                        <option value="{{ $license->name }}">{{ $license->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_license_type"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">License Expiry</label>
                                                <input type="date" name="license_expiry" class="form-control">
                                                <span class="text-danger form-error" id="error_license_expiry"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">DBS confirmed</label>
                                                <input type="text" name="dbs_confirmed" class="form-control"
                                                    placeholder="Enter DBS confirmed">

                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Address group</label>
                                                <select class="form-select" name="address_group_additional">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error"
                                                    id="error_address_group_additional"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Profile Picture</label>
                                                <input type="file" name="profile_picture" class="form-control">
                                                <span class="text-danger form-error" id="error_profile_picture"></span>

                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Staff Type</label>
                                                <select class="form-select" name="employee_type">
                                                    <option value="">--choose--</option>
                                                    @foreach ($employee_types as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_employee_type"></span>


                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Do you need visa to work or remain in the
                                                    UK?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="visa_to_work"
                                                            class="form-check-input mb-0" value="1">
                                                        <label class="form-check-label">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="visa_to_work"
                                                            class="form-check-input mb-0" value="0">
                                                        <label class="form-check-label">No</label>
                                                    </div>
                                                </div>


                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">A current driving license?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="driving_license"
                                                            class="form-check-input mb-0" value="1">
                                                        <label class="form-check-label">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="driving_license"
                                                            class="form-check-input mb-0" value="0">
                                                        <label class="form-check-label">No</label>
                                                    </div>
                                                </div>


                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="" class="form-label">
                                                    License Number
                                                </label>
                                                <input type="text" name="license_number" class="form-control"
                                                    placeholder="Enter License Number">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Vehicle in use?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="vehicle_in_use"
                                                            class="form-check-input mb-0" value="1">
                                                        <label class="form-check-label">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="vehicle_in_use"
                                                            class="form-check-input mb-0" value="0">
                                                        <label class="form-check-label">No</label>
                                                    </div>
                                                </div>


                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Any current endorsement. If so please give
                                                    details</label>
                                                <input type="text" name="current_endorsement" class="form-control">
                                            </div>
                                        </div>

                                        <h3 class="mt-2 mb-4">Documents</h3>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="sia_licence_file">SIA Licence</label>
                                            <input type="file" name="sia_licence_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="passport_file">Passport</label>
                                            <input type="file" name="passport_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="proof_of_address_file">Proof of address</label>
                                            <input type="file" name="proof_of_address_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="ni_letter_file">Ni letter</label>
                                            <input type="file" name="ni_letter_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="first_aid_certificate_file">First AID certificate</label>
                                            <input type="file" name="first_aid_certificate_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="act_certificate_file">ACT certificate, Blue and Orange</label>
                                            <input type="file" name="act_certificate_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <h3 class="mt-2 mb-4">Uniform Size</h3>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Collar</label>
                                            <input type="text" name="collar" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Waist</label>
                                            <input type="text" name="waist" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Jacket</label>
                                            <input type="text" name="jacket" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Shoe</label>
                                            <input type="text" name="shoe" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Inseam</label>
                                            <input type="text" name="inseam" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Signature Of Applicant</label>
                                            <input type="file" name="signature" class="form-control">
                                        </div>
                                        <h3 class="mt-2 mb-4">Payroll Information</h3>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Guard Rate</label>
                                            <input type="text" name="guard_rate" placeholder="Enter Guard Rate"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Payment Period</label>
                                            <select class="form-select" name="payment_period">
                                                <option value="Fortnightly" selected>Fortnightly</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Fixed Pay (if any)</label>
                                            <input type="text" name="fixed_pay" placeholder="Enter Fixed Pay (if any)"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Account Name</label>
                                            <input type="text" name="account_name"
                                                placeholder="Enter Bank Account Name" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Account Number</label>
                                            <input type="text" name="account_number"
                                                placeholder="Enter Bank Account Number" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Sort Code</label>
                                            <input type="text" name="sort_code" placeholder="Enter Bank Sort Code"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" name="bank_name" placeholder="Enter Bank Name "
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Branch</label>
                                            <input type="text" name="bank_branch" placeholder="Enter Bank Branch"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Other Information</label>
                                            <textarea name="other_info" id="" cols="30" rows="4" class="form-control"></textarea>
                                        </div>

                                        <div class="holidays-section">
                                            <h5>Employee Holidays</h5>
                                            <div id="holiday-rows">
                                                <!-- Dynamic rows will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary my-3" id="addHolidayRow">+ Add Holiday</button>
                                        </div>

                                    </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light border me-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="add_worker-form1" id="saveemployee"
                            class="btn btn-primary">Save </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add Employee -->

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
                                                        <input type="text" name="employee_name" id="payroll_employee_name" readonly style="background: #eee;"
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
                                                                <label class="form-label">Date From: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_from"
                                                                    id="payroll_date_from" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="payrollerror_date_from"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date To: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_to"
                                                                    id="payroll_date_to" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="payrollerror_date_to"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Notes <span
                                                                class="text-danger">*</span></label>
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
        <div class="modal fade" id="edit_employee">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Employee</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body pb-0 ">
                            <form method="POST" id="edit_employee_form">
                                    @csrf
                                    <div class="row part-1">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div id="map1"></div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="employee_id" id="employee_id">
                                        <div class="col-md-6">

                                        </div>
                                    </div>

                                    <div class="row part-2">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Forename <span class="text-danger">*</span></label>
                                            <input type="text" name="fore_name" id="fore_name"
                                                class="form-control bg-yellow" placeholder="Enter Forename" required>
                                            <span class="text-danger form-error" id="error_forename"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Surname <span class="text-danger">*</span></label>
                                            <input type="text" name="sur_name" id="sur_name"
                                                class="form-control bg-yellow" placeholder="Enter Surname" required>
                                            <span class="text-danger form-error" id="error_surname"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                                            <select class="form-select bg-yellow" name="gender" id="gender">
                                                <option value="Male" selected>Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_gender"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label ">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" id="email"
                                                class="form-control bg-yellow" placeholder="Enter Email">
                                            <span class="text-danger form-error" id="error_email"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">N.I. Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="ni_number" id="ni_number"
                                                class="form-control bg-yellow" placeholder="Enter N.I. Number">
                                            <span class="text-danger form-error" id="error_ni_number"></span>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isaCheck">
                                                <label class="form-check-label text-danger" for="isaCheck">SIA not
                                                    required</label>

                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SIA Licence <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="sia_licence" id="sia_licence"
                                                class="form-control bg-yellow" placeholder="Enter SIA Licence"> <span
                                                class="text-danger form-error" id="error_sia_licence"></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SIA Expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="sia_expiry" id="sia_expiry"
                                                class="form-control bg-yellow" placeholder="Enter SIA Expiry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Licence Type </label>
                                            <select class="form-select bg-yellow" name="licence_type" id="licence_type">
                                                <option value="">--choose--</option>
                                                @foreach ($licenses as $license)
                                                    <option value="{{ $license->name }}">{{ $license->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Date of Entry / Re-entry</label>
                                            <input type="date" name="entry_date" id="entry_date" class="form-control"
                                                placeholder="Enter Date of Entry / Re-entry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">D.O.B</label>
                                            <input type="date" name="dob" id="dob" class="form-control"
                                                placeholder="D.O.B">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Service Type</label>
                                            <select class="form-select" name="service_type" id="service_type">
                                                <option selected value="Alarm Response">Alarm Response</option>
                                                <option value="Keyholding">Keyholding</option>
                                                <option value="Event Staff">Event Staff</option>
                                                <option value="Mobile Patrol">Mobile Patrol</option>
                                                <option value="Static Guards">Static Guards</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Visa Type</label>
                                            <select class="form-select visa_type" name="visa_type" id="visa_type">
                                                <option value="">-- choose --</option>
                                                @foreach ($visa_types as $visa)
                                                    <option value="{{ $visa->name }}">{{ $visa->name }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="visa_expiry">Visa Expiry</label>
                                            <input type="date" name="visa_expiry" id="visa_expiry"
                                                class="form-control" placeholder="Enter Visa Expiry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="place_work">Place of Work</label>
                                            <input type="text" name="place_work" id="place_work" class="form-control"
                                                placeholder="Place of Work">
                                        </div>
                                        <div class="terms-section-edit" style="display: none;">
                                            <h5>Employee Terms</h5>
                                            <div id="editterm-rows">
                                                <!-- Terms load here -->
                                            </div>
                                            <button type="button" id="editTermRow" class="btn btn-sm btn-primary my-3">+ Add Term</button>
                                        </div>
                                        <div class="clear-fix"></div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="hour_per_week">No of hours per week</label>
                                            <input type="text" name="hour_per_week" id="hour_per_week"
                                                class="form-control" placeholder="Enter Hours">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="passport_no">Passport no.</label>
                                            <input type="text" name="passport_no" id="passport_no"
                                                class="form-control" placeholder="Enter Passport no.">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="passport_expiry">Passport expiry</label>
                                            <input type="date" name="passport_expiry" id="passport_expiry"
                                                class="form-control" placeholder="Enter Passport expiry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="address_group">Address group</label>
                                            <select class="form-select" name="address_group" id="address_group">
                                                <option selected>-- choose --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="contact">Contact No:</label>
                                            <input type="text" name="contact" id="contact" class="form-control"
                                                placeholder="Enter Contact No">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="emergency_contact">Emergency contact</label>
                                            <input type="text" name="emergency_contact" id="emergency_contact"
                                                class="form-control" placeholder="Enter emergency contact no.">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="job_title">Job Title</label>
                                            <input type="text" name="job_title" id="job_title" class="form-control"
                                                placeholder="Enter Job Title">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="nationality">Nationality</label>
                                            <input type="text" name="nationality" id="nationality"
                                                class="form-control" placeholder="Enter nationality">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="pin">P.I.N</label>
                                            <input type="text" name="pin" id="pin" class="form-control"
                                                placeholder="Enter PIN">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="reference_to_emp">Reference</label>
                                            <input type="text" name="reference_to_emp" id="reference_to_emp"
                                                class="form-control" placeholder="Enter Reference">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="next_kin">Next of Kin</label>
                                            <input type="text" name="next_kin" id="next_kin" class="form-control"
                                                placeholder="Enter next of kin">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="relation_with_kin">Relationship</label>
                                            <input type="text" name="relation_with_kin" id="relation_with_kin"
                                                class="form-control" placeholder="Enter Relationship">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="kin_address">Kin address</label>
                                            <input type="text" name="kin_address" id="kin_address"
                                                class="form-control" placeholder="Enter kin address">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="kin_number">Next of Kin Contact No.</label>
                                            <input type="text" name="kin_number" id="kin_number" class="form-control"
                                                placeholder="Enter Next of Kin Contact No.">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="kin_work_tel">Kin Work tel</label>
                                            <input type="text" name="kin_work_tel" id="kin_work_tel"
                                                class="form-control" placeholder="Enter Work Tel">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="kin_mobile">Kin Mobile</label>
                                            <input type="text" name="kin_mobile" id="kin_mobile" class="form-control"
                                                placeholder="Enter Kin Mobile">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="share_code">Share Code <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="share_code" id="share_code" class="form-control"
                                                placeholder="Enter Share Code">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="share_code_expiry">Share Code Expiry <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="share_code_expiry" id="share_code_expiry" class="form-control"
                                                placeholder="Enter Share Code Expiry">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="biometric_residence_permit">Biometric residence
                                                permit</label>
                                            <input type="text" name="biometric_residence_permit"
                                                id="biometric_residence_permit" class="form-control"
                                                placeholder="Enter biometric residence permit">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="biometric_residence_permit_expiry">Biometric
                                                residence permit expiry</label>
                                            <input type="date" name="biometric_residence_permit_expiry"
                                                id="biometric_residence_permit_expiry" class="form-control"
                                                placeholder="Enter biometric residence permit expiry">
                                        </div>

                                        {{--<div class="col-md-4 mb-3">
                                            <label class="form-label" for="brp_status">BRP status</label>
                                            <select class="form-select" name="brp_status" id="brp_status1">
                                                <option value="Student Visa" selected>Student Visa</option>
                                                <option value="Dependent Visa">Dependent Visa</option>
                                                <option value="Refugee Status">Refugee Status</option>
                                                <option value="Applied For A New Visa">Applied For A New Visa</option>
                                                <option value="Skilled Worker Visa">Skilled Worker Visa</option>
                                                <option value="Other Visa">Other Visa</option>
                                            </select>
                                        </div>--}}

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="settlement">Settlement</label>
                                            <input type="text" name="settlement" id="settlement" class="form-control"
                                                placeholder="Settlement">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="tags">Tags</label>
                                            <textarea name="tags" id="tags" cols="30" rows="4" class="form-control">QA54ER</textarea>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="department">Department</label>
                                            <select class="form-select" name="department_id" id="department_id">
                                                <option value="" selected>--choose--</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="subcontractor">Subcontractor</label>
                                            <select class="form-select" name="subcontractor" id="subcontractor">
                                                <option value="AWS SERVICES LTD">AWS SERVICES LTD</option>
                                                <option value="GOOD HANDS LTD">GOOD HANDS LTD</option>
                                                <option value="TOTAL PROTECTION SERVICES LTD">TOTAL PROTECTION SERVICES
                                                    LTD
                                                </option>
                                                <option value="MASSEC PROTECT LTD">MASSEC PROTECT LTD</option>
                                                <option value="XL STRATEGY SERVICES LTD">XL STRATEGY SERVICES LTD</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="mb-0 form-check-input" type="checkbox" id="isaCheck1">
                                                <label class="form-check-label" for="isaCheck1">Additional
                                                    License</label>
                                            </div>
                                        </div>

                                        <div id="additional-license-section" class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="additional_sia_number">S.I.A
                                                    License</label>
                                                <input type="text" name="additional_sia_number"
                                                    id="additional_sia_number" class="form-control"
                                                    placeholder="Enter S.I.A License">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="license_type">License Type</label>
                                                <select class="form-select" name="license_type" id="license_type">
                                                    <option value="">--choose--</option>
                                                    @foreach ($licenses as $license)
                                                        <option value="{{ $license->name }}">{{ $license->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="license_expiry">License Expiry</label>
                                                <input type="date" name="license_expiry" id="license_expiry"
                                                    class="form-control">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="dbs_confirmed">DBS confirmed</label>
                                                <input type="text" name="dbs_confirmed" id="dbs_confirmed"
                                                    class="form-control" placeholder="Enter DBS confirmed">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="address_group_additional">Address
                                                    group</label>
                                                <select class="form-select" name="address_group_additional"
                                                    id="address_group_additional">
                                                    <option value="">--choose--</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="profile_picture">Profile Picture</label>
                                                <input type="file" name="profile_picture" id="profile_picture"
                                                    class="form-control">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="employee_type">Employee Type</label>
                                                <select class="form-select" name="employee_type" id="employee_type">
                                                    <option value="">--choose--</option>

                                                    @foreach ($employee_types as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Do you need visa to work or remain in the
                                                    UK?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="visa_to_work"
                                                            id="visa_to_work_yes" class="form-check-input mb-0"
                                                            value="1">
                                                        <label class="form-check-label"
                                                            for="visa_to_work_yes">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="visa_to_work" id="visa_to_work_no"
                                                            class="form-check-input mb-0" value="0">
                                                        <label class="form-check-label" for="visa_to_work_no">No</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">A current driving license?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="driving_license"
                                                            id="driving_license_yes" class="form-check-input mb-0"
                                                            value="1">
                                                        <label class="form-check-label"
                                                            for="driving_license_yes">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="driving_license"
                                                            id="driving_license_no" class="form-check-input mb-0"
                                                            value="0">
                                                        <label class="form-check-label"
                                                            for="driving_license_no">No</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="license_number">License Number</label>
                                                <input type="text" name="license_number" id="license_number1"
                                                    class="form-control" placeholder="Enter License Number">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Vehicle in use?</label>
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <input type="radio" name="vehicle_in_use"
                                                            id="vehicle_in_use_yes" class="form-check-input mb-0"
                                                            value="1">
                                                        <label class="form-check-label"
                                                            for="vehicle_in_use_yes">Yes</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="vehicle_in_use"
                                                            id="vehicle_in_use_no" class="form-check-input mb-0"
                                                            value="0">
                                                        <label class="form-check-label"
                                                            for="vehicle_in_use_no">No</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label" for="current_endorsement">Any current
                                                    endorsement.
                                                    If so please give details</label>
                                                <input type="text" name="current_endorsement"
                                                    id="current_endorsement" class="form-control">
                                            </div>
                                        </div>

                                        <h3 class="mt-2 mb-4">Documents</h3>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="sia_licence_file">SIA Licence</label>
                                            <input type="file" name="sia_licence_file" accept=".jpg,.jpeg,.png,.pdf" id="sia_licence_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="passport_file">Passport_file</label>
                                            <input type="file" name="passport_file" accept=".jpg,.jpeg,.png,.pdf" id="passport_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="proof_of_address_file">Proof of address</label>
                                            <input type="file" name="proof_of_address_file" accept=".jpg,.jpeg,.png,.pdf" id="proof_of_address_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="ni_letter_file">Ni letter</label>
                                            <input type="file" name="ni_letter_file" accept=".jpg,.jpeg,.png,.pdf" id="ni_letter_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="first_aid_certificate_file">First AID certificate</label>
                                            <input type="file" name="first_aid_certificate_file" accept=".jpg,.jpeg,.png,.pdf" id="first_aid_certificate_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="act_certificate_file">ACT certificate, Blue and Orange</label>
                                            <input type="file" name="act_certificate_file" accept=".jpg,.jpeg,.png,.pdf" id="act_certificate_file"
                                                class="form-control">
                                            <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png, Pdf)</span>
                                        </div>

                                        <h3 class="mt-2 mb-4">Uniform Size</h3>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="collar">Collar</label>
                                            <input type="text" name="collar" id="collar"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="waist">Waist</label>
                                            <input type="text" name="waist" id="waist"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="jacket">Jacket</label>
                                            <input type="text" name="jacket" id="jacket"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="shoe">Shoe</label>
                                            <input type="text" name="shoe" id="shoe"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="inseam">Inseam</label>
                                            <input type="text" name="inseam" id="inseam"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="signature">Signature Of Applicant</label>
                                            <input type="file" name="signature" id="signature"
                                                class="form-control">
                                        </div>

                                        <h3 class="mt-2 mb-4">Payroll Information</h3>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="guard_rate">Guard Rate</label>
                                            <input type="text" name="guard_rate" id="guard_rate1"
                                                placeholder="Enter Guard Rate" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="payment_period">Payment Period</label>
                                            <select class="form-select" name="payment_period" id="payment_period">
                                                <option value="Fortnightly" selected>Fortnightly</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="fixed_pay">Fixed Pay (if any)</label>
                                            <input type="text" name="fixed_pay" id="fixed_pay"
                                                placeholder="Enter Fixed Pay (if any)" class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="account_name">Bank Account Name</label>
                                            <input type="text" id="account_name" name="account_name"
                                                placeholder="Enter Bank Account Name" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="account_number">Bank Account Number</label>
                                            <input type="text" id="account_number" name="account_number"
                                                placeholder="Enter Bank Account Number" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="sort_code">Bank Sort Code</label>
                                            <input type="text" id="sort_code" name="sort_code"
                                                placeholder="Enter Bank Sort Code" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="bank_name">Bank Name</label>
                                            <input type="text" id="bank_name" name="bank_name"
                                                placeholder="Enter Bank Name" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="bank_branch">Bank Branch</label>
                                            <input type="text" id="bank_branch" name="bank_branch"
                                                placeholder="Enter Bank Branch" class="form-control">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="other_info">Other Information</label>
                                            <textarea id="other_info" name="other_info" cols="30" rows="4" class="form-control"></textarea>
                                        </div>

                                        <div class="holidays-section-edit">
                                            <h5>Employee Holidays</h5>
                                            <div id="editholiday-rows">
                                                <!-- Holidays load here -->
                                            </div>
                                            <button type="button" id="editHolidayRow" class="btn btn-sm btn-primary my-3">+ Add Holiday</button>
                                        </div>

                                    </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light border me-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editEmployeeBtn" class="btn btn-primary" form="edit_employee_form">Update
                            Employee</button>
                    </div>
                </div>
            </div>
        </div>
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
                                                <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:</h6>
                                                <ul class="mb-0 small">
                                                    <li>Headers should be in Row 1 starting from Column A</li>
                                                    <li>Data should start from Row 2, Column A onwards</li>
                                                    <li><strong>Required:</strong> Full Name</li>
                                                    <li><strong>Optional:</strong> Date of Registration, Subcontractor, Pay Rate, Contact, SIA Number, Service Type, SIA Expiry, DOB, Email, Username, Address with Post Code, Address Group, Account Name, Sort Code, Account Number, NI Number, Visa Status, Visa Expiry Date</li>
                                                    <li>Full Name will be split into First and Last name automatically</li>
                                                    <li>If Subcontractor name is provided but doesn't exist, a new subcontractor will be created</li>
                                                    <li><strong>User Account Creation:</strong> If Username is provided, a user account will be created with default password "password123". Username must be a valid email address.</li>
                                                    <li>Date formats supported: "02-Aug-24", "02-Aug-2024", standard date formats</li>
                                                    <li>Remaining SIA/VISA days are calculated automatically if expiry dates are provided</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="import_file" class="form-control" required accept=".xlsx,.xls,.csv">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('employees.export.excel', ['template' => 1]) }}" class="btn btn-outline-primary w-100">
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

        $('.visa_type').on('change', function () {
            const form = $(this).closest('form');
            const showTerms = $(this).val() === 'Student';

            form.find('.terms-section, .terms-section-edit').each(function () {
                $(this).toggle(showTerms);
            });

            form.find('#term-rows, #editterm-rows').each(function () {
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
                        $('#add_employee').modal('hide');
                        $('#success_message').html('Employee Added Successfully');
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]).removeClass('d-none');
                            });

                            // scroll to the first error visible
                            $('#add_employee .modal-body').scrollTop(
                                $('#add_employee .form-error:visible').first().siblings('label').offset().top - $('#add_employee .modal-body').offset().top + $('#add_employee .modal-body').scrollTop()
                            );
                        } else {
                            alert('An error occurred. Please try again.');
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
                        $('#edit_employee').modal('hide');
                        $('#success_message').html('Employee Updated Successfully');
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]).removeClass('d-none');
                            });

                            // scroll to the first error visible
                            $('#edit_employee .modal-body').scrollTop(
                                $('#edit_employee .form-error:visible').first().siblings('label').offset().top - $('#edit_employee .modal-body').offset().top + $('#edit_employee .modal-body').scrollTop()
                            );
                        } else {
                            alert('An error occurred. Please try again.');
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
                        $('#generate_payroll').modal('hide');
                        $('#success_message').html('Payroll Created Successfully!')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#payrollerror_' + key).text(value[0]);
                            });
                        } else {
                            alert('An error occurred. Please try again.');
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
                    $('#payroll_employee_id').val(data.employee.id);
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
            $.get(`${baseUrl}/editemployee/`+record_id, function(data) {
                if (data.employee) {
                    $('#employee_id').val(data.employee.id);
                    $('#username').val(data.employee.username)
                    $('#status').val(data.employee.status)
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
                    $('#license_expiry').val(data.employee.license_expiry);
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

                    // Clear previous holidays
                    $('#editholiday-rows').empty();
                    $('#editterm-rows').empty();

                    if (data.holidays && data.holidays.length > 0) {
                        data.holidays.forEach((holiday, index) => {
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
                                </div>
                            `;
                            $('#editholiday-rows').append(holidayRow);
                        });
                    }

                    if(data.employee.visa_type == 'Student')
                    {
                        $('.terms-section-edit').show();
                        if (data.terms && data.terms.length > 0) {
                            data.terms.forEach((term, index) => {
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
                                    </div>
                                `;
                                $('#editterm-rows').append(termRow);
                            });
                        }
                    }

                    $('#edit_employee').modal('show');
                }
            })
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
                        $('#delete_modal').modal('hide');

                        $('#success_message').html('Employee Deleted Successfully');
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
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
                alert('Please select at least one client to delete.');
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
                    $('#success_message').text('Selected employees deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
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
                    <input type="text" name="holidays[${editholiday}][term_name]" class="form-control">
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
                $('#address_group_detail').text(data.address_group);
                $('#guard_rate_detail').text(`$${data.guard_rate}`);
                $('#bank_info_detail').text(`${data.bank_name} / ${data.account_name} / ${data.account_number}`);
                $('#other_info_detail').text(data.other_info);

                let modal = new bootstrap.Modal(document.getElementById('viewEmployeeDetailModal'));
                modal.show();
            }).fail(function() {
                alert('Failed to fetch employee detail.');
            });
        }
    </script>

@endsection
