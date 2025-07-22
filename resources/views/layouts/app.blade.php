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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/js/custom/toastr-helpers.js', 'resources/js/custom/ajax.js'])
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/sp_logo.png') }}">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/sp_logo.png') }}">

    <!-- Preload Critical CSS -->
    {{-- <link rel="preload" href="{{ asset('assets/css/bootstrap.min.css') }}" as="style"> --}}
    <link rel="preload" href="{{ asset('assets/css/style.css') }}" as="style">

    <!-- Core CSS -->
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <!-- Feather CSS -->

    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">
    <!-- Dragula CSS -->
    <!-- Vendor CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">
    <!-- Defer Theme Script -->
    {{-- <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    <script src="{{ asset('assets/js/theme-script.js') }}" defer></script>
    <!-- Moment + Datetimepicker -->
    <script src="{{ asset('assets/js/moment.js') }}" defer></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
    <script type="text/javascript">
        const baseUrl = "{{ url('/') }}";
    </script>
    @yield('styles')
</head>

<body style="font-size: 12px !important">
    <div id="global-loader" style="display: none;">
        <div class="page-loader"></div>
    </div>


    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <div class="header">
            <div class="main-header">

                <div class="header-left">
                    <a href="#" class="logo">
                        <img src="{{ asset('assets/img/website-1.png') }}" alt="Logo">
                    </a>
                    <a href="#" class="dark-logo">
                        <img src="{{ asset('assets/img/website-1.png') }}" alt="Logo">
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
                            <a id="toggle_btn" href="{{ url('dashboard') }}" class="btn btn-menubar me-1">
                                <i class="ti ti-arrow-bar-to-left"></i>
                            </a>
                            <!-- Search -->
                            {{--<div class="input-group input-group-flat d-inline-flex me-1">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Search...">

                            </div>--}}
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
                                                <a href="#">
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
                                                <a href="#"
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
                                                <a href="#">
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
                                                <a href="#">
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
                                        <img src="{{ asset('uploads/no.png') }}" alt="Img"
                                            class="img-fluid rounded-circle">
                                    </span>
                                </a>
                                <div class="dropdown-menu shadow-none">
                                    <div class="card mb-0">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-lg me-2 avatar-rounded">
                                                    <img src="{{ asset('uploads/no.png') }}" alt="img">
                                                </span>
                                                <div>
                                                    <h5 class="mb-0">{{ auth()->user()->first_name }}
                                                        {{ auth()->user()->last_name }}
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
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i
                                                        class="ti ti-login me-2"></i>Logout</button>
                                            </form>

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
                        <a class="dropdown-item" href="#">My
                            Profile</a>
                        <a class="dropdown-item"
                            href="#">Settings</a>
                        <a class="dropdown-item" href="#">Logout</a>
                    </div>
                </div>
                <!-- /Mobile Menu -->

            </div>

        </div>
        <!-- /Header -->

        <!-- Sidebar -->
        @include('layouts.sidebar')
        <!-- /Sidebar -->

        <!-- Page Wrapper -->
        @yield('contents')
        <!-- /Page Wrapper -->

        @include('_modals.global-modal')

    </div>
    <!-- /Main Wrapper -->

    <!-- jQuery -->
    <script>
        $(document).ready(function() {
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

            // Client search functionality
            $('.search_box').on('keyup', function() {
                // trigger the search of datatable
                $('.datatable').DataTable().search($(this).val()).draw();
            });

            // Select All toggle
            $('#selectAll').on('change', function() {
                $('.dT-row-checkbox').prop('checked', $(this).prop('checked'));
            });

            // toggle select all checkbox
            $(document).on('change', '.dT-row-checkbox', function() {
                if ($('.dT-row-checkbox:checked').length === $('.dT-row-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                } else {
                    $('#selectAll').prop('checked', false);
                }
            });
        });
    </script>


    <!-- Core JS Libraries -->
    {{-- <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}" defer></script> --}}
    <script src="{{ asset('assets/js/feather.min.js') }}" defer></script>
    <script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}" defer></script>

    <!-- Plugins -->
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/theia-sticky-sidebar@1.7.0/dist/theia-sticky-sidebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/theia-sticky-sidebar@1.7.0/dist/jquery.theia.sticky.js"></script>

    <!-- ApexCharts (Corrected) -->
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/apexchart/apexcharts.min.js" defer></script>
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/apexchart/chart-data.js" defer></script>

    <!-- Other Plugins -->
    <script src="{{ asset('assets/plugins/@simonwep/pickr/pickr.es5.min.js') }}" defer></script>
    <script src="https://smarthr.co.in/demo/html/template/assets/plugins/fullcalendar/index.global.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

    <!-- Custom Scripts -->
    <script src="{{ asset('assets/js/theme-colorpicker.js') }}" defer></script>
    <script src="{{ asset('assets/js/script.js') }}" defer></script>
    <script>
        const addShiftBtn = document.querySelector('.add-multiple-shifts_btn');
        if (addShiftBtn) {
            addShiftBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const wrapper = document.querySelector('.shift-wrapper');
                const group = wrapper.querySelector('.shift-group');
                const clone = group.cloneNode(true);
                wrapper.appendChild(clone);
            });
        }
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
            window.location.href = "#";
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
            window.location.href = "#";
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.addEventListener('load', function () {
            if (typeof L === 'undefined') {
                toast_danger('Leaflet (L) is still undefined! Check CDN.');
                return;
            }

            // Check if container exists before initializing
            const mapDiv = document.getElementById('map');
            if (mapDiv) {
                var map = L.map('map').setView([51.505, -0.09], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                L.circle([51.505, -0.09], {
                    fillColor: '#f03',
                    fillOpacity: 0.5,
                    radius: 700
                }).addTo(map).bindPopup("Red Zone Area");
            }

            const map1Div = document.getElementById('map1');
            if (map1Div) {
                var map1 = L.map('map1').setView([51.505, -0.09], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map1);
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
