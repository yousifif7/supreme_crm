@extends('layouts.app')
@section('title', 'Shifts')
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper security_board">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shifts</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
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
                    <div class="sort-box">
                        <select name="" id="" class="form-control">
                            <option value="" hidden>Sort Shift</option>
                            <option value="">All</option>
                            <option value="">Coordinators</option>
                            <option value="">Archieved</option>
                        </select>
                        <i class="ti ti-chevron-down"></i>
                    </div>

                </div>


            </div>
            <!-- /Breadcrumb -->

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>

                                    <th>#</th>
                                    <th>Client Name</th>
                                    <th>Site Name</th>
                                    <th>Staff Name</th>
                                    <th>Shift Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Break Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shifts as $shift)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $shift->shift->client->client_name }}</td>
                                        <td>{{ $shift->shift->site->site_name }}</td>
                                        @if (!empty($shift->shift->staff->fore_name))
                                            <td>{{ $shift->shift->staff->fore_name }}</td>
                                        @else
                                            <td>No staff</td>
                                        @endif
                                        <td>{{ $shift->shift_date }}</td>
                                        <td>{{ $shift->start_time }}</td>
                                        <td>{{ $shift->end_time }}</td>
                                        <td>{{ $shift->break_time }}</td>
                                        <td>

                                            @php
                                                $statusMap = [
                                                    0 => ['label' => 'Pending', 'color' => 'secondary'],
                                                    1 => ['label' => 'Dispatched', 'color' => 'info'],
                                                    2 => ['label' => 'Accepted', 'color' => 'primary'],
                                                    3 => ['label' => 'Started', 'color' => 'warning'],
                                                    4 => ['label' => 'Ended', 'color' => 'success'],
                                                    5 => ['label' => 'Rejected', 'color' => 'danger'],
                                                    6 => ['label' => 'Canceled', 'color' => 'dark'],
                                                    7 => ['label' => 'Pre-Shift', 'color' => 'light'],
                                                    8 => ['label' => 'Await-Finish', 'color' => 'purple'], // use a custom class if needed
                                                ];

                                                $status = $statusMap[$shift->shift->is_assign] ?? [
                                                    'label' => 'Unknown',
                                                    'color' => 'secondary',
                                                ];
                                            @endphp

                                            <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                                        </td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <button onclick="window.location='#'"
                                                    class="sites_action-btn">Logs</button>
                                                <a href="#" class="me-2"
                                                    onclick="editShift({{ $shift->id }})"><i class="ti ti-edit"></i></a>
                                                <a onclick="deleteShift({{ $shift->id }})"><i
                                                        class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $shifts->links('vendor.pagination.bootstrap-5') }}
                        </div>
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
                                                        <label class="form-label">Staff <span
                                                                class="text-danger">*</span></label>
                                                        <select name="client_id" id="client_id"
                                                            class="form-select select2" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($staffs as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->fore_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
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
                                                </div>
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
                                                        <input type="time" name="end_shift" id="end_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Book off <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end_shift" id="end_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status <span
                                                                class="text-danger">*</span></label>
                                                        <select name="client_id" id="client_id"
                                                            class="form-select select2" required>
                                                            <option value="">--choose--</option>
                                                            <option value="">Pending</option>
                                                            <option value="">Dispatched</option>
                                                            <option value="">Accepted</option>
                                                            <option value="">Rejected</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
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
        </div>
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
                            alert('You must have at least one shift.');
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
                        $('#add_shift').modal('hide');
                        $('#success_message').html('Shift Added Successfully');
                        $('#success_modal').modal('show');

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
                            alert('An error occurred. Please try again.');
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
                let shiftId = $('#shift_id').val();

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
                        $('#edit_shift').modal('hide');
                        $('#success_message').html('Shifts Updated Successfully!')
                        $('#success_modal').modal('show');
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
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });
        });

        function editShift(record_id) {
            $.get('/editshift/' + record_id, function(data) {
                if (data.shift) {
                    $('#shift_id').val(data.shift.id);
                    $('#client_id').val(data.shift.client_id);
                    $('#site_id').val(data.shift.site_id);
                    $('#company_id').val(data.shift.company_id);
                    $('#start_shift').val(data.shift.start_shift);
                    $('#end_shift').val(data.shift.end_shift);
                    $('#break-mins_shift').val(data.shift['break-mins_shift']);
                    $('#number_shift').val(data.shift.number_shift);
                    $('#site_rate').val(data.shift.site_rate);
                    $('#service_type_1').val(data.shift.service_type_1);
                    $('#service_type_2').val(data.shift.service_type_2);
                    $('#comments').val(data.shift.comments);
                    $('#from_shift').val(data.shift.from_shift);
                    $('#to_shift').val(data.shift.to_shift);

                    // ✅ Staff and rates
                    $('#staff_id').val(data.shift.staff_id);
                    $('#employee_rate').val(data.shift.employee_rate);
                    $('#start').val(data.shift.start);
                    $('#end').val(data.shift.end);
                    $('#po_number').val(data.shift.po_number);
                    $('#lost_time').val(data.shift.lost_time);
                    $('#po_rate').val(data.shift.po_rate);
                    $('#manager_1_id').val(data.shift.manager_1_id);
                    $('#manager_2_id').val(data.shift.manager_2_id);
                    $('#subcontractor_id').val(data.shift.subcontractor_id);

                    // ✅ Checkboxes
                    $('#restrict_start_time').prop('checked', data.shift.restrict_start_time == 1);
                    $('#enforce_picture_check').prop('checked', data.shift.enforce_picture_check == 1);
                    $('#restrict_location_check').prop('checked', data.shift.restrict_location_check == 1);

                    // ✅ Days Handling
                    let daysStr = data.shift.days || '[]';
                    try {
                        // Parse JSON string (["Mon, Tue"])
                        let parsedDays = JSON.parse(daysStr);

                        if (Array.isArray(parsedDays) && parsedDays.length > 0) {
                            daysStr = parsedDays[0]; // "Mon, Tue"
                        }

                        const selectedDays = daysStr.split(',').map(d => d.trim()); // ['Mon', 'Tue']

                        // Reset day-box selections
                        $('#edit_shift .day-box').removeClass('selected');

                        // Highlight selected days
                        selectedDays.forEach(day => {
                            $(`#edit_shift .day-box[data-day="${day}"]`).addClass('selected');
                        });

                        // Update hidden input value
                        $('#edit_shift .selectedDays').val(selectedDays.join(','));
                    } catch (e) {
                        console.error('Invalid days format:', e);
                    }

                    // ✅ Show Modal
                    $('#edit_shift').modal('show');
                }
            });
        }
    </script>

    <script>
        // Sidebar Menu
        $('.submenu > a').click(function(e) {
            e.preventDefault();
            var $this = $(this);
            var $submenu = $this.next('ul');

            if (!$this.hasClass('subdrop')) {
                $('.submenu > a').removeClass('subdrop');
                $('.submenu ul').slideUp(200);
                $this.addClass('subdrop');
                $submenu.slideDown(200);
            } else {
                $this.removeClass('subdrop');
                $submenu.slideUp(200);
            }
        });

        var currentPage = window.location.pathname.split("/").pop();
        $('#sidebar-menu a').each(function() {
            var linkPage = $(this).attr('href');
            if (linkPage === currentPage) {
                $(this).addClass('active');
                var $submenu = $(this).closest('.submenu');
                if ($submenu.length) {
                    $submenu.find('> a').addClass('subdrop');
                    $submenu.find('ul').slideDown(0).css('display', 'block');
                }
            }
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
@endsection
