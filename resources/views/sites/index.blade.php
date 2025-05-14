@extends('layouts.app')
@section('title', 'CRM - Sites')
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Sites</h2>

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
                    <div class="sort-box">
                        <select name="" id="" class="form-control">
                            <option value="" hidden>Sort Sites</option>
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
                                    <th>Site Group</th>
                                    <th>Site Name</th>
                                    <th>Address</th>
                                    <th>Site Code</th>
                                    <th>Post Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($sites->currentPage() - 1) * $sites->perPage() + 1; @endphp
                                @foreach ($sites as $site)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <div class="d-flex align-items-center file-name-icon">
                                                <div class="ms-2">
                                                    <h6 class="fw-medium"><a
                                                            href="https://smarthr.co.in/demo/html/template/client-details.html">{{ $site->client->client_name }}</a>
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: left;">{{ $site->site_group }}</td>
                                        <td style="text-align: left;">{{ $site->site_name }}</td>
                                        <td style="text-align: left;">{{ $site->address }}</td>
                                        <td style="text-align: left;">{{ $site->site_code }}</td>
                                        <td style="text-align: left;">{{ $site->post_code }}</td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <button onclick="window.location='Clients-sites.html'"
                                                    class="sites_action-btn">AI</button>
                                                <button onclick="window.location='Clients-sites.html'"
                                                    class="sites_action-btn">Keys</button>
                                                <button onclick="window.location='visit.html'"
                                                    class="sites_action-btn">Visits</button>
                                                <button onclick="window.location='logs.html'"
                                                    class="sites_action-btn">Logs</button>
                                                <button onclick="window.location='checkpoints.html'"
                                                    class="sites_action-btn">Checkpoints</button>
                                                <a href="#" class="me-2" onclick="editSite({{ $site->id }})"><i
                                                        class="ti ti-edit"></i></a>
                                                <a onclick="deleteSite({{ $site->id }})"><i class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $sites->links('vendor.pagination.bootstrap-5') }}
                        </div>
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
                                                <label class="form-label">Client Name</label>
                                                <select class="form-select" name="client_id">
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}">{{ $client->client_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_client_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Group</label>
                                                <select class="form-select" name="site_group">
                                                    <option value="">Select group</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_site_group"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Name</label>
                                                <input type="text" name="site_name" class="form-control"
                                                    placeholder="Enter Site Name">
                                                <span class="text-danger form-error" id="error_site_name"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="error_address"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Post Code</label>
                                                <input type="text" name="post_code" class="form-control"
                                                    placeholder="Enter Post Code">
                                                <span class="text-danger form-error" id="error_post_code"></span>


                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Code</label>
                                                <input type="text" name="site_code" class="form-control"
                                                    placeholder="Enter Site Code">
                                                <span class="text-danger form-error" id="error_site_code"></span>


                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" name="contact_number" class="form-control"
                                                    placeholder="Enter Contact Number">
                                                <span class="text-danger form-error" id="error_contact_number"></span>


                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Note</label>
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
                                                <select class="form-select" name="break_time">Select Break Time</select>
                                                <span class="text-danger form-error" id="error_break_time"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Guard Rate</label>
                                                <input type="text" name="guard_rate" class="form-control"
                                                    placeholder="Guard Rate">
                                                <span class="text-danger form-error" id="error_guard_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Office Rate</label>
                                                <input type="text" name="office_rate" class="form-control"
                                                    placeholder="Office Rate">
                                                <span class="text-danger form-error" id="error_office_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses</label>
                                                <input type="text" name="billable_rate" class="form-control"
                                                    placeholder="Billable">
                                                <span class="text-danger form-error" id="error_billable_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses</label>
                                                <input type="text" name="payable_rate" class="form-control"
                                                    placeholder="Payable">
                                                <span class="text-danger form-error" id="error_payable_rate"></span>
                                            </div>
                                            <div class="card bg-light-500 shadow-none">
                                                <div
                                                    class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                    <h6>Services types</h6>

                                                </div>
                                            </div>
                                            <div class="table-responsive permission-table border rounded">
                                                <table class="table">
                                                    <tbody>
                                                        <tr class="bg-bluish">
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Name
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        Guard Rate
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        Office Rate
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Alarm Response
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Event Staff
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Keyholding
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Mobile Petrol
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Static Guards
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>


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
                                                <label class="form-label">Client Name</label>
                                                <select class="form-select" name="client_id" id="client_id">
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}">{{ $client->client_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_client_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Group</label>
                                                <select class="form-select" name="site_group" id="site_group">
                                                    <option value="">Select group</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_site_group"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Name</label>
                                                <input type="text" name="site_name" id="site_name"
                                                    class="form-control" placeholder="Enter Site Name">
                                                <span class="text-danger form-error" id="error_site_name"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" id="address" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="error_address"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Post Code</label>
                                                <input type="text" name="post_code" id="post_code"
                                                    class="form-control" placeholder="Enter Post Code">
                                                <span class="text-danger form-error" id="error_post_code"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Site Code</label>
                                                <input type="text" name="site_code" id="site_code"
                                                    class="form-control" placeholder="Enter Site Code">
                                                <span class="text-danger form-error" id="error_site_code"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" name="contact_number" id="contact_number"
                                                    class="form-control" placeholder="Enter Contact Number">
                                                <span class="text-danger form-error" id="error_contact_number"></span>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Site Note</label>
                                                <textarea class="form-control" name="note" id="note" cols="30" rows="4"></textarea>
                                                <span class="text-danger form-error" id="error_note"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager</label>
                                                <select class="form-select" name="manager_1_id" id="manager_1_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_manager_1_id"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Manager (2)</label>
                                                <select class="form-select" name="manager_2_id" id="manager_2_id">
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
                                                <input type="time" class="form-control" name="start_time"
                                                    id="start_time">
                                                <span class="text-danger form-error" id="error_start_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">End Time</label>
                                                <input type="time" name="end_time" id="end_time"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_end_time"></span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Break Time</label>
                                                <select class="form-select" name="break_time" id="break_time">Select
                                                    Break Time</select>
                                                <span class="text-danger form-error" id="error_break_time"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Guard Rate</label>
                                                <input type="text" name="guard_rate" id="guard_rate"
                                                    class="form-control" placeholder="Guard Rate">
                                                <span class="text-danger form-error" id="error_guard_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Office Rate</label>
                                                <input type="text" name="office_rate" id="office_rate"
                                                    class="form-control" placeholder="Office Rate">
                                                <span class="text-danger form-error" id="error_office_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses</label>
                                                <input type="text" name="billable_rate" id="billable_rate"
                                                    class="form-control" placeholder="Billable">
                                                <span class="text-danger form-error" id="error_billable_rate"></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expenses</label>
                                                <input type="text" name="payable_rate" id="payable_rate"
                                                    class="form-control" placeholder="Payable">
                                                <span class="text-danger form-error" id="error_payable_rate"></span>
                                            </div>
                                            <div class="card bg-light-500 shadow-none">
                                                <div
                                                    class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                    <h6>Services types</h6>

                                                </div>
                                            </div>
                                            <div class="table-responsive permission-table border rounded">
                                                <table class="table">
                                                    <tbody>
                                                        <tr class="bg-bluish">
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Name
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        Guard Rate
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        Office Rate
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Alarm Response
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Event Staff
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Keyholding
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Mobile Petrol
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input">
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-check-md form-switch me-2">
                                                                    <label class="form-check-label mt-0">

                                                                        Static Guards
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Guard rate">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-check form-check-md d-flex align-items-center">
                                                                    <label class="form-check-label mt-0">

                                                                        <input type="text" class="form-control"
                                                                            placeholder="Office rate">
                                                                    </label>
                                                                </div>
                                                            </td>

                                                        </tr>


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

    <!-- Add Client Success -->
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
                                    <a href="{{ url('sites') }}" class="btn btn-dark w-100">Back to List</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Client Success -->

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
@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#add_site-form').on('submit', function(e) {
                e.preventDefault();

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
                        $('#add_site').modal('hide');
                        $('#success_message').html('Sites Added Successfully')
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
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
            $('#edit_site-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editsite'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let siteId = $('#site_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `/updatesite/${siteId}`, // OR use Laravel Blade: `{{ url('sites') }}/` + siteId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#edit_site').modal('hide');
                        $('#success_message').html('Sites Updated Successfully!')
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

        function editSite(record_id) {
            $.get('/editsite/' + record_id, function(data) {
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

                    $('#edit_site').modal('show');
                }
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
                    url: `/deletesite/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');

                        $('#success_message').html('Site Deleted Successfully!')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {

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
        });
    </script>
@endsection
