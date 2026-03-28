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
                            $__notifUser = auth()->user();
                            if ($__notifUser && $__notifUser->hasRole('admin')) {
                                // BelongsToAdmin scope auto-filters to WHERE admin_id = auth()->id()
                                $notifications = \App\Models\Notification::orderBy('created_at', 'desc')->limit(25)->get();
                            } else {
                                // superadmin, controller, staff_leader, control_room — system notifications
                                $notifications = \App\Models\Notification::withoutGlobalScope('admin_scope')
                                    ->where('user_id', 1)
                                    ->orderBy('created_at', 'desc')->limit(25)->get();
                            }
                        @endphp
                        <div class="d-flex align-items-center">
                            @hasanyrole('superadmin|controller|staff_leader|control_room|admin')
                            <div class="me-1 notification_item" style="padding:7px;">
                                <a href="" class="btn btn-menubar position-relative me-1" id="notificationBell"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-bell"></i>
                                    @if (!$notifications)
                                    @else
                                        <span class="text-danger" >{{ $notifications->where('read', false)->count() }}
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
                                        <a href="{{ url('notifications') }}" class="btn btn-light w-100 me-2">View All</a>
                                        {{-- <a href="#" class="btn btn-primary w-100">View All</a> --}}
                                    </div>
                                </div>
                            </div>
                            @endhasanyrole
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
                                        {{-- <div class="card-body">

                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="#">
                                                <i class="ti ti-settings me-1"></i>Settings
                                            </a>

                                        </div> --}}
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
                        {{-- <a class="dropdown-item" href="#">Settings</a> --}}
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

    <!-- Profile Change Request Modal -->
    <div class="modal fade" id="profileChangeModal" tabindex="-1" aria-labelledby="profileChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileChangeModalLabel">Profile Change Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pc_request_id" value="" />
                    <div class="mb-2"><strong>Full name:</strong> <span id="pc_fullname"></span></div>
                    <div class="mb-2"><strong>Current email:</strong> <span id="pc_old_email"></span></div>
                    <div class="mb-2"><strong>Requested email:</strong> <span id="pc_new_email"></span></div>
                    <div class="mb-2 text-muted small"><strong>Requested:</strong> <span id="pc_timestamp"></span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="denyRequestBtn">Deny</button>
                    <button type="button" class="btn btn-success" id="approveRequestBtn">Approve</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Action Modal -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <p id="confirmActionText">Are you sure?</p>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmActionYesBtn">Yes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            }).catch(e => {});
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
            // Use event delegation to avoid duplicate listeners and improve performance
            // This handles dynamically added notification links too

            // 2. Mark all as read
            (function() {
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
            })();

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

        // Poll notifications API every 2 minutes and update bell + dropdown without reloading
        (function setupNotificationPoller() {
            const POLL_INTERVAL_MS = 15000; // 15 seconds

            async function fetchNotifications() {
                // Skip polling if page is hidden/minimized to save resources
                if (document.hidden) {
                    return;
                }
                
                try {
                    const res = await fetch('/notifications/json?limit=25', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!res.ok) return;
                    const data = await res.json();

                    // Expect data.notifications as array
                    const notifications = data.notifications || [];
                    const unreadCount = notifications.filter(n => !n.read).length;

                    // --- Persistent seen/toasted logic (localStorage) ---
                    const SEEN_KEY = 'seenNotificationIds_v1';
                    const TOASTED_KEY = 'toastedNotificationIds_v1';
                    // Load sets from storage safely
                    let seenArr = [];
                    let toastedArr = [];
                    try { seenArr = JSON.parse(localStorage.getItem(SEEN_KEY) || '[]'); } catch (e) { seenArr = []; }
                    try { toastedArr = JSON.parse(localStorage.getItem(TOASTED_KEY) || '[]'); } catch (e) { toastedArr = []; }
                    const seenIds = new Set(Array.isArray(seenArr) ? seenArr : []);
                    const toastedIds = new Set(Array.isArray(toastedArr) ? toastedArr : []);

                    window._activeNotificationToasts = window._activeNotificationToasts || [];
                    const MAX_TOASTS = 5;

                    // Determine whether this is the initial poll since page load
                    const isInitialFetch = (window._notificationInitialFetch === undefined);

                    // Compute notifications IDs
                    const currentIds = (notifications || []).map(n => n.id);

                    if (isInitialFetch) {
                        // On first load: treat existing notifications as 'seen' (so they won't toast)
                        currentIds.forEach(id => seenIds.add(id));
                        // Persist seen IDs so reloads won't re-toast these
                        try {
                            // Keep size bounded: keep last 500 entries
                            let arr = Array.from(seenIds);
                            if (arr.length > 500) arr = arr.slice(arr.length - 500);
                            localStorage.setItem(SEEN_KEY, JSON.stringify(arr));
                        } catch (e) {
                            console.warn('Failed to persist seen IDs', e);
                        }
                        // mark initial fetch done
                        window._notificationInitialFetch = false;
                    } else {
                        // Identify newly-arrived notifications compared to seenIds
                        const newNotifications = notifications.filter(n => !seenIds.has(n.id));

                        if (newNotifications.length > 0) {
                            // Number of toast slots available
                            const availableSlots = Math.max(0, MAX_TOASTS - window._activeNotificationToasts.length);
                            const toDisplay = newNotifications.filter(n => !toastedIds.has(n.id)).slice(0, availableSlots);

                            toDisplay.forEach(n => {
                                try {
                                    // Configure toastr for top-right, 5s
                                    toastr.options = toastr.options || {};
                                    toastr.options.positionClass = 'toast-top-right';
                                    toastr.options.timeOut = 5000;
                                    toastr.options.extendedTimeOut = 1000;
                                    toastr.options.closeButton = true;

                                    const title = n.title ? (n.title + ':') : '';
                                    toastr.info(title + ' ' + (n.message || ''), 'New notification');

                                    // Track active toast id to limit concurrents
                                    window._activeNotificationToasts.push(n.id);
                                    // Remove id after toast duration + small buffer
                                    setTimeout(() => {
                                        const idx = window._activeNotificationToasts.indexOf(n.id);
                                        if (idx > -1) window._activeNotificationToasts.splice(idx, 1);
                                    }, 5500);

                                    // Mark as toasted and seen (persist)
                                    toastedIds.add(n.id);
                                    seenIds.add(n.id);
                                } catch (e) {
                                    console.warn('Toast failed', e);
                                }
                            });

                            // Persist toasted and seen IDs (bounded)
                            try {
                                let tArr = Array.from(toastedIds);
                                if (tArr.length > 1000) tArr = tArr.slice(tArr.length - 1000);
                                localStorage.setItem(TOASTED_KEY, JSON.stringify(tArr));

                                let sArr = Array.from(seenIds);
                                if (sArr.length > 1000) sArr = sArr.slice(sArr.length - 1000);
                                localStorage.setItem(SEEN_KEY, JSON.stringify(sArr));
                            } catch (e) {
                                console.warn('Failed to persist toast/seen IDs', e);
                            }
                        }
                    }

                    // Update badge
                    const badge = document.querySelector('#notificationBell .text-danger') || document.querySelector('#notificationBell span.text-danger');
                    if (badge) {
                        badge.textContent = unreadCount;
                    } else {
                        // create badge if missing
                        const bell = document.getElementById('notificationBell');
                        if (bell) {
                            const span = document.createElement('span');
                            span.className = 'text-danger';
                            span.style.marginLeft = '4px';
                            span.textContent = unreadCount;
                            bell.appendChild(span);
                        }
                    }

                    // Update dropdown list (replace contents of #notif-list) - only if dropdown is visible
                    const list = document.getElementById('notif-list');
                    const dropdown = document.querySelector('.notification-dropdown');
                    
                    // Only update DOM if dropdown is visible or about to be shown
                    if (list && (!dropdown || dropdown.classList.contains('show') || window._forceNotificationUpdate)) {
                        window._forceNotificationUpdate = false;
                        
                        const fragment = document.createDocumentFragment();
                        
                        if (notifications.length === 0) {
                            const p = document.createElement('p');
                            p.className = 'text-muted';
                            p.textContent = 'No new notifications';
                            fragment.appendChild(p);
                        } else {
                            notifications.forEach(n => {
                                    const item = document.createElement('div');
                                    var itemClass = 'notif-item border-bottom mb-3 pb-3 d-flex align-items-start ' + (n.read ? 'read' : 'unread');
                                    item.className = itemClass;

                                if (!n.read) {
                                    const cb = document.createElement('input');
                                    cb.type = 'checkbox';
                                    cb.className = 'form-check-input me-2 notif-checkbox';
                                    cb.name = 'ids[]';
                                    cb.value = n.id;
                                    item.appendChild(cb);
                                }

                                const a = document.createElement('a');
                                a.href = n.action_url || '#';
                                a.className = 'notification-link flex-grow-1';
                                a.dataset.id = n.id;

                                a.innerHTML = '<div class="d-flex"><div><p class="mb-1"><span class="fw-semibold"><b>' + (n.title || '') + ':</b></span> ' + (n.message || '') + '</p><span class="text-muted small">' + (new Date(n.created_at).toLocaleString()) + '</span></div></div>';

                                // click handling is delegated globally to avoid per-item listeners

                                item.appendChild(a);
                                fragment.appendChild(item);
                            });
                        }
                        
                        // Use fragment to minimize reflows
                        list.innerHTML = '';
                        list.appendChild(fragment);
                    }

                    // Update notif count in header
                    const countSpan = document.getElementById('notif-count');
                    if (countSpan) countSpan.textContent = unreadCount;

                } catch (err) {
                    console.error('Notification poller error', err);
                }
            }

            // expose for other code to trigger a manual refresh after actions
            window.fetchNotifications = fetchNotifications;

            // Initial fetch shortly after load
            setTimeout(fetchNotifications, 3000);
            // Periodic polling
            setInterval(fetchNotifications, POLL_INTERVAL_MS);
            
            // When user opens notification dropdown, force update
            const notifBell = document.getElementById('notificationBell');
            if (notifBell) {
                notifBell.addEventListener('click', function() {
                    window._forceNotificationUpdate = true;
                    setTimeout(() => fetchNotifications(), 100);
                });
            }
        })();
        
        // Expose manual trigger for SIA check (call from console or other scripts)
        window.triggerSiaCheck = async function ({ force = false } = {}) {
            const KEY = 'sia_check_last_v1';
            const DAY_MS = 24 * 60 * 60 * 1000;
            const LOCK_KEY = 'sia_check_lock_v1';
            const ENDPOINT = "{{ route('process.sia.licences') }}";
            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                if (!force) {
                    const last = parseInt(localStorage.getItem(KEY) || '0', 10);
                    if (Date.now() - last < DAY_MS) {
                        return { skipped: true, reason: 'recent' };
                    }
                }

                const now = Date.now();
                const lock = localStorage.getItem(LOCK_KEY);
                if (lock && (now - parseInt(lock, 10) < 60 * 1000)) {
                    return { skipped: true, reason: 'locked' };
                }

                try { localStorage.setItem(LOCK_KEY, now.toString()); } catch (e) { /* ignore */ }

                const res = await fetch(ENDPOINT, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({ triggered_by: 'manual' })
                });

                if (!res.ok) {
                    const txt = await res.text().catch(() => null);
                    throw new Error('Server error ' + res.status + ' ' + (txt || ''));
                }

                const json = await res.json().catch(() => null);
                try { localStorage.setItem(KEY, Date.now().toString()); } catch (e) { /* ignore */ }
                try { localStorage.removeItem(LOCK_KEY); } catch (e) { /* ignore */ }

                return { ok: true, json };
            } catch (err) {
                console.error('triggerSiaCheck failed', err);
                try { localStorage.removeItem(LOCK_KEY); } catch (e) { /* ignore */ }
                throw err;
            }
        };

        // Auto-trigger SIA check once a day at 16:00 Europe/London from the browser.
        // Open browser DevTools → Console to see [SIA] debug lines.
        setTimeout(async function () {
            try {
                const now = new Date();

                // Build today's 16:00 in Europe/London (handles GMT/BST automatically)
                const ukParts = new Intl.DateTimeFormat('en-GB', {
                    timeZone: 'Europe/London',
                    year: 'numeric', month: '2-digit', day: '2-digit'
                }).formatToParts(now).reduce((acc, p) => { acc[p.type] = p.value; return acc; }, {});

                const londonTargetUtc = Date.UTC(
                    parseInt(ukParts.year), parseInt(ukParts.month) - 1, parseInt(ukParts.day),
                    16, 0, 0, 0
                );

                const utcDate = new Date(now.toLocaleString('en-US', { timeZone: 'UTC' }));
                const londonDate = new Date(now.toLocaleString('en-US', { timeZone: 'Europe/London' }));
                const londonOffsetMs = utcDate - londonDate;

                const target = new Date(londonTargetUtc + londonOffsetMs);
                const msUntilTarget = target.getTime() - now.getTime();

                console.log('[SIA] auto-trigger init. Now (UK):', londonDate.toLocaleTimeString(), '| Target 16:00 UK:', target.toLocaleTimeString(), '| ms until target:', msUntilTarget);

                if (now >= target) {
                    console.log('[SIA] past 16:00 UK — triggering now');
                    const result = await window.triggerSiaCheck();
                    console.log('[SIA] triggerSiaCheck result:', result);
                    return;
                }

                // Schedule for the remaining milliseconds until 16:00 London
                console.log('[SIA] before 16:00 — scheduling in', Math.round(msUntilTarget / 60000), 'minutes');

                let scheduled = false;
                try {
                    setTimeout(async () => {
                        console.log('[SIA] scheduled timeout fired — triggering now');
                        try {
                            const result = await window.triggerSiaCheck();
                            console.log('[SIA] triggerSiaCheck result:', result);
                        } catch (e) {
                            console.error('[SIA] triggerSiaCheck error in scheduled trigger:', e);
                        }
                    }, msUntilTarget);
                    scheduled = true;
                    console.log('[SIA] setTimeout scheduled successfully');
                } catch (e) {
                    console.warn('[SIA] setTimeout failed, falling back to poll interval:', e);
                    scheduled = false;
                }

                if (!scheduled) {
                    // Fallback: poll every 5 minutes until 16:00 then trigger
                    const pollInterval = setInterval(async () => {
                        const now2 = new Date();
                        if (now2 >= target) {
                            clearInterval(pollInterval);
                            console.log('[SIA] poll interval: past 16:00 UK — triggering now');
                            try {
                                const result = await window.triggerSiaCheck();
                                console.log('[SIA] triggerSiaCheck result:', result);
                            } catch (e) {
                                console.error('[SIA] triggerSiaCheck error in poll fallback:', e);
                            }
                        }
                    }, 5 * 60 * 1000);
                }
            } catch (e) {
                console.warn('[SIA] auto-trigger outer error:', e);
            }
        }, 15000); // wait 15 s after page load

        // Helper to open profile change request modal
        async function openProfileChangeModal(id) {
            try {
                const res = await fetch('/api/admin/profile-change-requests/' + id, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Failed to load request');
                const data = await res.json();
                const req = data.request;
                document.getElementById('pc_request_id').value = req.id;
                document.getElementById('pc_fullname').textContent = (req.user?.first_name || '') + ' ' + (req.user?.last_name || '');
                document.getElementById('pc_old_email').textContent = req.old_email || '';
                document.getElementById('pc_new_email').textContent = req.requested_email || '';
                document.getElementById('pc_timestamp').textContent = new Date(req.created_at).toLocaleString();

                const modalEl = document.getElementById('profileChangeModal');
                const bsModal = new bootstrap.Modal(modalEl);
                bsModal.show();
            } catch (err) {
                console.error('Failed to open request modal', err);
                toastr.error('Failed to load request details');
            }
        }

                // Delegated click handler for notification links (marks read, then navigates or opens modal)
                document.addEventListener('click', async function(e) {
                    const target = e.target.closest && e.target.closest('.notification-link');
                    if (!target) return;

                    const href = target.getAttribute('href') || '';
                    const id = target.dataset?.id;
                    const m = href.match(/\/admin\/profile-change-requests\/(\d+)/);

                    // prevent default navigation; we'll handle it after marking read
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    // mark notification as read (best-effort)
                    try {
                        if (id) {
                            await fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                credentials: 'same-origin'
                            });
                        }
                    } catch (err) {
                        console.warn('Failed to mark notif read', err);
                    }

                    // Update UI immediately
                    const row = target.closest('.notif-item');
                    const wasUnread = row && row.classList.contains('unread');
                    if (row) {
                        row.classList.remove('unread');
                        row.classList.add('read');
                    }
                    const countSpan = document.getElementById('notif-count');
                    if (countSpan && wasUnread) {
                        const cur = parseInt(countSpan.textContent) || 0;
                        countSpan.textContent = Math.max(0, cur - 1);
                    }

                    if (m) {
                        openProfileChangeModal(m[1]);
                    } else {
                        // navigate to target href
                        try { window.location.href = href; } catch (e) { console.warn('Navigation failed', e); }
                    }
                });

        // Approve / Deny flow
        let pendingAction = null; // {type: 'approve'|'deny', id}
        document.getElementById('approveRequestBtn')?.addEventListener('click', function() {
            pendingAction = { type: 'approve', id: document.getElementById('pc_request_id').value };
            document.getElementById('confirmActionText').textContent = 'Approve this profile change request?';
            new bootstrap.Modal(document.getElementById('confirmActionModal')).show();
        });

        document.getElementById('denyRequestBtn')?.addEventListener('click', function() {
            pendingAction = { type: 'deny', id: document.getElementById('pc_request_id').value };
            document.getElementById('confirmActionText').textContent = 'Deny this profile change request?';
            new bootstrap.Modal(document.getElementById('confirmActionModal')).show();
        });

        document.getElementById('confirmActionYesBtn')?.addEventListener('click', async function() {
            if (!pendingAction) return;
            const id = pendingAction.id;
            const type = pendingAction.type;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch('/api/admin/profile-change-requests/' + id + (type === 'approve' ? '/approve' : '/deny'), {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({})
                });

                const json = await res.json();
                if (!res.ok) {
                    throw new Error(json.message || 'Action failed');
                }

                // Attempt to hide both modals reliably
                try {
                    const confirmEl = document.getElementById('confirmActionModal');
                    const profileEl = document.getElementById('profileChangeModal');
                    const confirmInst = bootstrap.Modal.getOrCreateInstance(confirmEl);
                    const profileInst = bootstrap.Modal.getOrCreateInstance(profileEl);
                    confirmInst.hide();
                    profileInst.hide();
                } catch (e) {
                    console.warn('Modal hide failed:', e);
                }

                // Ensure any leftover backdrop / body classes removed
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = null;

                toastr.success(json.message || 'Action successful');

                // refresh notifications dropdown
                if (window.fetchNotifications) window.fetchNotifications();

                // As a fallback ensure UI fully refreshed — reload after a brief delay
                setTimeout(() => {
                    try {
                        window.location.reload();
                    } catch (e) {
                        console.warn('Reload failed:', e);
                    }
                }, 700);
            } catch (err) {
                console.error('Approve/Deny failed', err);
                toastr.error(err.message || 'Action failed');
            } finally {
                pendingAction = null;
            }
        });

        // Removed duplicate event listeners - handled by delegated listener below
    </script>
    <script>
        (function setupShiftNotificationsTrigger() {
            const ENDPOINT = `${baseUrl}/process-shift-notifications`;
            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const INTERVAL_MS = 2 * 60 * 1000; // 2 minutes
            const LOCK_TTL_MS = 1 * 60 * 1000; // avoid re-running within 1 minute from another tab

            async function triggerOnce(reason = 'manual') {
                try {
                    // Skip if page is hidden/minimized to save resources
                    if (reason !== 'manual' && document.hidden) {
                        return;
                    }
                    
                    const now = Date.now();
                    const lock = localStorage.getItem('processShiftNotifications:lock');
                    if (lock && (now - parseInt(lock, 10) < LOCK_TTL_MS)) {
                        return;
                    }

                    // Acquire lock
                    try { localStorage.setItem('processShiftNotifications:lock', now.toString()); } catch (e) { /* ignore */ }

                    const res = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ triggered_by: reason })
                    });

                    if (!res.ok) {
                        return;
                    }

                    const json = await res.json().catch(() => ({}));

                    try { localStorage.setItem('processShiftNotifications:lastRun', Date.now().toString()); } catch (e) { /* ignore */ }
                } catch (err) {
                    console.error('shift-notifications: trigger failed', err);
                }
            }

            // Initial trigger shortly after load (give page a moment)
            setTimeout(() => triggerOnce('initial'), 5000);

            // Periodic trigger every INTERVAL_MS
            setInterval(() => triggerOnce('interval'), INTERVAL_MS);

            // Also allow manual triggering via window for debugging
            window.triggerShiftNotifications = () => triggerOnce('manual');

            // If another tab triggers, we can optionally react (not strictly necessary here)
            window.addEventListener('storage', (e) => {
                if (e.key === 'processShiftNotifications:lastRun') {
                    // Tab synchronization
                }
            });
        })();
    </script>
      @yield('scripts')

    @stack('scripts')
</body>

</html>
