@extends('layouts.app')
@section('title', 'SP CRM')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Dashboard</h2>

                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
                    <div class="me-2 mb-2">
                        <div class="dropdown">
                            <a href="javascript:void(0);"
                                class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                                data-bs-toggle="dropdown">
                                <i class="ti ti-file-export me-1"></i>Export
                            </a>
                            <ul class="dropdown-menu  dropdown-menu-end p-3">
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
                    <div class="input-icon mb-2 position-relative">
                        <span class="input-icon-addon">
                            <i class="ti ti-calendar text-gray-9"></i>
                        </span>
                        <input type="text" class="form-control date-range bookingrange"
                            placeholder="dd/mm/yyyy - dd/mm/yyyy">
                    </div>

                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-primary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Total Wokers</p>
                                    <h5>2400</h5>
                                </div>
                            </div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 40%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-danger fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>-4.01% </span> from last week</p>
                            <span class="position-absolute top-0 end-0"><img
                                    src="https://smarthr.co.in/demo/html/template/assets/img/bg/card-bg-04.png"
                                    alt="Img"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-secondary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-heartbeat text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Total Clients</p>
                                    <h5>15000</h5>
                                </div>
                            </div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 40%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>+20.01% </span> from last week</p>
                            <span class="position-absolute top-0 end-0"><img
                                    src="https://smarthr.co.in/demo/html/template/assets/img/bg/card-bg-04.png"
                                    alt="Img"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-danger flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-stairs-up text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">No of Invoice Sent</p>
                                    <h5>30</h5>
                                </div>
                            </div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-pink" role="progressbar" style="width: 40%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>+55% </span> from last week</p>
                            <span class="position-absolute top-0 end-0"><img
                                    src="https://smarthr.co.in/demo/html/template/assets/img/bg/card-bg-04.png"
                                    alt="Img"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-purple flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users-group text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Pending Interviews</p>
                                    <h5>10</h5>
                                </div>
                            </div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-purple" role="progressbar" style="width: 40%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>+55% </span> from last week</p>
                            <span class="position-absolute top-0 end-0"><img
                                    src="https://smarthr.co.in/demo/html/template/assets/img/bg/card-bg-04.png"
                                    alt="Img"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Live Tracking</div>
                        </div>
                        <div class="card-body">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                            <h5 class="mb-2">Today Shifts (Live)</h5>

                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0">
                                    <thead>
                                        <tr class="text-center">
                                            <th>TIME</th>
                                            <th>PERSON</th>
                                            <th>IN</th>
                                            <th>BREAK</th>
                                            <th>OUT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>9:00 AM</td>
                                            <td>John Doe</td>
                                            <td>✔</td>
                                            <td>12:00 PM</td>
                                            <td>5:00 PM</td>
                                        </tr>
                                        <tr>
                                            <td>9:30 AM</td>
                                            <td>Jane Smith</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>5:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:00 AM</td>
                                            <td>Mike Johnson</td>
                                            <td>✔</td>
                                            <td>12:30 PM</td>
                                            <td>6:00 PM</td>
                                        </tr>
                                        <tr>
                                            <td>8:45 AM</td>
                                            <td>Sara Lee</td>
                                            <td>✔</td>
                                            <td>12:15 PM</td>
                                            <td>4:45 PM</td>
                                        </tr>
                                        <tr>
                                            <td>9:15 AM</td>
                                            <td>Chris Brown</td>
                                            <td>✔</td>
                                            <td>1:30 PM</td>
                                            <td>5:15 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                        <tr>
                                            <td>10:30 AM</td>
                                            <td>Emma Wilson</td>
                                            <td>✔</td>
                                            <td>1:00 PM</td>
                                            <td>6:30 PM</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                            <h5 class="mb-2">Action Required</h5>

                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0 action_require-table">

                                    <tbody>
                                        <tr class="required">
                                            <td>0</td>
                                            <td>Need Approval</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>22</td>
                                            <td>Check points</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>6</td>
                                            <td>Awaiting acceptance</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>20</td>
                                            <td>Awaiting Start</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>1</td>
                                            <td>Pending Dispatch</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>12</td>
                                            <td>Shift Started</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr class="required">
                                            <td>0</td>
                                            <td>Shift Ended</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr class="required">
                                            <td>0</td>
                                            <td>Shift Rejected</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>4</td>
                                            <td>Visa Expiry</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>1000</td>
                                            <td>S.I.A Expiry</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr class="required">
                                            <td>4</td>
                                            <td>S.I.A last checked today</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>51</td>
                                            <td>S.I.A not found</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>S.I.A revoked</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td>9</td>
                                            <td>Passport expiry</td>
                                            <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                        class="ti ti-arrow-right fs-16"></i></a></td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                <h5>Upcomming Shifts</h5>

                            </div>
                        </div>
                        <div class="card-body pb-0">

                            <div id="revenue-income"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                            <h5 class="mb-2">Latest Actions</h5>

                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0 lastest_action-table">

                                    <tbody>
                                        <tr>
                                            <td>
                                                <div>
                                                    <p>43 minutes ago</p>
                                                    <span class="text-info">MUHAMMAD NASIR (SPL)</span>
                                                </div>

                                            </td>
                                            <td>
                                                <p><b>Job Accepted</b></p>
                                                <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div>
                                                    <p>3 hours ago</p>
                                                    <span class="text-info">HEENARANA</span>
                                                </div>

                                            </td>
                                            <td>
                                                <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div>
                                                    <p>3 hours ago</p>
                                                    <span class="text-info">HEENARANA</span>
                                                </div>

                                            </td>
                                            <td>
                                                <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div>
                                                    <p>3 hours ago</p>
                                                    <span class="text-info">HEENARANA</span>
                                                </div>

                                            </td>
                                            <td>
                                                <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div>
                                                    <p>3 hours ago</p>
                                                    <span class="text-info">HEENARANA</span>
                                                </div>

                                            </td>
                                            <td>
                                                <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>



    </div>
    <!-- /Page Wrapper -->
@endsection
