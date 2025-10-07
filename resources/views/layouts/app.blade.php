<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="Smarthr - Bootstrap Admin Template">
    <meta name="keywords" content="admin, estimates, bootstrap, business, html5, responsive, Projects">
    <meta name="author" content="Dreams technologies - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
    <link rel="stylesheet" href="{{ asset('assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/toast/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/toast/alerts2.css') }}">
    <!-- Defer Theme Script -->
    {{-- <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    <script src="{{ asset('assets/js/theme-script.js') }}" defer></script>
    <!-- Moment + Datetimepicker -->
    <script src="{{ asset('assets/js/moment.js') }}" defer></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet" type="text/css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
    </style>
    <script type="text/javascript">
        const baseUrl = "{{ url('/') }}";
    </script>
    @yield('styles')
</head>
<style>
    .notif-item {
        padding: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #ccc;
        transition: background-color 0.2s;
    }

    .notif-item.unread {
        background-color: #e8f1ff;
        border-left: 4px solid #0d6efd;
        font-weight: bold;
    }

    .notif-item.read {
        background-color: #ffffff;
        color: #6c757d;
    }

    #notif-list {
        scrollbar-width: thin;
        scrollbar-color: #0d6efd #f1f1f1;
    }

    #notif-list::-webkit-scrollbar {
        width: 6px;
    }

    #notif-list::-webkit-scrollbar-thumb {
        background-color: #0d6efd;
        border-radius: 3px;
    }

    #notif-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    html {
        font-size: 80%;
    }
</style>

<body style="font-size: 12px !important">
    <div id="global-loader" style="display: none;">
        <div class="page-loader"></div>
    </div>

    {{-- Sound for alerts --}}
    <audio id="alert-sound" src="/sounds/alert2.mp3" preload="auto"></audio>


    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <div class="header">
            <div class="main-header">

                <div class="header-left">
                    <a href="#" class="logo">
                        <img src="{{ asset('assets/sp_logo.png') }}" alt="Logo">
                    </a>
                    <a href="#" class="dark-logo">
                        <img src="{{ asset('assets/sp_logo.png') }}" alt="Logo">
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
                            {{-- <button id="alert-bell" class="btn btn-light btn-sm" title="Toggle alert sounds">
                                🔔
                            </button> --}}


                            <!-- Search -->
                            {{-- <div class="input-group input-group-flat d-inline-flex me-1">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Search...">

                            </div> --}}
                            <!-- /Search -->


                        </div>
                        <!-- Horizontal Single -->

                        <!-- /Horizontal Single -->
                        @php
                            $notifications = \App\Models\Notification::where('user_id', 1)
                                ->orderBy('created_at', 'desc')
                                ->get();
                        @endphp
                        <div class="d-flex align-items-center">
                            <div class="me-1 notification_item" style="padding:7px;">
                                <a href="" class="btn btn-menubar position-relative me-1" id="notificationBell"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-bell"></i>
                                    @if (!$notifications)
                                    @else
                                        <span class="text-danger">{{ $notifications->where('read', false)->count() }}
                                        </span>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-4">
                                    <!-- Hidden form for Mark All Read -->


                                    <!-- Notifications Header -->
                                    <div
                                        class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                        <h4 class="notification-title">
                                            Notifications (<span
                                                id="notif-count">{{ $notifications->where('read', false)->count() }}</span>)
                                        </h4>
                                        <form id="markAllForm" action="{{ route('notifications.markAllRead') }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit" class="text-primary fs-15 btn btn-link p-0">Mark
                                                All
                                                as Read</button>
                                        </form>
                                        {{-- <a href="#" id="mark-all-read"
                                            class="text-primary fs-15 btn btn-link p-0">Mark all as read</a> --}}
                                    </div>

                                    <!-- Scrollable Notifications List (max 5 visible at once) -->
                                    <form action="{{ route('notifications.markSelectedRead') }}" method="POST">
                                        @csrf
                                        <div id="notif-list" style="max-height: 360px; overflow-y: auto;"
                                            class="d-flex flex-column">
                                            @forelse($notifications as $notif)
                                                <div
                                                    class="notif-item border-bottom mb-3 pb-3 d-flex align-items-start {{ $notif->read ? 'read' : 'unread' }}">
                                                    @if (!$notif->read)
                                                        <input type="checkbox"
                                                            class="form-check-input me-2 notif-checkbox"
                                                            name="ids[]" value="{{ $notif->id }}">
                                                    @endif

                                                    <a href="{{ $notif->action_url ?? '#' }}"
                                                        class="notification-link flex-grow-1"
                                                        data-id="{{ $notif->id }}">
                                                        <div class="d-flex">
                                                            <div>
                                                                <p class="mb-1">
                                                                    <span
                                                                        class="fw-semibold"><b>{{ $notif->title }}:</b></span>
                                                                    {{ $notif->message }}
                                                                </p>
                                                                <span
                                                                    class="text-muted small">{{ $notif->created_at->diffForHumans() }}</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            @empty
                                                <p class="text-muted">No new notifications</p>
                                            @endforelse
                                        </div>
                                        <br>
                                        <div class="text-center">
                                            <button class="btn btn-outline-primary" type="submit">Mark selected as
                                                read</button>
                                        </div>
                                    </form>

                                    <!-- Footer buttons -->
                                    <div class="d-flex p-0 pt-3 border-top mt-3">
                                        <a href="#" class="btn btn-light w-100 me-2">Cancel</a>
                                        {{-- <a href="#" class="btn btn-primary w-100">View All</a> --}}
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
                        <a class="dropdown-item" href="#">Settings</a>
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
        let soundUnlocked = false;
