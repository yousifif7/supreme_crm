<!DOCTYPE html>
<html lang="en">



<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="Smarthr - Bootstrap Admin Template">
    <meta name="keywords" content="admin, estimates, bootstrap, business, html5, responsive, Projects">
    <meta name="author" content="Dreams technologies - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/sp_logo.png">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/sp_logo.png">

    <!-- Theme Script js -->
    <script src="assets/js/theme-script.js"></script>

    <!-- Preload Critical CSS -->
    <link rel="preload" href="assets/css/bootstrap.min.css" as="style">
    <link rel="preload" href="assets/css/style.css" as="style">

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Feather CSS -->

    <link rel="stylesheet" href="assets/plugins/icons/feather/feather.css">
    <!-- Dragula CSS -->
    <!-- Vendor CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/plugins/icons/feather/feather.css">
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.css">
    <link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="assets/plugins/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="assets/plugins/@simonwep/pickr/themes/nano.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr JS -->

    <!-- Defer Theme Script -->
    <script src="assets/js/theme-script.js" defer></script>

    @yield('styles')
</head>

<body>
    <div id="global-loader" style="display: none;">
        <div class="page-loader"></div>
    </div>


    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <div class="header">
            <div class="main-header">

                <div class="header-left">
                    <a href="dashboard.html" class="logo">
                        <img src="assets/img/website-1.png" alt="Logo">
                    </a>
                    <a href="dashboard.html" class="dark-logo">
                        <img src="assets/img/website-1.png" alt="Logo">
                    </a>
                </div>

                <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                    <span class="bar-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </a>

                <div class="header-user">
                    <div class="nav user-menu nav-list">

                        <div class="me-auto d-flex align-items-center" id="header-search">
                            <a id="toggle_btn" href="dashboard.html" class="btn btn-menubar me-1">
                                <i class="ti ti-arrow-bar-to-left"></i>
                            </a>
                            <!-- Search -->
                            <div class="input-group input-group-flat d-inline-flex me-1">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Search...">

                            </div>
                            <!-- /Search -->


                        </div>

                        <!-- Horizontal Single -->

                        <!-- /Horizontal Single -->

                        <div class="d-flex align-items-center">


                            <div class="me-1 notification_item">
                                <a href="#" class="btn btn-menubar position-relative me-1" id="notification_popup"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-bell"></i>
                                    <span class="notification-status-dot"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-4">
                                    <div
                                        class="d-flex align-items-center justify-content-between border-bottom p-0 pb-3 mb-3">
                                        <h4 class="notification-title">Notifications (2)</h4>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="text-primary fs-15 me-3 lh-1">Mark all as
                                                read</a>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                    <i class="ti ti-calendar-due me-1"></i>Today
                                                </a>
                                                <ul class="dropdown-menu mt-2 p-3">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                            This Week
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                            Last Week
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                            Last Month
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="noti-content">
                                        <div class="d-flex flex-column">
                                            <div class="border-bottom mb-3 pb-3">
                                                <a href="https://smarthr.co.in/demo/html/template/activity.html">
                                                    <div class="d-flex">
                                                        <span class="avatar avatar-lg me-2 flex-shrink-0">
                                                            <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-27.jpg"
                                                                alt="Profile">
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-1"><span
                                                                    class="text-dark fw-semibold">Shawn</span>
                                                                performance in Math is below the threshold.</p>
                                                            <span>Just Now</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="border-bottom mb-3 pb-3">
                                                <a href="https://smarthr.co.in/demo/html/template/activity.html"
                                                    class="pb-0">
                                                    <div class="d-flex">
                                                        <span class="avatar avatar-lg me-2 flex-shrink-0">
                                                            <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-23.jpg"
                                                                alt="Profile">
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-1"><span
                                                                    class="text-dark fw-semibold">Sylvia</span> added
                                                                appointment on 02:00 PM</p>
                                                            <span>10 mins ago</span>
                                                            <div
                                                                class="d-flex justify-content-start align-items-center mt-1">
                                                                <span class="btn btn-light btn-sm me-2">Deny</span>
                                                                <span class="btn btn-primary btn-sm">Approve</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="border-bottom mb-3 pb-3">
                                                <a href="https://smarthr.co.in/demo/html/template/activity.html">
                                                    <div class="d-flex">
                                                        <span class="avatar avatar-lg me-2 flex-shrink-0">
                                                            <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-25.jpg"
                                                                alt="Profile">
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-1">New student record <span
                                                                    class="text-dark fw-semibold"> George</span>
                                                                is created by <span
                                                                    class="text-dark fw-semibold">Teressa</span>
                                                            </p>
                                                            <span>2 hrs ago</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="border-0 mb-3 pb-0">
                                                <a href="https://smarthr.co.in/demo/html/template/activity.html">
                                                    <div class="d-flex">
                                                        <span class="avatar avatar-lg me-2 flex-shrink-0">
                                                            <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-01.jpg"
                                                                alt="Profile">
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-1">A new teacher record for <span
                                                                    class="text-dark fw-semibold">Elisa</span> </p>
                                                            <span>09:45 AM</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex p-0">
                                        <a href="#" class="btn btn-light w-100 me-2">Cancel</a>
                                        <a href="#" class="btn btn-primary w-100">View All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown profile-dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
                                    data-bs-toggle="dropdown">
                                    <span class="avatar avatar-sm online">
                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-12.jpg"
                                            alt="Img" class="img-fluid rounded-circle">
                                    </span>
                                </a>
                                <div class="dropdown-menu shadow-none">
                                    <div class="card mb-0">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-lg me-2 avatar-rounded">
                                                    <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-12.jpg"
                                                        alt="img">
                                                </span>
                                                <div>
                                                    <h5 class="mb-0">Voags.
                                                    </h5>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">

                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="#">
                                                <i class="ti ti-settings me-1"></i>Settings
                                            </a>


                                        </div>
                                        <div class="card-footer py-1">
                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="login-3.html"><i class="ti ti-login me-2"></i>Logout</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div class="dropdown mobile-user-menu">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="https://smarthr.co.in/demo/html/template/profile.html">My
                            Profile</a>
                        <a class="dropdown-item"
                            href="https://smarthr.co.in/demo/html/template/profile-settings.html">Settings</a>
                        <a class="dropdown-item" href="https://smarthr.co.in/demo/html/template/login.html">Logout</a>
                    </div>
                </div>
                <!-- /Mobile Menu -->

            </div>

        </div>
        <!-- /Header -->

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="sidebar-logo">
                <a href="dashboard.html" class="logo logo-normal">
                    <img src="assets/sp_logo.png" alt="Logo">
                </a>
                <a href="dashboard.html" class="logo-small">
                    <img src="assets/sp_logo.png" alt="Logo">
                </a>
                <a href="dashboard.html" class="dark-logo">
                    <img src="assets/sp_logo.png" alt="Logo">
                </a>
            </div>
            <!-- /Logo -->
            <div class="modern-profile p-3 pb-0">
                <div class="text-center rounded bg-light p-3 mb-4 user-profile">
                    <div class="avatar avatar-lg online mb-3">
                        <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-02.jpg"
                            alt="Img" class="img-fluid rounded-circle">
                    </div>
                    <h6 class="fs-12 fw-normal mb-1">Adrian Herman</h6>
                    <p class="fs-10">System Admin</p>
                </div>
                <div class="sidebar-nav mb-3">
                    <ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent"
                        role="tablist">
                        <li class="nav-item"><a class="nav-link active border-0" href="#">Menu</a></li>
                        <li class="nav-item"><a class="nav-link border-0"
                                href="https://smarthr.co.in/demo/html/template/chat.html">Chats</a></li>
                        <li class="nav-item"><a class="nav-link border-0"
                                href="https://smarthr.co.in/demo/html/template/email.html">Inbox</a></li>
                    </ul>
                </div>
            </div>
            <div class="sidebar-header p-3 pb-0 pt-2">
                <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile d-flex align-items-center">
                    <div class="avatar avatar-md onlin">
                        <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-02.jpg"
                            alt="Img" class="img-fluid rounded-circle">
                    </div>
                    <div class="text-start sidebar-profile-info ms-2">
                        <h6 class="fs-12 fw-normal mb-1">Adrian Herman</h6>
                        <p class="fs-10">System Admin</p>
                    </div>
                </div>
                <div class="input-group input-group-flat d-inline-flex mb-4">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Search in HRMS">
                    <span class="input-group-text">
                        <kbd>CTRL + / </kbd>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between menu-item mb-3">
                    <div class="me-3">
                        <a href="https://smarthr.co.in/demo/html/template/calendar.html" class="btn btn-menubar">
                            <i class="ti ti-layout-grid-remove"></i>
                        </a>
                    </div>
                    <div class="me-3">
                        <a href="https://smarthr.co.in/demo/html/template/chat.html"
                            class="btn btn-menubar position-relative">
                            <i class="ti ti-brand-hipchat"></i>
                            <span
                                class="badge bg-info rounded-pill d-flex align-items-center justify-content-center header-badge">5</span>
                        </a>
                    </div>
                    <div class="me-3 notification-item">
                        <a href="https://smarthr.co.in/demo/html/template/activity.html"
                            class="btn btn-menubar position-relative me-1">
                            <i class="ti ti-bell"></i>
                            <span class="notification-status-dot"></span>
                        </a>
                    </div>
                    <div class="me-0">
                        <a href="https://smarthr.co.in/demo/html/template/email.html" class="btn btn-menubar">
                            <i class="ti ti-message"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>

                        <li>
                            <ul>
                                <li class="menu-title"><span>MAIN MENU</span></li>
                                <li class="{{ request()->is('dashboard*') ? 'active' : '' }}">
                                    <a href="{{ url('dashboard') }}">
                                        <i class="ti ti-layout-dashboard"></i><span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="submenu {{ request()->is('shifts') ? 'open' : '' }}">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-shield-half-filled"></i><span>Security Board</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="{{ request()->is('scheduling') ? 'active' : '' }}"><a
                                                href="{{ url('scheduling') }}"><i
                                                    class="ti ti-calendar-plus"></i>Scheduling</a></li>
                                        <li><a href="{{ url('worker_calendar') }}"><i
                                                    class="ti ti-calendar-month"></i>Worker Calendar</a></li>
                                        <li><a href="{{ url('site_calendar') }}"><i
                                                    class="ti ti-calendar-time"></i>Site Calendar</a></li>
                                        <li><a href="{{ url('today_rota') }}"><i
                                                    class="ti ti-calendar-time"></i>Today's Rota</a></li>
                                        <li><a href="{{ url('shifts') }}"><i class="ti ti-calendar-time"></i>Manage
                                                Shift</a></li>


                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-users"></i><span>User Management</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="{{ route('users.index') }}"><i class="ti ti-users"></i>All
                                                Users</a></li>
                                        <li><a href="{{ route('roles.index') }}"><i class="ti ti-accessible"></i>All
                                                Roles</a></li>
                                        <!--
                                        <li><a href="total_hours.html"><i class="ti ti-clock-24"></i>Total Hours</a>
                                        </li>
                                        <li><a href="holidays.html"><i class="ti ti-helicopter-landing"></i>Holidays
                                            </a></li>-->


                                    </ul>
                                </li>
                                <li
                                    class="submenu {{ request()->is('clients') || request()->is('sites') ? 'open' : '' }}">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-heartbeat"></i><span>Clients</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="{{ request()->is('clients') ? 'active' : '' }}">
                                            <a href="{{ url('clients') }}"><i class="ti ti-heartbeat"></i>Clients</a>
                                        </li>
                                        <li class="{{ request()->is('sites') ? 'active' : '' }}">
                                            <a href="{{ url('sites') }}"><i class="ti ti-world-pin"></i>Sites</a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="{{ request()->is('employees*') ? 'active' : '' }}">
                                    <a href="{{ url('employees') }}">
                                        <i class="ti ti-users-group"></i><span>Security Staff</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="Service-partner.html">
                                        <i class="ti ti-user"></i><span>Service Partners</span>
                                    </a>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-search"></i><span>Compliance</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="Worker-Anomalies.html"><i class="ti ti-reorder"></i>Worker
                                                Anomalies</a></li>
                                        <li><a href="Partner-Anomalies.html"><i class="ti ti-waterpolo"></i>Partner
                                                Anomalies</a></li>

                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-tool"></i><span>Tools</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="Invoices-payments.html"><i
                                                    class="ti ti-file-invoice"></i>Invoices & Payments</a></li>
                                        <li><a href="Pay-mgt.html"><i class="ti ti-cash-register"></i>Pay Mgt.</a>
                                        </li>
                                        <li><a href="Holiday-Mgt.html"><i class="ti ti-door-exit"></i>Holiday Mgt.</a>
                                        </li>
                                        <li><a href="Timesheet-Report.html"><i
                                                    class="ti ti-calendar-stats"></i>Timesheet Report</a></li>
                                        <li><a href="RIO-Report.html"><i class="ti ti-checkup-list"></i>RIO Report</a>
                                        </li>



                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-file"></i><span>Reports</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="report.html"><i class="ti ti-settings"></i>Guard Job Sheet</a>
                                        </li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Shift Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Book on Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Salary Sheet</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>HR Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Staff List</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Staff Profile</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Staff Holidays</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>SIA Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Documents Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Site Schedule</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Site Roster</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Shift Summary Logs</a>
                                        </li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Client Report</a></li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Roster Schedule Data</a>
                                        </li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Check Point Report</a>
                                        </li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Performance Status</a>
                                        </li>
                                        <li><a href="#"><i class="ti ti-settings"></i>Performance Summary</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>


                    </ul>
                    </li>
                    </ul>
                    </li>
                    </ul>
                </div>
            </div>
        </div>




        <!-- Page Wrapper -->
        @yield('contents')
        <!-- /Page Wrapper -->

    </div>
    <!-- /Main Wrapper -->

    <!-- jQuery -->
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


    <!-- Core JS Libraries -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js" defer></script>
    <script src="assets/js/feather.min.js" defer></script>
    <script src="assets/js/jquery.slimscroll.min.js" defer></script>

    <!-- Plugins -->
    <script src="assets/plugins/daterangepicker/daterangepicker.js" defer></script>

    <!-- ApexCharts (Corrected) -->
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/apexchart/apexcharts.min.js" defer></script>
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/apexchart/chart-data.js" defer></script>

    <!-- Other Plugins -->
    <script src="assets/plugins/@simonwep/pickr/pickr.es5.min.js" defer></script>
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/fullcalendar/index.global.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

    <!-- Moment + Datetimepicker -->
    <script src="assets/js/moment.js" defer></script>
    <script src="assets/js/bootstrap-datetimepicker.min.js" defer></script>

    <!-- Custom Scripts -->
    <script src="assets/js/theme-colorpicker.js" defer></script>
    <script src="assets/js/script.js" defer></script>
    <script>
        document.querySelector('.add-multiple-shifts_btn').addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = document.querySelector('.shift-wrapper');
            const group = wrapper.querySelector('.shift-group');
            const clone = group.cloneNode(true);
            wrapper.appendChild(clone);
        });
    </script>
    <script>
        window.addEventListener('load', function() {
            if (typeof L === 'undefined') {
                alert('Leaflet (L) is still undefined! Check CDN.');
                return;
            }

            var map = L.map('map').setView([51.505, -0.09], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);




        });
        window.addEventListener('load', function() {
            if (typeof L === 'undefined') {
                alert('Leaflet (L) is still undefined! Check CDN.');
                return;
            }

            var map = L.map('map1').setView([51.505, -0.09], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);




        });
    </script>
    <script>
        if (localStorage.getItem("successNotification")) {
            document.querySelector(".alert-box-container").innerHTML = localStorage.getItem("successNotification");
            localStorage.removeItem("successNotification");
        }


        document.getElementById("edit_worker-form")?.addEventListener("submit", function(event) {
            event.preventDefault();

            const successMessage = `
  <div class="alert alert-solid-success alert-dismissible fade show">
    <strong>Success!</strong> The worker has been successfully updated in the system.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      <i class="fas fa-xmark"></i>
    </button>
  </div>
`;

            localStorage.setItem("successNotification", successMessage);
            window.location.href = "Workers-all-workers.html";
        });


        document.getElementById("add_worker-form")?.addEventListener("submit", function(event) {
            event.preventDefault();

            const successMessage = `
  <div class="alert alert-solid-success alert-dismissible fade show">
    <strong>Success!</strong> A new worker has been successfully added to the system.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      <i class="fas fa-xmark"></i>
    </button>
  </div>
`;

            localStorage.setItem("successNotification", successMessage);
            window.location.href = "Workers-all-workers.html";
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.addEventListener('load', function() {
            if (typeof L === 'undefined') {
                alert('Leaflet (L) is still undefined! Check CDN.');
                return;
            }

            var map = L.map('map').setView([51.505, -0.09], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);


            var redZone = L.circle([51.505, -0.09], {
                color: '',
                fillColor: '#f03',
                fillOpacity: 0.5,
                radius: 700
            }).addTo(map);

            redZone.bindPopup("Red Zone Area");
        });
    </script>
    @yield('scripts')

</body>


</html>
