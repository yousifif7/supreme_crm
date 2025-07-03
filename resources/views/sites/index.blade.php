@extends('layouts.app')
@section('title', 'CRM - Sites')
@section('styles')
    <style>
        .select2-container--default .select2-selection--single {
            height: 40px;
            /* adjust as needed */
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
        }
    </style>
@endsection
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Sites</h2>

                    @if (session('success'))
                        <div class="alert alert-success mt-3">
                            {{ session('success') }}
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

                </div>

            </div>
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
                                <a href="{{ route('sites.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('sites.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_site"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Site
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
                        {{ $dataTable->setTableHeadClass('thead-light')->table(['class' => 'table datatable']) }}
                    </div>
                </div>
            </div>
        </div>



    </div>
    <!-- /Page Wrapper -->
    <!-- Add Client -->
    <div class="modal fade" id="add_site">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Site</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form method="POST" id="add_site-form">
                    @csrf
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                            tabindex="0">
                            <div class="modal-body pb-0 ">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Client Name <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select select2 select-client" id="clientSelect" name="client_id"
                                                    style="height: 100px !important;">
                                                    <option value="">--choose--</option>
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}">{{ $client->client_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_client_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="site_name" class="form-control"
                                                    placeholder="Enter Site Name">
                                                <span class="text-danger form-error" id="error_site_name"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Address <span
                                                        class="text-danger">*</span></label>
                                                <textarea class="form-control" name="address" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="error_address"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Post Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="post_code" class="form-control"
                                                    placeholder="Enter Post Code">
                                                <span class="text-danger form-error" id="error_post_code"></span>


                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="site_code" class="form-control"
                                                    placeholder="Enter Site Code">
                                                <span class="text-danger form-error" id="error_site_code"></span>


                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Person <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="contact_person" class="form-control"
                                                    placeholder="Enter Contact Person">
                                                <span class="text-danger form-error" id="error_contact_person"></span>


                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="contact_number" class="form-control"
                                                    placeholder="Enter Contact Number">
                                                <span class="text-danger form-error" id="error_contact_number"></span>


                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Note <span
                                                        class="text-danger">*</span></label>
                                                <textarea class="form-control" name="note" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="error_note"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager</label>
                                                <select class="form-select" name="manager_id_1">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_manager_1_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager (2)</label>
                                                <select class="form-select" name="manager_id_2">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_manager_2_id"></span>
                                            </div>

                                        </div>
                                    </div> <!--part-1 -->
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" class="form-control" name="start_time">
                                                <span class="text-danger form-error" id="error_start_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">End Time</label>
                                                <input type="time" name="end_time" class="form-control">
                                                <span class="text-danger form-error" id="error_end_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Break Time</label>
                                                <select class="form-select" name="break_time">
                                                    <option value="" hidden>Select Break Time</option>
                                                    <option value="0">No Break</option>
                                                    <option value="15">15 Minutes</option>
                                                    <option value="30">30 Minutes</option>
                                                    <option value="45">45 Minutes</option>
                                                    <option value="60">1 Hour</option>
                                                    <option value="75">1:15 Hour</option>
                                                    <option value="90">1:30 Hours</option>
                                                    <option value="105">1:45 Hour</option>
                                                    <option value="120">2:00 Hours</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_break_time"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Guard Rate <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="guard_rate"
                                                    class="form-control numeric-input guardRate" placeholder="Guard Rate">
                                                <span class="text-danger form-error" id="error_guard_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Rate <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="office_rate"
                                                    class="form-control numeric-input siteRate" placeholder="Office Rate">
                                                <span class="text-danger form-error" id="error_office_rate"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">
                                                    Name of the Guards
                                                    <small class="text-muted">(Include additional info such as Trained
                                                        Guards, Banned Guards)</small> <span class="text-danger">*</span>
                                                </label>
                                                <textarea name="guard_names" class="form-control" rows="3" placeholder="Enter names and info of guards..."></textarea>
                                                <span class="text-danger form-error" id="error_guard_names"></span>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="billable_rate"
                                                    class="form-control numeric-input" placeholder="Billable">
                                                <span class="text-danger form-error" id="error_billable_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="payable_rate"
                                                    class="form-control numeric-input" placeholder="Payable">
                                                <span class="text-danger form-error" id="error_payable_rate"></span>
                                            </div>
                                            <div class="card bg-light-500 shadow-none">
                                                <div
                                                    class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                    <h6>Service types</h6>

                                                </div>
                                            </div>
                                            <div class="table-responsive permission-table border rounded">
                                                <table class="table">
                                                    <thead>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Guard Rate</th>
                                                        <th>Office Rate</th>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($employee_types as $type)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox"
                                                                        class="form-check-input toggle-rate"
                                                                        name="employee_types[]"
                                                                        value="{{ $type->id }}"
                                                                        data-id="{{ $type->id }}">
                                                                </td>
                                                                <td>
                                                                    <label
                                                                        class="form-check-label mt-0">{{ $type->name }}</label>
                                                                </td>
                                                                <td>
                                                                    <input type="text"
                                                                        class="form-control numeric-input guard-rate-input rate-{{ $type->id }}"
                                                                        name="employee_guard_rate[{{ $type->id }}]"
                                                                        placeholder="Guard rate" style="display: none;">
                                                                </td>
                                                                <td>
                                                                    <input type="text"
                                                                        class="form-control numeric-input office-rate-input rate-{{ $type->id }}"
                                                                        name="employee_office_rate[{{ $type->id }}]"
                                                                        placeholder="Office rate" style="display: none;">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-light border me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="add_site-form" id="savesite" class="btn btn-primary">Save
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Client -->

    <!-- Edit Client -->
    <div class="modal fade" id="edit_site">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Site</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form method="POST" id="edit_site-form">
                    @csrf
                    <input type="hidden" name="site_id" id="site_id">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                            aria-labelledby="info-tab" tabindex="0">
                            <div class="modal-body pb-0 ">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Client Name <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select select2 select-client" name="client_id"
                                                    id="client_id">
                                                    <option value="">--choose--</option>
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}">{{ $client->client_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="editerror_client_id"></span>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="site_name" id="site_name"
                                                    class="form-control" placeholder="Enter Site Name">
                                                <span class="text-danger form-error" id="editerror_site_name"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Address <span
                                                        class="text-danger">*</span></label>
                                                <textarea class="form-control" name="address" id="address" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="editerror_address"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Post Code <span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="post_code" id="post_code"
                                                    class="form-control" placeholder="Enter Post Code">
                                                <span class="text-danger form-error" id="editerror_post_code"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="site_code" id="site_code"
                                                    class="form-control" placeholder="Enter Site Code">
                                                <span class="text-danger form-error" id="editerror_site_code"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Person <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="contact_person" id="contact_person"
                                                    class="form-control" placeholder="Enter Contact person">
                                                <span class="text-danger form-error" id="editerror_contact_person"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="contact_number" id="contact_number"
                                                    class="form-control" placeholder="Enter Contact Number">
                                                <span class="text-danger form-error" id="editerror_contact_number"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Note <span
                                                        class="text-danger">*</span></label>
                                                <textarea class="form-control" name="note" id="note" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="editerror_note"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager</label>
                                                <select class="form-select" name="manager_1_id" id="manager_1_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="editerror_manager_1_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager (2)</label>
                                                <select class="form-select" name="manager_2_id" id="manager_2_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="editerror_manager_2_id"></span>
                                            </div>

                                        </div>
                                    </div> <!--part-1 -->
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" class="form-control" name="start_time"
                                                    id="start_time">
                                                <span class="text-danger form-error" id="editerror_start_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">End Time</label>
                                                <input type="time" name="end_time" id="end_time"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="editerror_end_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Break Time</label>
                                                <select class="form-select" name="break_time" id="break_time">
                                                    <option value="" hidden>Select Break Time</option>
                                                    <option value="0">No Break</option>
                                                    <option value="15">15 Minutes</option>
                                                    <option value="30">30 Minutes</option>
                                                    <option value="45">45 Minutes</option>
                                                    <option value="60">1 Hour</option>
                                                    <option value="75">1:15 Hour</option>
                                                    <option value="90">1:30 Hours</option>
                                                    <option value="105">1:45 Hour</option>
                                                    <option value="120">2:00 Hours</option>
                                                </select>
                                                <span class="text-danger form-error" id="editerror_break_time"></span>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Guard Rate <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="guard_rate" id="guard_rate"
                                                    class="form-control" placeholder="Guard Rate">
                                                <span class="text-danger form-error" id="editerror_guard_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Office Rate <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="office_rate" id="office_rate"
                                                    class="form-control" placeholder="Office Rate">
                                                <span class="text-danger form-error" id="editerror_office_rate"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">
                                                    Name of the Guards
                                                    <small class="text-muted">(Include additional info such as Trained
                                                        Guards, Banned Guards)</small> <span class="text-danger">*</span>
                                                </label>
                                                <textarea name="guard_names" id="guard_names" class="form-control" rows="3"
                                                    placeholder="Enter names and info of guards..."></textarea>
                                                <span class="text-danger form-error" id="editerror_guard_names"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="billable_rate" id="billable_rate"
                                                    class="form-control numeric-input" placeholder="Billable">
                                                <span class="text-danger form-error" id="editerror_billable_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="payable_rate" id="payable_rate"
                                                    class="form-control numeric-input" placeholder="Payable">
                                                <span class="text-danger form-error" id="editerror_payable_rate"></span>
                                            </div>
                                            <div class="card bg-light-500 shadow-none">
                                                <div
                                                    class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                    <h6>Services types</h6>

                                                </div>
                                            </div>
                                            <div class="table-responsive permission-table border rounded">
                                                <table class="table">
                                                    <thead>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Guard Rate</th>
                                                        <th>Office Rate</th>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($employee_types as $type)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input"
                                                                        name="employee_types[]"
                                                                        value="{{ $type->id }}">
                                                                </td>
                                                                <td>
                                                                    <div class="form-check form-check-md form-switch me-2">
                                                                        <label class="form-check-label mt-0">
                                                                            {{ $type->name }}
                                                                        </label>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div
                                                                        class="form-check form-check-md d-flex align-items-center">
                                                                        <label class="form-check-label mt-0">
                                                                            <input type="text"
                                                                                class="form-controlnumeric-input"
                                                                                name="employee_guard_rate[{{ $type->id }}]"
                                                                                placeholder="Guard rate">
                                                                        </label>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div
                                                                        class="form-check form-check-md d-flex align-items-center">
                                                                        <label class="form-check-label mt-0">
                                                                            <input type="text"
                                                                                class="form-control numeric-input"
                                                                                name="employee_office_rate[{{ $type->id }}]"
                                                                                placeholder="Office rate">
                                                                        </label>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-light border me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="edit_site-form" id="editsite"
                                    class="btn btn-primary">Update
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Client -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Confirm Delete</h4>
                    <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.</p>
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
                    <h4 class="modal-title">Import Sites</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{ route('sites.import') }}" method="POST" enctype="multipart/form-data">
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
                                                <li>Row 1 and Column A should be left empty</li>
                                                <li>Row 2 starting from Column B should contain headers</li>
                                                <li>Data should start from Row 3, Column B onwards</li>
                                                <li><strong>Required:</strong> Site Name</li>
                                                <li><strong>Optional:</strong> Client Name, Address, Site Code, Post Code, Guard Names, Contact Number, Contact Person, Note, Start Time, End Time, Break Time, Guard Rate, Office Rate, Billable Rate, Payable Rate</li>
                                                <li>If Client Name is provided, it must exist in the clients database</li>
                                                <li>The system will automatically find the client and assign its ID when Client Name is provided</li>
                                                <li>Time fields should be in HH:MM format (e.g., 08:00, 18:30)</li>
                                                <li>Rate fields should be numeric values</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="d-flex gap-2">
                                            <input type="file" name="import_file" class="form-control" required accept=".xlsx,.xls,.csv">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="{{ route('sites.export.excel', ['template' => 1]) }}" class="btn btn-outline-primary w-100">
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
    <!-- Logs Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Site Logs Detail
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
    <!-- View Site Detail Modal -->
    <div class="modal fade" id="viewSiteDetailModal" tabindex="-1" aria-labelledby="siteDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="siteDetailLabel">
                        Site <span id="site_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Site Name</th>
                                <td id="site_name_detail"></td>
                            </tr>
                            <tr>
                                <th>Guard Names</th>
                                <td id="guard_names_detail"></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td id="address_detail"></td>
                            </tr>
                            <tr>
                                <th>Post Code</th>
                                <td id="post_code_detail"></td>
                            </tr>
                            <tr>
                                <th>Site Code</th>
                                <td id="site_code_detail"></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td id="contact_number_detail"></td>
                            </tr>
                            <tr>
                                <th>Contact Person</th>
                                <td id="contact_person_detail"></td>
                            </tr>
                            <tr>
                                <th>Note</th>
                                <td id="note_detail"></td>
                            </tr>
                            <tr>
                                <th>Start Time</th>
                                <td id="start_time_detail"></td>
                            </tr>
                            <tr>
                                <th>End Time</th>
                                <td id="end_time_detail"></td>
                            </tr>
                            <tr>
                                <th>Break Time</th>
                                <td id="break_time_detail"></td>
                            </tr>
                            <tr>
                                <th>Guard Rate</th>
                                <td id="guard_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Office Rate</th>
                                <td id="office_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Billable Rate</th>
                                <td id="billable_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Payable Rate</th>
                                <td id="payable_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Manager 1</th>
                                <td id="manager_1_detail"></td>
                            </tr>
                            <tr>
                                <th>Manager 2</th>
                                <td id="manager_2_detail"></td>
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
        $(document).ready(function() {
            $('.select-client').select2({
                width: '100%',
            });
        });
        $(document).ready(function() {

            $(document).on("change","#clientSelect",function() {
                var $this = $(this);
                const clientId = $(this).val();

                if (!clientId) return;

                $.ajax({
                    url: `${baseUrl}/api/client/${clientId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        $('.guardRate').val(data.client.guard_rate || '');
                        $('.siteRate').val(data.client.office_rate || '');
                    },
                    error: function (xhr, status, error) {
                        console.error('Fetch error:', error);
                    }
                });
            });

            $('#add_site-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#savesite'); // Add an ID to your submit button

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
                        closeBsModal('#add_site');
                        toast_success('Sites Added Successfully')
                        reloadDatatable('#sites-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
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
            $('#edit_site-form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='editerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editsite'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let siteId = $('#site_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatesite/${siteId}`, // OR use Laravel Blade: `{{ url('sites') }}/` + siteId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_site');
                        toast_success('Sites Updated Successfully!');
                        reloadDatatable('#sites-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]);
                            });
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

        });

        function editSite(record_id) {
            $.get(`${baseUrl}/editsite/` + record_id, function(data) {
                if (data.site) {
                    $('#site_id').val(data.site.id);
                    $('#client_id').val(data.site.client_id);
                    $('#site_name').val(data.site.site_name);
                    $('#site_group').val(data.site.site_group);
                    $('#address').val(data.site.address);
                    $('#post_code').val(data.site.post_code);
                    $('#site_code').val(data.site.site_code);
                    $('#contact_number').val(data.site.contact_number);
                    $('#note').val(data.site.note);
                    $('#manager_1_id').val(data.site.manager_1_id);
                    $('#manager_2_id').val(data.site.manager_2_id);
                    $('#start_time').val(data.site.start_time);
                    $('#end_time').val(data.site.end_time);
                    $('#break_time').val(data.site.break_time);
                    $('#guard_rate').val(data.site.guard_rate);
                    $('#office_rate').val(data.site.office_rate);
                    $('#billable_rate').val(data.site.billable_rate);
                    $('#payable_rate').val(data.site.payable_rate);

                    // ✅ Handle employee types
                    if (data.employee_types) {
                        data.employee_types.forEach(type => {
                            // Assuming checkbox: name="employee_types[]" value="type.id"
                            $(`input[name="employee_types[]"][value="${type.id}"]`).prop('checked', true);

                            // Guard Rate field: name="employee_guard_rate[type.id]"
                            $(`input[name="employee_guard_rate[${type.id}]"]`).val(type.guard_rate);

                            // Office Rate field: name="employee_office_rate[type.id]"`
                        $(`input[name="employee_office_rate[${type.id}]"]`).val(type.office_rate);
                    });
                }

                $('#edit_site').modal('show');
            }
        });
    }

    function viewSiteDetail(id) {
        $.get(`${baseUrl}/sites/${id}/view`, function(data) {
            $('#site_name_heading').text(data.site_name);
            $('#site_name_detail').text(data.site_name);
            $('#guard_names_detail').text(data.guard_names);
            $('#address_detail').text(data.address);
            $('#post_code_detail').text(data.post_code);
            $('#site_code_detail').text(data.site_code);
            $('#contact_number_detail').text(data.contact_number);
            $('#contact_person_detail').text(data.contact_person);
            $('#note_detail').text(data.note);
            $('#start_time_detail').text(data.start_time);
            $('#end_time_detail').text(data.end_time);
            $('#break_time_detail').text(data.break_time);
            $('#guard_rate_detail').text(`$${data.guard_rate}`);
            $('#office_rate_detail').text(`$${data.office_rate}`);
            $('#billable_rate_detail').text(`$${data.billable_rate}`);
            $('#payable_rate_detail').text(`$${data.payable_rate}`);
            $('#manager_1_detail').text(data.manager_1_name ?? '');
            $('#manager_2_detail').text(data.manager_2_name ?? '');

            let modal = new bootstrap.Modal(document.getElementById('viewSiteDetailModal'));
            modal.show();
        }).fail(function() {
            alert('Failed to fetch site detail.');
        });
    }


    let selectedId = null;

    function deleteSite(record_id) {
        selectedId = record_id;
        $('#delete_modal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (selectedId !== null) {
            $.ajax({
                url: `${baseUrl}/deletesite/${selectedId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    closeBsModal('#delete_modal');

                    toast_success('Site Deleted Successfully!')
                    reloadDatatable('#sites-table');
                },
                error: function(xhr) {
                    closeBsModal('#delete_modal');
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
            alert('Please select at least one site to delete.');
            return;
        }

        if (!confirm('Are you sure you want to delete the selected sites?')) return;

        $.ajax({
            url: '{{ route('sites.bulkDelete') }}',
            type: 'POST',
            data: {
                ids: selected,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toast_success('Selected sites deleted successfully!');
                reloadDatatable('#sites-table');
            },
            error: function() {
                alert('Something went wrong during bulk delete.');
            }
        });
    });

    function viewLogs(siteId) {
        // Clear existing content
        const modalBody = document.querySelector('#logModal .modal-body');
        modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

        fetch(`${baseUrl}/sites/${siteId}/logs/ajax`)
            .then(response => response.json())
            .then(data => {
                if (data.logs.length === 0) {
                    modalBody.innerHTML = '<p class="text-muted">No logs found for this site.</p>';
                } else {
                    let html = '<table class="table table-bordered table-striped">';
                    html +=
                        '<thead><tr><th>User</th><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>';
                    data.logs.forEach(log => {
                        html +=
                            `<tr>
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
    </script>
    <script>
        document.querySelectorAll('.toggle-rate').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const id = this.dataset.id;
                const guardInput = document.querySelector(`.guard-rate-input.rate-${id}`);
                const officeInput = document.querySelector(`.office-rate-input.rate-${id}`);

                if (this.checked) {
                    guardInput.style.display = 'block';
                    officeInput.style.display = 'block';
                } else {
                    guardInput.style.display = 'none';
                    officeInput.style.display = 'none';
                }
            });
        });
        document.querySelectorAll('.numeric-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');

                // Optional: Only allow one decimal point
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts[1];
                }
            });
        });
    </script>

    {!! $dataTable->scripts() !!}
@endsection