document.addEventListener('click', () => {
    if (!soundUnlocked) {
        const sound = document.getElementById('alert-sound');
        sound.play().then(() => {
            sound.pause();
            sound.currentTime = 0;
            soundUnlocked = true;
            console.log('Alert sound unlocked for autoplay.');
        }).catch(e => console.warn('Sound still blocked:', e));
    }
}, { once: true });

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



    <script src="{{ asset('assets/js/theme-colorpicker.js') }}" defer></script>
    <script src="{{ asset('assets/js/script.js') }}" defer></script>
    <script src="{{ asset('assets/toast/toast.js') }}" defer></script>
    <script src="{{ asset('assets/toast/alerts6.js') }}" defer></script>
        @yield('scripts')

    @stack('scripts')
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
    <script>
        // Handling notifications
        // Fetch unread notifications count & list (limit to 5 for dropdown)
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Mark a single notification as read when clicked
            document.querySelectorAll('.notification-link').forEach(link => {
                link.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;

                    try {
                        await fetch(`/api/notifications/${id}/read`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'include'
                        });

                        window.location.href = this.href;
                    } catch (err) {
                        console.error('Failed to mark as read:', err);
                    }
                });
            });

            // 2. Mark all as read
            document.addEventListener('DOMContentLoaded', function() {
                const markAllBtn = document.getElementById('mark-all-read');
                const form = document.getElementById('markAllForm');

                if (markAllBtn && form) {
                    markAllBtn.addEventListener('click', async function(e) {
                        e.preventDefault();

                        try {
                            const formData = new FormData(form);

                            const res = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': formData.get('_token'),
                                },
                                credentials: 'same-origin',
                            });

                            if (!res.ok) throw new Error('Request failed');

                            // Update UI after marking all as read
                            document.querySelectorAll('.notif-item').forEach(el => {
                                el.classList.remove('unread');
                                el.classList.add('read');
                            });

                            document.getElementById('notif-count').textContent = 0;
                        } catch (err) {
                            console.error('❌ Mark all failed:', err);
                            alert('Failed to mark all notifications as read.');
                        }
                    });
                }
            });


            // 3. Mark selected as read
            document.getElementById('mark-selected-read')?.addEventListener('click', async function(
                e) {
                e.preventDefault();

                const checkedBoxes = document.querySelectorAll(
                    '.notif-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('No notifications selected.');
                    return;
                }

                const ids = Array.from(checkedBoxes).map(cb => cb.value);

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute(
                            'content');

                    const res = await fetch('/notifications/mark-selected-read', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            ids
                        })
                    });

                    if (!res.ok) throw new Error(
                        'Failed to mark selected notifications as read');

                    ids.forEach(id => {
                        const box = document.querySelector(
                            `.notif-checkbox[value="${id}"]`);
                        if (box) {
                            const row = box.closest('.notif-item');
                            row.classList.remove('unread');
                            row.classList.add('read');
                        }
                    });

                    const countElement = document.getElementById('notif-count');
                    countElement.textContent = Math.max(0, parseInt(countElement
                            .textContent) - ids
                        .length);
                } catch (err) {
                    console.error('Error marking selected as read:', err);
                }
            });
        });

        document.querySelectorAll('.notification-link').forEach(link => {
            link.addEventListener('click', async function(e) {
                e.preventDefault();
                const id = this.dataset.id;

                try {
                    const res = await fetch(`/api/notifications/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        credentials: 'include'
                    });

                    if (!res.ok) throw new Error('Failed to mark as read');

                    // ✅ Update UI instantly
                    const notifItem = this.closest('.notif-item');
                    if (notifItem) {
                        notifItem.classList.remove('unread');
                        notifItem.classList.add('read');
                    }

                    const countElement = document.getElementById('notif-count');
                    if (countElement) {
                        countElement.textContent = Math.max(0, parseInt(countElement.textContent) - 1);
                    }

                    // ✅ Redirect after marking as read
                    window.location.href = this.href;
                } catch (err) {
                    console.error('❌ Failed to mark as read:', err);
                    window.location.href = this.href; // fallback redirect
                }
            });
        });
    </script>

</body>

</html>
