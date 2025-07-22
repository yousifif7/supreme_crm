@extends('layouts.app')
@section('title', 'Shifts')
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper security_board">
        <div class="content">
            <div class="alert-box-container"></div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {!! session('warning') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
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
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shifts</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary me-2" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('shifts.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('shifts.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_shift"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Shift
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

            <!-- /Breadcrumb -->
        </div>
        <!-- Add shift -->
        <div class="modal fade" id="add_shift">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Shift</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="add_shift-form" action="{{ route('shifts.store') }}">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group border rounded p-3 mb-3">
                                            <div class="row">

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Client <span
                                                                class="text-danger">*</span></label>
                                                        <select name="client_id[]" class="form-select select2" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($clients as $client)
                                                                <option value="{{ $client->id }}">
                                                                    {{ $client->client_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site <span
                                                                class="text-danger">*</span></label>
                                                        <select name="site_id[]" class="form-select">
                                                            <option value="">--choose--</option>
                                                            @foreach ($sites as $site)
                                                                <option value="{{ $site->id }}">
                                                                    {{ $site->site_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_site_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Parent company <span
                                                                class="text-danger">*</span></label>
                                                        <select name="company_id[]" class="form-select">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start_shift[]" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_start_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end_shift[]" class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Break (mins) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="break-mins_shift[]"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_break-mins_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Staff <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="number_shift[]" placeholder="number"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_number_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="site_rate[]" placeholder="$"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_site_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Service Type <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="service_type_1[]">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_service_type_1"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">From <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="from_shift[]" class="form-control">
                                                        <span class="text-danger form-error" id="error_from_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">To <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="to_shift[]" class="form-control">
                                                        <span class="text-danger form-error" id="error_to_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Comment <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="comments[]" placeholder="Comment"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_comments"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <label class="form-label">Select Days</label>
                                                    <div class="day-selector d-flex gap-2 flex-wrap">
                                                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                                            <div class="day-box" data-day="{{ $day }}">
                                                                {{ $day }} <span class="checkmark">✔</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <input type="hidden" name="days[]" id="selectedDays">
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Staff <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="staff_id[]">
                                                            <option value="">--choose--</option>
                                                            @foreach ($staffs as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->fore_name }}
                                                                    {{ $staff->sur_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_staff_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Employee Rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="employee_rate[]" placeholder="$"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_employee_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Service Type <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="service_type_2[]">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_select_type_2"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start[]" class="form-control">
                                                        <span class="text-danger form-error" id="error_start"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Subcontractor <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="subcontractor_id[]">
                                                            <option value="">--choose--</option>

                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_subcontractor_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end[]" class="form-control">
                                                        <span class="text-danger form-error" id="error_end"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">PO Number <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="po_number[]" placeholder="PO Number"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_po_number"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Lost Time <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="lost_time[]" placeholder="Lost Time"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_lost_time"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">PO Rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="po_rate[]" placeholder="PO Rate"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_po_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Manager (1) <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="manager_1_id[]">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_manager_1_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Manager (2) <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="manager_2_id[]">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_manager_2_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="restrict_start_time[]" value="1">
                                                            <label class="form-label mb-0">Restrict shift start time
                                                                <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="enforce_picture_check[]" value="1">
                                                            <label class="form-label mb-0">Enforce picture check <span
                                                                    class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="restrict_location_check[]" value="1">
                                                            <label class="form-label mb-0">Restrict start shift
                                                                location check <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-12 text-end">
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm remove-shift">Remove</button>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <button type="button" class="btn btn-success btn-sm addShiftGroup">+
                                                        Add More Shifts</button>

                                                </div>

                                            </div> <!-- .row -->
                                        </div> <!-- .shift-group -->
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_shift-form" id="saveshift"
                                        class="btn btn-primary">Save </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit Shift -->
        <div class="modal fade" id="edit_shift">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Shift</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_shift-form">
                        @csrf
                        <input type="hidden" name="shift_id" id="shift_id">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                {{--<div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Client <span
                                                                class="text-danger">*</span></label>
                                                        <select name="client_id" id="client_id"
                                                            class="form-select select2" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($clients as $client)
                                                                <option value="{{ $client->id }}">
                                                                    {{ $client->client_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>--}}
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Staff <span
                                                                class="text-danger">*</span></label>
                                                        <select name="staff_id"
                                                            class="form-select select2_modal" id="staff_id" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($staffs as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->fore_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>

                                                {{--<div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site <span
                                                                class="text-danger">*</span></label>
                                                        <select name="site_id" id="site_id" class="form-select select2"
                                                            readonly>
                                                            <option value="">--choose--</option>
                                                            @foreach ($sites as $site)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $site->site_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_site_id"></span>
                                                    </div>
                                                </div>--}}
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shift Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="shift_date" id="shift_date"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_shift_date_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start_shift" id="start_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_start_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end_shift" id="end_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Book on <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="book_on" id="book_on"
                                                            class="form-control" value="{{ date('h:i') }}">
                                                        <span class="text-danger form-error" id="error_book_on"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Book off <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="book_off" id="book_off"
                                                            class="form-control" value="{{ date('h:i') }}">
                                                        <span class="text-danger form-error" id="error_book_off"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status </label>
                                                        <select name="status_id" id="status_id"
                                                            class="form-select select2_modal">
                                                            <option value="">--choose--</option>
                                                            <option value="Pending">Pending</option>
                                                            <option value="Dispatched">Dispatched</option>
                                                            <option value="Accepted">Accepted</option>
                                                            <option value="Rejected">Rejected</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_status_id"></span>
                                                    </div>
                                                </div>
                                            </div> <!-- .row -->
                                        </div> <!-- .shift-group -->
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_shift-form" id="editshift"
                                        class="btn btn-primary">Update </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit Shift -->
        <div class="modal fade" id="edit_all_shift">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Shift</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_all_shift-form">
                        @csrf
                        <input type="hidden" name="shift_id" id="shift_id">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Client <span
                                                                class="text-danger">*</span></label>
                                                        <select name="client_id" id="client_id"
                                                            class="form-select select2" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($clients as $client)
                                                                <option value="{{ $client->id }}">
                                                                    {{ $client->client_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site <span
                                                                class="text-danger">*</span></label>
                                                        <select name="site_id" id="site_id" class="form-select">
                                                            <option value="">--choose--</option>
                                                            @foreach ($sites as $site)
                                                                <option value="{{ $site->id }}">
                                                                    {{ $site->site_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_site_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Parent company <span
                                                                class="text-danger">*</span></label>
                                                        <select name="company_id" id="company_id" class="form-select">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start_shift" id="start_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_start_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end_shift" id="end_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Break (mins) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="break-mins_shift"
                                                            id="break-mins_shift" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_break-mins_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Staff <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="number_shift" id="number_shift"
                                                            placeholder="number" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_number_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="site_rate" id="site_rate"
                                                            placeholder="$" class="form-control">
                                                        <span class="text-danger form-error" id="error_site_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Service Type <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="service_type_1"
                                                            id="service_type_1">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_service_type_1"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">From <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="from_shift" id="from_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_from_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">To <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="to_shift" id="to_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_to_shift"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Comment <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="comments" id="comments"
                                                            placeholder="Comment" class="form-control">
                                                        <span class="text-danger form-error" id="error_comments"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <label class="form-label">Select Days</label>
                                                    <div class="day-selector d-flex gap-2 flex-wrap">
                                                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                                            <div class="day-box" data-day="{{ $day }}">
                                                                {{ $day }} <span class="checkmark">✔</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <input type="hidden" name="days[]" class="selectedDays">
                                                </div>


                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Staff <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="staff_id" id="staff_id">
                                                            <option value="">--choose--</option>
                                                            @foreach ($staffs as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->fore_name }}
                                                                    {{ $staff->sur_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_staff_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Employee Rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="employee_rate" id="employee_rate"
                                                            placeholder="$" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_employee_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Service Type <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="service_type_2"
                                                            id="service_type_2">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_select_type_2"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start" id="start"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_start"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Subcontractor <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="subcontractor_id"
                                                            id="subcontractor_id">
                                                            <option value="">--choose--</option>

                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_subcontractor_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end" id="end"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">PO Number <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="po_number" id="po_number"
                                                            placeholder="PO Number" class="form-control">
                                                        <span class="text-danger form-error" id="error_po_number"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Lost Time <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="lost_time" id="lost_time"
                                                            placeholder="Lost Time" class="form-control">
                                                        <span class="text-danger form-error" id="error_lost_time"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">PO Rate <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="po_rate" id="po_rate"
                                                            placeholder="PO Rate" class="form-control">
                                                        <span class="text-danger form-error" id="error_po_rate"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Manager (1) <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="manager_1_id"
                                                            id="manager_1_id">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_manager_1_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Manager (2) <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="manager_2_id"
                                                            id="manager_2_id">
                                                            <option value="">--choose--</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_manager_2_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="restrict_start_time" id="restrict_start_time"
                                                                value="1">
                                                            <label class="form-label mb-0">Restrict shift start time
                                                                <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="enforce_picture_check" id="enforce_picture_check"
                                                                value="1">
                                                            <label class="form-label mb-0">Enforce picture check <span
                                                                    class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                            <input type="checkbox" class="form-check"
                                                                name="restrict_location_check"
                                                                id="restrict_location_check" value="1">
                                                            <label class="form-label mb-0">Restrict start shift
                                                                location check <span class="text-danger">*</span></label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <a href="#" class="add-multiple-shifts_btn"><i
                                                            class="ti ti-plus"></i> More Shifts</a>
                                                </div>

                                            </div> <!-- .row -->
                                        </div> <!-- .shift-group -->
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_shift-form" id="editshift"
                                        class="btn btn-primary">Update </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>--}}

        <!-- Import modal -->
        <div class="modal fade" id="import_modal">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Import Shifts</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('shifts.import') }}" method="POST" enctype="multipart/form-data">
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
                                                    <li><strong>Required:</strong> Date, Client, Site, Start, End</li>
                                                    <li><strong>Optional:</strong> #, Day, Officer, Phone, Lost Time, Hours, Comments</li>
                                                    <li><strong>Date format:</strong> 01-May-2025, 2025-05-01, 01/05/2025</li>
                                                    <li><strong>Time format:</strong> 06:00, 18:00, 6:00, 18:00</li>
                                                    <li>Client and Site names must exist in the database</li>
                                                    <li>Officer names are matched against employee records (first name, last name, or full name)</li>
                                                    <li>Hours will be calculated automatically if not provided</li>
                                                    <li>If Officer is assigned, SIA license expiry and overlapping shifts will be checked</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('shifts.export.excel', ['template' => 1]) }}" class="btn btn-outline-primary w-100">
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

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 20%; min-width: 20%;">
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

        <!-- Add Shift Success -->
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
                                        <a href="{{ url('shifts') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assign Shift Modal -->
        @include('security_boards.assign-shift-modal')

    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initDaySelector(shiftGroup) {
                const dayBoxes = shiftGroup.querySelectorAll('.day-box');
                const hiddenInput = shiftGroup.querySelector('input[name="days[]"]');

                dayBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        box.classList.toggle('selected');
                        const selected = Array.from(shiftGroup.querySelectorAll(
                                '.day-box.selected'))
                            .map(el => el.getAttribute('data-day'));

                        hiddenInput.value = selected.join(',');
                    });
                });
            }

            function bindEvents() {
                // Add Shift Button
                document.querySelectorAll('.addShiftGroup').forEach(btn => {
                    btn.onclick = function() {
                        const wrapper = document.querySelector('.shift-wrapper');
                        const lastGroup = wrapper.querySelector('.shift-group:last-of-type');
                        const clone = lastGroup.cloneNode(true);

                        // Reset values in clone
                        clone.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        });

                        // Reset day selection
                        clone.querySelectorAll('.day-box').forEach(box => box.classList.remove(
                            'selected'));
                        clone.querySelector('input[name="days[]"]').value = '';

                        wrapper.appendChild(clone);

                        // Re-init new shift group logic
                        initDaySelector(clone);
                        bindEvents();
                    };
                });

                // Remove Shift Button
                document.querySelectorAll('.remove-shift').forEach(btn => {
                    btn.onclick = function() {
                        const shiftGroups = document.querySelectorAll('.shift-wrapper .shift-group');
                        if (shiftGroups.length > 1) {
                            btn.closest('.shift-group').remove();
                        } else {
                            toast_danger('You must have at least one shift.');
                        }
                    };
                });
            }

            // Initialize for first shift-group
            document.querySelectorAll('.shift-group').forEach(group => initDaySelector(group));

            // Initial binding
            bindEvents();
        });
    </script>
    <script>
        $(document).ready(function() {
            $(".select2_modal").select2({
                dropdownParent: $("#edit_shift")
            });
            
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = this;
                let formData = new FormData(form);
                let submitButton = $('#saveshift');

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                // Clear previous errors
                $('.form-error').text('');

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content') // Better practice
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        toast_success('Shift Added Successfully');
                        reloadDatatable('#shifts-table');

                        // Optional: Reset form after success
                        form.reset();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
            $('#edit_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editshift'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let shiftId = $(this).find('#shift_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updateshift/${shiftId}`, // OR use Laravel Blade: `{{ url('sites') }}/` + siteId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_shift');
                        toast_success('Shift Updated Successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
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

            // Bulk delete button
            $('#bulkDeleteBtn').on('click', function() {
                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (selected.length === 0) {
                    toast_danger('Please select at least one shift to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected shifts?')) return;

                $.ajax({
                    url: '{{ route('shifts.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected shifts deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function() {
                        toast_danger('Something went wrong during bulk delete.');
                    }
                });
            });
        });

        let selectedId = null;

        function deleteShift(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteshift/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Shift deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });

        function editShift(record_id) {
            $.get(`${baseUrl}/editshift/` + record_id, function(data) {
                if (data.shift) {
                    $('#shift_id').val(record_id);
                    $('#staff_id').val(data.shift.staff_id).trigger('change');
                    $('#shift_date').val(data.shift.shift_date);
                    
                    $('#start_shift').val(data.shift.start_time);
                    $('#end_shift').val(data.shift.end_time);

                    if(typeof data.shift.absentee_start_time != 'undefined')
                        $('#book_on').val(data.shift.absentee_start_time);
                    if(typeof data.shift.absentee_end_time != 'undefined')
                        $('#book_off').val(data.shift.absentee_end_time);


                    // ✅ Show Modal
                    $('#edit_shift').modal('show');
                }
            });
        }
    </script>

    <script>
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
