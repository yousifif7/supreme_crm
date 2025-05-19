@extends('layouts.app')
@section('title', 'CRM - Site Calendar')
@section('styles')
    <style>
        .datepic .fc-prev-button,
        .datepic .fc-next-button {
            font-size: 12px !important;
            /* Reduce arrow icon size */
            padding: 2px 4px !important;
            /* Reduce button padding */
            height: 24px !important;
            /* Reduce button height */
            width: 30px !important;
            /* Optional: make buttons smaller square */
        }
    </style>
@endsection
@section('contents')<!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper site_calendar">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Scheduling</h2>

                </div>

            </div>


            <!-- Filter Header -->
            <div class="filters">
                <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-1">
                    <div class="left">
                        <button onclick="window.location='{{ url('scheduling') }}'">Complete Rota</button>
                        <button onclick="window.location='{{ url('worker_calendar') }}'">Worker Calendar</button>
                        <button onclick="window.location='{{ url('site_calendar') }}'" class="active">Site
                            Calendar</button>
                        <button onclick="window.location='{{ url('today_rota') }}'">Today's Rota</button>
                    </div>

                    <div class="right">
                        <div class="status-summary">
                            <div onclick="window.location='{{ url('clients') }}'" class="active-sites">&#9679; Active Sites
                                ({{ $sites->count() }})</div>
                            <div onclick="window.location='{{ url('employees') }}'" class="active-workers">&#9679; Active
                                Workers ({{ $staffs->count() }})</div>
                        </div>

                    </div>




                </div>
                <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-1">

                    <div class="left mt-4">
                        <button class="refresh_btn" onclick="window.location.reload()">
                            <i class="ti ti-reload"></i>Refresh
                        </button>
                    </div>

                    <div class="right  mt-4">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_shift"
                            class=" add_btn btn btn-white"">
                            <i class="ti ti-plus me-0"></i> Add Shift
                        </a>
                        <div class="input-group input-group-flat d-inline-flex me-1">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" class=" search_box" placeholder="Search...">


                            <!-- /Search -->


                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_rota" class=" add_btn btn btn-white"">
                            <i class="ti ti-plus me-0"></i> Rota (0)
                        </a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_client"
                            class=" day-off_btn btn btn-white"">
                            <i class="ti ti-plus me-0"></i> Day off (0)
                        </a>
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
                </div>

            </div>

            <div class="row" style="padding-right: 0px !important; padding-left: 0px !important;">

                <!-- Calendar Sidebar -->
                <div class="col-xxl-2 col-xl-3" style="padding-right: 0px !important; padding-left: 0px !important;">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="border-bottom pb-2 mb-4">
                                <div class="datepic"></div>
                            </div>

                            <!-- Event -->
                            <div class="border-bottom pb-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5>Event </h5>
                                    <a href="#" class="link-primary" data-bs-toggle="modal"
                                        data-bs-target="#add_event"><i
                                            class="ti ti-square-rounded-plus-filled fs-16"></i></a>
                                </div>
                                <p class="fs-12 mb-2">Drag and drop your event or click in the calendar</p>
                                <div id='external-events'>
                                    <div class="fc-event bg-dark-blue mb-1" data-event='{ "title": "Pending" }'
                                        data-event-classname="bg-dark-blue">
                                        Pending
                                    </div>
                                    <div class="fc-event bg-lighter mb-1" data-event='{ "title": "Dispatched" }'
                                        data-event-classname="bg-lighter">
                                        Dispatched
                                    </div>
                                    <div class="fc-event bg-dark-green mb-1" data-event='{ "title": "Accepted" }'
                                        data-event-classname="bg-dark-green">
                                        Accepted
                                    </div>
                                    <div class="fc-event bg-light-yellow mb-1" data-event='{ "title": "Started" }'
                                        data-event-classname="bg-light-yellow">
                                        Started
                                    </div>
                                    <div class="fc-event bg-light-blue mb-1" data-event='{ "title": "Ended" }'
                                        data-event-classname="bg-light-blue">
                                        Ended
                                    </div>
                                    <div class="fc-event bg-purple mb-0" data-event='{ "title": "Rejected" }'
                                        data-event-classname="bg-purple">
                                        Rejected
                                    </div>
                                    <div class="fc-event bg-red mb-0" data-event='{ "title": "Cancelled" }'
                                        data-event-classname="bg-red">
                                        Cancelled
                                    </div>
                                    <div class="fc-event bg-dark-yellow mb-0" data-event='{ "title": "Pre-Start" }'
                                        data-event-classname="bg-primary">
                                        Pre-Start
                                    </div>
                                    <div class="fc-event bg-orange mb-0" data-event='{ "title": "Await-Finish" }'
                                        data-event-classname="bg-orange">
                                        Await-Finish
                                    </div>
                                </div>

                            </div>
                            <!-- /Event -->



                        </div>
                    </div>

                </div>
                <!-- /Calendar Sidebar -->

                <div class="col-xxl-10 col-xl-9 theiaStickySidebar">
                    <div class="card border-0">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Calendar View -->


            <!-- Modal -->
            <div class="modal" id="eventModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Rota Detail</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>

                        </div>
                        <form action="clients.html">
                            <div class="tabs-parent_main">
                                <div class="tabs-parent nav nav-tabs" role="tablist">
                                    <button class="nav-link active" id="info-tab2" data-bs-toggle="tab"
                                        data-bs-target="#basic-info2" type="button" role="tab"
                                        aria-controls="basic-info2" aria-selected="true">Rota Detail</button>
                                    <button class="nav-link" id="address-tab2" data-bs-toggle="tab"
                                        data-bs-target="#address2" type="button" role="tab"
                                        aria-controls="address2" aria-selected="false">Office Validation</button>
                                    <button class="nav-link" id="progress-tab2" data-bs-toggle="tab"
                                        data-bs-target="#progress2" type="button" role="tab"
                                        aria-controls="progress2" aria-selected="false">Job Progress</button>
                                    <button class="nav-link" id="logs-tab2" data-bs-toggle="tab" data-bs-target="#logs2"
                                        type="button" role="tab" aria-controls="logs2"
                                        aria-selected="false">Logs</button>
                                </div>

                                <div class="expiry_date">
                                    <div class="form-check form-check-lg form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="switch-lg">
                                        <label class="form-check-label" for="switch-lg">
                                            Stand-downSIA Number : 1087662819100 &nbsp;&nbsp;Expiry: 05 June 2025
                                        </label>
                                    </div>
                                </div>
                            </div>


                            <div class="tab-content rota-detail_tab-content" id="myTabContent2">
                                <div class="tab-pane fade show active" id="basic-info2" role="tabpanel"
                                    aria-labelledby="info-tab2">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-6 col-12">
                                                <div class="upper-stats-box">
                                                    <div class="profile-detail">
                                                        <div class="avater">
                                                            <img src="https://th.bing.com/th/id/OIP.W6PBNPGnclmjSHcE-VbNRQHaHa?rs=1&pid=ImgDetMain"
                                                                class="profile-avater">
                                                        </div>
                                                        <div class="profile-details">
                                                            <h6>Edison Viegas</h6>
                                                            <div class="mb-1">
                                                                <i class="ti ti-phone"></i>
                                                                <span>+1 234567890</span>
                                                            </div>
                                                            <div>
                                                                <i class="ti ti-mail"></i>
                                                                <span>edison@example.com</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="partner-details">
                                                        <h6>Partner</h6>
                                                        <span>Supreme Partner...</span>
                                                    </div>
                                                </div>
                                                <div class="bottom-stats-box">
                                                    <div class="other-detail_boxes">
                                                        <div class="box">
                                                            <h6>Site Address</h6>
                                                            <span>Wembley HA9,UK</span>
                                                        </div>
                                                        <div class="box">
                                                            <h6>Date</h6>
                                                            <span>2024-11-10</span>
                                                        </div>
                                                        <div class="box">
                                                            <h6>Shift Time</h6>
                                                            <span>06:20 8:30 (hrs:12)</span>
                                                        </div>
                                                        <div class="box">
                                                            <h6>Customer</h6>
                                                            <span>Quintain</span>
                                                        </div>
                                                        <div class="box">
                                                            <h6>Site Name</h6>
                                                            <span>Wembley Park</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <div id="map-first"></div>
                                            </div>
                                            <div class="col-md-6 col-12">


                                                <div id="s-col" class="chart-set"></div>


                                            </div>
                                            <div class="col-md-6 col-12">
                                                <div id="map-second"></div>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <div class="book-on_box">
                                                    <div class="profile-detail">
                                                        <div class="avater">
                                                            <img src="https://th.bing.com/th/id/OIP.W6PBNPGnclmjSHcE-VbNRQHaHa?rs=1&pid=ImgDetMain"
                                                                class="profile-avater">
                                                        </div>
                                                        <div class="profile-details">
                                                            <h6>Book on (App Version 24.10.2.1)</h6>
                                                            <div class="mb-1">
                                                                <i class="ti ti-calendar"></i>
                                                                <span>November 10 2024 , at 06:52</span>
                                                            </div>
                                                            <div>
                                                                <i class="ti ti-map-pin"></i>
                                                                <span>Wembley Park , London , Wembley HA0 , UK</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="map-image">
                                                        <img src="https://www.ucionica.net/wp-content/uploads/2021/10/kobu-agency-FyvE6XPs5gk-unsplash-scaled.jpg"
                                                            alt="">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="book-off_box">
                                                    <div class="profile-detail">
                                                        <div class="avater">
                                                            <img src="https://th.bing.com/th/id/OIP.W6PBNPGnclmjSHcE-VbNRQHaHa?rs=1&pid=ImgDetMain"
                                                                class="profile-avater">
                                                        </div>
                                                        <div class="profile-details">
                                                            <h6>Book Off </h6>
                                                            <div class="mb-1">
                                                                <i class="ti ti-calendar"></i>
                                                                <span>November 10 2024 , at 06:52</span>
                                                            </div>
                                                            <div>
                                                                <i class="ti ti-map-pin"></i>
                                                                <span>Wembley Park , London , Wembley HA0 , UK</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="map-image">
                                                        <img src="https://www.ucionica.net/wp-content/uploads/2021/10/kobu-agency-FyvE6XPs5gk-unsplash-scaled.jpg"
                                                            alt="">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="address2" role="tabpanel"
                                    aria-labelledby="address-tab2">
                                    <div class="images-grid">
                                        <div class="parent_image-wrapper">
                                            <div class="image-wrapper">
                                                <div class="badge">Profile</div>
                                                <img src="https://th.bing.com/th/id/OIP.q5YMW7MeZkVYotwhAnFYTAAAAA?rs=1&pid=ImgDetMain"
                                                    alt="Selfie 1" />
                                            </div>
                                            <div class=" id_card_wrapper">
                                                <div class="badge">SIA CARD</div>
                                                <img src="https://th.bing.com/th/id/OIP.M1CkkE74hioAnV6m5eJNrwHaE7?rs=1&pid=ImgDetMain"
                                                    alt="SIA Card" />
                                            </div>
                                        </div>
                                        <div class="parent_image-wrapper">
                                            <div class="image-wrapper">
                                                <div class="badge">Book On</div>
                                                <img src="https://th.bing.com/th/id/OIP.Nz-E0d6scG_xdLV4U_0MhgHaLW?rs=1&pid=ImgDetMain"
                                                    alt="Selfie 2" />
                                            </div>
                                            <div class="image-wrapper">
                                                <div class="badge">Book Off</div>
                                                <img src="https://th.bing.com/th/id/OIP.Y-VLqHwqQIy2KVTXueJPGQHaHa?w=1200&h=1200&rs=1&pid=ImgDetMain"
                                                    alt="Selfie 3" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="progress2" role="tabpanel"
                                    aria-labelledby="progress-tab2">
                                    <div class="modal-body">Job Progress content goes here.</div>
                                </div>
                                <div class="tab-pane fade" id="logs2" role="tabpanel" aria-labelledby="logs-tab2">
                                    <div class="modal-body">Logs content goes here.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Rota -->

            <div class="modal fade" id="add_rota">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Add New Rota</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form action="https://smarthr.co.in/demo/html/template/companies-grid.html">
                            <div class="contact-grids-tab">
                                <ul class="nav nav-underline" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab"
                                            data-bs-target="#basic-info" type="button" role="tab"
                                            aria-selected="true">Basic Information</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="address-tab" data-bs-toggle="tab"
                                            data-bs-target="#address" type="button" role="tab"
                                            aria-selected="false">Address</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="social-profile-tab" data-bs-toggle="tab"
                                            data-bs-target="#social-profile" type="button" role="tab"
                                            aria-selected="false">Social Profiles</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="access-tab" data-bs-toggle="tab"
                                            data-bs-target="#access" type="button" role="tab"
                                            aria-selected="false">Access</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="schedule-tab" data-bs-toggle="tab"
                                            data-bs-target="#schedule" type="button" role="tab"
                                            aria-selected="false">Scheduling</button>
                                    </li>

                                </ul>
                            </div>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                    aria-labelledby="info-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div
                                                    class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">
                                                    <div
                                                        class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
                                                        <i class="ti ti-photo text-gray-2 fs-16"></i>
                                                    </div>
                                                    <div class="profile-upload">
                                                        <div class="mb-2">
                                                            <h6 class="mb-1">Upload Profile Image</h6>
                                                            <p class="fs-12">Image should be below 4 mb</p>
                                                        </div>
                                                        <div class="profile-uploader d-flex align-items-center">
                                                            <div class="drag-upload-btn btn btn-sm btn-primary me-2">
                                                                Upload
                                                                <input type="file" class="form-control image-sign"
                                                                    multiple="">
                                                            </div>
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-light btn-sm">Cancel</a>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Company Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number 2</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Fax</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Website</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Ratings <span class="text-danger">
                                                            *</span></label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-star text-gray-6"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Owner <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Hendry Milner</option>
                                                        <option>Guilory Berggren</option>
                                                        <option>Jami Carlile</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Tags <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input-tags form-control" placeholder="Add new"
                                                        type="text" data-role="tagsinput" name="Label"
                                                        value="Collab">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label class="col-form-label p-0">Deals <span
                                                                class="text-danger">*</span></label>
                                                        <a href="#" class="add-new text-primary"
                                                            data-bs-target="#add_deals" data-bs-toggle="modal"><i
                                                                class="ti ti-plus text-primary me-1"></i>Add New</a>
                                                    </div>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Collins</option>
                                                        <option>Konopelski</option>
                                                        <option>Adams</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Industry <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Retail Industry</option>
                                                        <option>Banking</option>
                                                        <option>Hotels</option>
                                                        <option>Financial Services</option>
                                                        <option>Insurance</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Source <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Phone Calls</option>
                                                        <option>Social Media</option>
                                                        <option>Refferal Sites</option>
                                                        <option>Web Analytics</option>
                                                        <option>Previous Purchase</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Currency <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>USD</option>
                                                        <option>Euro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Language <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>English</option>
                                                        <option>Arabic</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-3 ">
                                                    <label class="form-label">About <span
                                                            class="text-danger">*</span></label>
                                                    <textarea class="form-control"></textarea>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Address <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Country <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>USA</option>
                                                        <option>Canada</option>
                                                        <option>Germany</option>
                                                        <option>France</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">State <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>California</option>
                                                        <option>New York</option>
                                                        <option>Texas</option>
                                                        <option>Florida</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">City <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Los Angeles</option>
                                                        <option>San Diego</option>
                                                        <option>Fresno</option>
                                                        <option>San Francisco</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Zipcode <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="social-profile" role="tabpanel"
                                    aria-labelledby="social-profile-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Facebook</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Twitter</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">LinkedIn</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Skype</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Whatsapp</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Instagram</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="access" role="tabpanel" aria-labelledby="access-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="mb-4">
                                            <h6 class="fs-14 fw-medium mb-1">Visibility</h6>
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault01">
                                                    <label class="form-check-label text-dark" for="flexRadioDefault01">
                                                        Public
                                                    </label>
                                                </div>
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault02" checked>
                                                    <label class="form-check-label text-dark" for="flexRadioDefault02">
                                                        Private
                                                    </label>
                                                </div>
                                                <div class="form-check ">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault03" checked>
                                                    <label class="form-check-label text-dark" for="flexRadioDefault03">
                                                        Select People
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-gray br-5 mb-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-06">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-01.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Michael Walker</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-07">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-02.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Sophie Headrick</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-08">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-03.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Cameron Drake</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-09">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-04.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Doris Crowley</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-11">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-12.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Thomas Bordelon</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <a href="#" class="btn btn-primary">Confirm</a>
                                            </div>
                                        </div>
                                        <div class="mb-3 ">
                                            <label class="form-label">Status</label>
                                            <select class="form-select">
                                                <option>Select</option>
                                                <option>Active</option>
                                                <option>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#success_compay">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="access-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Name</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Date</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control datetimepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-calendar text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Time</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Time</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Location</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label">Descriptions</label>
                                                    <textarea class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#success_compay">Save </button>
                                    </div>
                                </div>

                            </div>


                        </form>
                    </div>
                </div>
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
                                                            <select name="client_id[]" class="form-select select2"
                                                                required>
                                                                <option value="">--choose--</option>
                                                                @foreach ($clients as $client)
                                                                    <option value="{{ $client->id }}">
                                                                        {{ $client->client_name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <span class="text-danger form-error"
                                                                id="error_client_id"></span>
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
                                                            <span class="text-danger form-error"
                                                                id="error_site_id"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Parent company <span
                                                                    class="text-danger">*</span></label>
                                                            <select name="company_id[]" class="form-select">
                                                                <option value="">--choose--</option>
                                                            </select>
                                                            <span class="text-danger form-error"
                                                                id="error_company_id"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Start <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="time" name="start_shift[]"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_start_shift"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">End <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="time" name="end_shift[]"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_end_shift"></span>
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
                                                            <input type="text" name="number_shift[]"
                                                                placeholder="number" class="form-control">
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
                                                            <span class="text-danger form-error"
                                                                id="error_site_rate"></span>
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
                                                            <input type="date" name="from_shift[]"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_from_shift"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">To <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="date" name="to_shift[]" class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_to_shift"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Comment <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" name="comments[]" placeholder="Comment"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_comments"></span>
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
                                                            <span class="text-danger form-error"
                                                                id="error_staff_id"></span>
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
                                                            <input type="text" name="po_number[]"
                                                                placeholder="PO Number" class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_po_number"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Lost Time <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" name="lost_time[]"
                                                                placeholder="Lost Time" class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_lost_time"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">PO Rate <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" name="po_rate[]" placeholder="PO Rate"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="error_po_rate"></span>
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
                                                                    location check <span
                                                                        class="text-danger">*</span></label>
                                                            </div>
                                                            <div class="col-md-12 text-end">
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm remove-shift">Remove</button>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <button type="button"
                                                            class="btn btn-success btn-sm addShiftGroup">+
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
            <!-- /Breadcrumb -->


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
                                        <a href="{{ url('site_calendar') }}" class="btn btn-dark w-100">Back to List</a>
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

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveshift'); // Add an ID to your submit button

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
                        $('#add_shift').modal('hide');
                        $('#success_message').html('Shift Added Successfully')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        console.log("Status:", xhr.status);
                        console.log("Response:", xhr.responseText); // Helpful for debugging

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else if (xhr.responseJSON?.error) {
                            alert(xhr.responseJSON.error); //
                        } else {
                            alert('An unexpected error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
        });
    </script>
    <!-- Inline Scripts after libraries load -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Map Setup
            const london = [51.5074, -0.1278];
            const oxford = [51.7520, -1.2577];

            const map1 = L.map('map-first').setView(london, 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map1);
            const route = L.polyline([london, oxford], {
                color: 'darkblue',
                weight: 5
            }).addTo(map1);
            map1.fitBounds(route.getBounds());

            const map2 = L.map('map-second').setView(london, 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map2);

        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const colorMap = {
                'bg-dark-blue': '#5489C4',
                'bg-lighter': '#D6D4CE',
                'bg-dark-green': '#69CF83',
                'bg-light-yellow': '#FAD66B',
                'bg-light-blue': '#80BFFF',
                'bg-purple': '#9F87F5',
                'bg-red': '#F55B7C',
                'bg-primary11': '#FFFF5E',
                'bg-orange': '#F5B25F',
                'bg-secondary': '#6c757d'
            };

            fetch('/api/shifts-by-site')
                .then(response => response.json())
                .then(data => {
                    const highlightDates = data.highlightDates;

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: new Date().toISOString().split('T')[0],
                        timeZone: 'local',
                        eventDisplay: 'block',
                        displayEventEnd: true,

                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,dayGridWeek,dayGridDay'
                        },

                        events: data.events,

                        dayCellClassNames: function(arg) {
                            const dateStr = arg.date.toLocaleDateString('en-CA');
                            return highlightDates.includes(dateStr) ? ['highlight-day'] : [];
                        },

                        eventContent: function(info) {
                            const event = info.event;
                            const startTime = event.start?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';
                            const endTime = event.end?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';

                            // ✅ Get bgColor from event.classNames[0]
                            const bgClass = event.classNames?.[0] || 'bg-secondary';
                            const bgColor = colorMap[bgClass] || colorMap['bg-secondary'];

                            const container = document.createElement('div');
                            container.style.backgroundColor = bgColor;
                            container.style.padding = '5px';
                            container.style.borderRadius = '6px';
                            container.style.fontSize = '12px';
                            container.style.color = (bgColor === '#F8F9FA') ? '#000' : '#000';

                            container.innerHTML = `
                        <b>${event.title}</b><br>
                        ${startTime} - ${endTime}
                    `;

                            return {
                                domNodes: [container]
                            };
                        },

                        eventDidMount: function(info) {
                            info.el.style.overflow = 'visible';
                        }
                    });

                    calendar.render();

                    // Sidebar Mini Calendar
                    const sidebarEl = document.querySelector('.datepic');
                    if (sidebarEl) {
                        const sidebarCal = document.createElement('div');
                        sidebarEl.appendChild(sidebarCal);

                        new FullCalendar.Calendar(sidebarCal, {
                            initialView: 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev',
                                center: 'title',
                                right: 'next'
                            },
                            selectable: true,
                            dateClick: function(info) {
                                calendar.gotoDate(info.dateStr);
                            },
                            height: 'auto',
                            initialDate: new Date().toISOString().split('T')[0],
                            timeZone: 'local'
                        }).render();
                    }
                });
        });
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
    </script>
@endsection
