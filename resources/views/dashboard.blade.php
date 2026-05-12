@extends('layouts.app')
@section('title', 'SPL Connect')

@section('styles')

    <style>
        .user-label {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #000;
            padding: 2px 6px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            pointer-events: none;
        }

        .user-icon {
            width: 28px;
            height: 28px;
            display: inline-block;
        }
    </style>

@endsection

@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="">

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1"><b>Dashboard</b></h2>

                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="row row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3">
                <div class="col">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg br-10 icon-rotate bg-primary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users text-white fs-20"></i></span>
                                </div>
                                <div class="ms-3">
                                    <a href="/employees">
                                        <p class="fw-medium text-truncate mb-1 fs-16">Total Security Staff</p>
                                        <h5 class="fs-18">{{ $staffs }}</h5>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg br-10 icon-rotate bg-secondary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-heartbeat text-white fs-20"></i></span>
                                </div>
                                <div class="ms-3">
                                    <a href="/clients">
                                        <p class="fw-medium text-truncate mb-1 fs-16">Total Clients</p>
                                        <h5 class="fs-18">{{ $clients }}</h5>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                
                @can('Read Invoice Management')

                <div class="col">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg br-10 icon-rotate bg-danger flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-stairs-up text-white fs-20"></i></span>
                                </div>
                                <div class="ms-3">
                                    <a href="/invoices">
                                        <p class="fw-medium text-truncate mb-1 fs-16">No of Invoice Sent</p>
                                        <h5 class="fs-18">{{ $invoices }}</h5>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                @endcan
                <div class="col">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg br-10 icon-rotate bg-purple flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users-group text-white fs-20"></i></span>
                                </div>
                                <div class="ms-3">
                                    <a href="/shifts">
                                        <p class="fw-medium text-truncate mb-1 fs-16 ">Assigned shifts</p>
                                        <h5 class="fs-18">{{ $review }}</h5>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                @can('Read HR Managment')
 
                <div class="col">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg br-10 icon-rotate bg-warning flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-door-exit text-white fs-20"></i></span>
                                </div>
                                @php
                                    $leaves = App\Models\LeaveRequest::where('status', 'pending')->get();
                                @endphp
                                <div class="ms-3">
                                    <a href="/leaves/pending">
                                        <p class="fw-medium text-truncate mb-1 fs-16 ">New Leave Requests</p>
                                        <h5 class="fs-18">{{ $leaves->count() }}</h5>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <h3><b>Live Tracking</b></h3>
                            </div>
                        </div>
                        <div class="" style="padding-bottom:0px;">
                            <div id="map" style="height: 800px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">

            <div class="col-xxl-12 col-12 col-xl-12 d-flex">
                <div class="card w-100">
                    <div class="card-header">
                        <h5 class="fs-18"><b>Check Calls Monitoring for Today</b></h5>
                        <small>Filter by status: Pending | Missed | Completed</small>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Shift</th>
                                    <th>Staff</th>
                                    <th>CheckCall name</th>
                                    <th>Scheduled Time</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                    <th>Evidence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($checkCalls as $checkCall)
                                    @php
                                        $employee = App\Models\User::role('security_staff')
                                            ->where('id', $checkCall->employee_id)
                                            ->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $checkCall->shiftDate->shift->client?->name??''}} | {{ $checkCall->shiftDate->shift->site?->site_name ?? 'N/A' }}</td>
                                        <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                                        <td>{{ $checkCall->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($checkCall->scheduled_time)->format('d-m-Y H:i') }}
                                        </td>
                                        <td>{{ ucfirst($checkCall->status) }}</td>
                                        <td>{{ ucfirst($checkCall->method) }}</td>
                                        <td>
                                            @if ($checkCall->image_path)
                                                <a href="{{ asset('storage/' . $checkCall->image_path) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $checkCall->image_path) }}"
                                                        alt="Evidence" style="width:40px; height:auto;">
                                                </a>
                                            @else
                                                No Image
                                            @endif
                                        </td>
                                        <td>
                                            @if ($checkCall->status == 'pending')
                                                <button class="btn btn-success btn-sm"
                                                    title="Mark as completed" aria-label="Mark as completed"
                                                    onclick="updateStatus({{ $checkCall->id }}, 'completed')"><i class="fas fa-check"></i></button>
                                                <button class="btn btn-danger btn-sm"
                                                    title="Mark as missed" aria-label="Mark as missed"
                                                    onclick="updateStatus({{ $checkCall->id }}, 'missed')"><i class="fas fa-times"></i></button>
                                            @endif
                                            <button class="btn btn-secondary btn-sm"
                                                title="Comment" aria-label="Comment"
                                                onclick="openCommentModal({{ $checkCall->id }})"><i class="fas fa-comment"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No check calls found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Comment Modal -->
            <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form id="commentForm">
                        @csrf
                        <input type="hidden" name="check_call_id" id="check_call_id" value="">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="commentModalLabel">Add Comment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <textarea name="comment" class="form-control" rows="4" placeholder="Write your comment here..."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" title="Submit comment" aria-label="Submit comment"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xxl-12 col-12 col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                        <h5 class="mb-2"><b>Today Shifts (Live)</b></h5>
                    </div>

                    <!-- ✅ Scroll on card-body instead of table-responsive -->
                    <div class="card-body p-0">
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-nowrap mb-0">
                                <thead class="sticky-top bg-white">
                                    <tr class="text-center">
                                        <th>Shift Date</th>
                                        <th>Staff</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Site</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($shifts as $shift)
                                        @php
                                            $employee = App\Models\User::role('security_staff')
                                                ->where('id', $shift->staff_id)
                                                ->first();
                                            $siteName = data_get($shift, 'shift.site.site_name') 
                                                ?? data_get($shift, 'site.site_name') 
                                                ?? data_get($shift, 'shift.client.name') 
                                                ?? data_get($shift, 'client.name')
                                                ?? '-';
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($shift->start_time)->format('d-m-Y') }}</td>
                                            <td class="text-center">{!! trim(($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '')) ?: '<span class="text-gray">Unassigned</span>' !!}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</td>
                                            <td class="text-center">{{ $siteName }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex flex-column">
                <div class="card mt-4 flex-fill">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                            <h5 class="fs-18"><b>SIA License Check</b></h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>License</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($siaDocuments as $doc)
                                    <tr>
                                        <td>{{ $doc->fore_name ?? 'N/A' }} {{ $doc->sur_name ?? '' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($doc->sia_expiry)->format('d-m-Y') }}</td>
                                        <td><span class="badge bg-danger">Expired</span></td>
                                        <td>
                                            @if ($doc->sia_licence_file)
                                                <a class="btn btn-sm btn-success"
                                                    href="{{ asset('uploads/sia_licence_file/' . $doc->sia_licence_file) }}"
                                                    target="_blank">View</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No expired SIA licenses.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- ✨ Minimal pagination links --}}
                        @if ($siaDocuments->hasPages())
                            <div class="d-flex justify-content-center mt-3">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        {{-- Previous Page Link --}}
                                        @if ($siaDocuments->onFirstPage())
                                            <li class="page-item disabled"><span class="page-link">‹</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link"
                                                    href="{{ $siaDocuments->previousPageUrl() }}" rel="prev">‹</a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($siaDocuments->links()->elements[0] as $page => $url)
                                            @if ($page == $siaDocuments->currentPage())
                                                <li class="page-item active"><span
                                                        class="page-link">{{ $page }}</span></li>
                                            @else
                                                <li class="page-item"><a class="page-link"
                                                        href="{{ $url }}">{{ $page }}</a></li>
                                            @endif
                                        @endforeach

                                        {{-- Next Page Link --}}
                                        @if ($siaDocuments->hasMorePages())
                                            <li class="page-item"><a class="page-link"
                                                    href="{{ $siaDocuments->nextPageUrl() }}" rel="next">›</a></li>
                                        @else
                                            <li class="page-item disabled"><span class="page-link">›</span></li>
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex flex-column">
                <div class="card mt-4 flex-fill">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                            <h5 class="fs-18"><b>Book on | Book off</b></h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Shift</th>
                                    <th>Type</th>
                                    <th>Scheduled Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $booking)
                                    @php
                                        // $shift= App\Models\Shift::find($alarm->shift_id);
                                        $staff = App\Models\User::role('security_staff')
                                            ->where('id', $booking->user_id)
                                            ->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $staff->first_name ?? 'N/A' }}
                                            {{ $staff->last_name ?? '' }}</td>
                                        <td>{{ $booking->shift->shift->client?->name??''}} | {{ $booking->shift->shift->site?->site_name ?? 'N/A' }}</td>
                                        <td>{{ ucfirst($booking->type) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking->timestamp)->format('d-m-Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No Book On / Book Off records available.</td>
                                    </tr>
                                @endforelse
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

@section('scripts')
    <script>
        $.ajax({
            url: `${baseUrl}/shifts/stats`,
            method: 'GET',
            success: function(res) {
                var sColStacked = {
                    chart: {
                        height: 230,
                        type: 'bar',
                        stacked: true,
                        toolbar: {
                            show: false
                        }
                    },
                    colors: ['#FF6F28', '#F8F9FA'],
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            legend: {
                                position: 'bottom',
                                offsetX: -10,
                                offsetY: 0
                            }
                        }
                    }],
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            borderRadiusWhenStacked: 'all',
                            horizontal: false,
                            endingShape: 'rounded'
                        },
                    },
                    series: [{
                        name: 'Income',
                        data: res.shift
                    }, {
                        name: 'Shift',
                        data: res.shift
                    }],
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
                            'Oct', 'Nov', 'Dec'
                        ],
                        labels: {
                            style: {
                                colors: '#6B7280',
                                fontSize: '13px',
                            }
                        }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        labels: {
                            offsetX: -15,
                            style: {
                                colors: '#6B7280',
                                fontSize: '13px',
                            },
                            formatter: function(value) {
                                return value + "";
                            }
                        }
                    },
                    grid: {
                        borderColor: 'transparent',
                        strokeDashArray: 5,
                        padding: {
                            left: -8
                        },
                    },
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ""
                            }
                        }
                    },
                    fill: {
                        opacity: 1
                    }
                };

                var chart = new ApexCharts(document.querySelector("#revenue-income1"), sColStacked);
                chart.render();
            }
        });


        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        // Check calls script
        function updateStatus(id, status) {
            fetch(`/check-calls/${id}/status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        status
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // ✅ Find the row for this check call
                        const row = document.querySelector(`button[onclick="updateStatus(${id}, 'completed')"]`)
                            ?.closest('tr');
                        if (row) {
                            // Update the Status column (4th column, index 3)
                            row.cells[3].textContent = status.charAt(0).toUpperCase() + status.slice(1);

                            // Remove Completed/Missed buttons if needed
                            const actionBtns = row.querySelectorAll('button.btn-success, button.btn-danger');
                            actionBtns.forEach(btn => btn.remove());
                        }

                        // Optional: show a notification
                        alert('Status successfully updated!');
                    } else {
                        alert('Failed to update status.');
                    }
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    alert('An error occurred while updating status.');
                });
        }

        function openCommentModal(id) {
            document.getElementById('check_call_id').value = id;
            const myModal = new bootstrap.Modal(document.getElementById('commentModal'));
            myModal.show();
        }

        document.getElementById('commentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('check_call_id').value;
            const comment = this.comment.value;

            fetch(`/check-calls/${id}/comment`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        comment
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Comment added!');
                        const modalEl = document.getElementById('commentModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                    } else {
                        alert('Failed to add comment');
                    }
                })
                .catch(error => {
                    console.error('Error adding comment:', error);
                    alert('An error occurred while adding the comment.');
                });
        });

        // Booking on | off
        function acknowledgeAlarm(id) {
            fetch(`/booking-alarms/${id}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        acknowledged: true
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Acknowledged successfully.');
                        location.reload();
                    } else {
                        alert('Failed to acknowledge.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Something went wrong.');
                });
        }


        async function loadNotifications() {
            const notifications = await fetchNotifications();
            renderNotifications(notifications);
        }

        // Load initially
        loadNotifications();

        // Optionally, load whenever dropdown opens:
        document.getElementById('notification_popup').addEventListener('click', () => {
            loadNotifications();
        });
    </script>

    @php
        $currentUser = auth()->user();
    @endphp

    @if ($currentUser->hasAnyRole('controller|staff_leader|control_room'))
        @if(auth()->user()->admin_id !== null)
        @else
        <script src="{{ asset('assets/toast/alerts.js') }}" defer></script>
        @endif
    @endif

    <script>
        const userLocations = @json($userLocations ?? []);
        const siteLocations = @json($siteLocations ?? []);

        const iconByServiceId = {
            1: '/guard_icons/alarm_response.png',
            2: '/guard_icons/doghandlers.png',
            3: '/guard_icons/event_staff.png',
            4: '/guard_icons/key_holding.png',
            5: '/guard_icons/mobile_patrol.png',
            6: '/guard_icons/event_staff.png',
            7: '/guard_icons/fire_warden.png',
            8: '/guard_icons/close_protection.png',
        };

        const nameByServiceId = {
            1: 'Alarm Response',
            2: 'Doghandlers',
            3: 'Event Staff',
            4: 'Keyholding',
            5: 'Mobile Patrol',
            6: 'Static Guards',
            7: 'Fire Warden',
            8: 'Close Protection',
        };

        let map;
        let customMarkers = [];
        let currentInfoWindow = null;

        function initMap() {
            // --- Init map centered on England ---
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 9,
                center: {
                    lat: 51.87432911888075,
                    lng: -0.44027956053966389
                },
                gestureHandling: "auto", // user can drag/zoom
                mapTypeControl: true,
                streetViewControl: false,
            });

            // --- Add user markers ---
            userLocations.forEach(loc => {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                const latLng = new google.maps.LatLng(lat, lng);
                const username = loc.name ?? 'Unknown';
                const serviceTypeId = loc.service_type_id ?? 6;
                const iconUrl = iconByServiceId[serviceTypeId] ?? null;
                const serviceName = nameByServiceId[serviceTypeId] ?? 'Service';

                addCustomMarker(latLng, iconUrl, username, serviceName, loc, false);
            });

            // Close any open InfoWindow when user clicks on the map background
            map.addListener('click', function () {
                if (currentInfoWindow) {
                    currentInfoWindow.close();
                    currentInfoWindow = null;
                }
            });

            // (Site markers removed for this map)
        }

        function addCustomMarker(latLng, icon, displayName, serviceName, loc) {
            class CustomMarker extends google.maps.OverlayView {
                constructor(position) {
                    super();
                    this.position = position;
                    this.div = null;
                }

                onAdd() {
                    this.div = document.createElement("div");
                    this.div.className = "custom-marker";

                    const iconHTML = icon ? `<img src="${icon}" alt="${serviceName}" />` : `<div class="default-dot"></div>`;
                    // Only show the icon on the map; name appears in the info window
                    this.div.innerHTML = `
                        <div class="marker-wrapper">
                            <div class="marker-circle">${iconHTML}</div>
                        </div>
                    `;

                    this.div.style.cursor = 'pointer';
                    let closeTimeout = null;

                    const showInfo = () => {
                        if (closeTimeout) { clearTimeout(closeTimeout); closeTimeout = null; }
                        const siteNameRaw = loc.site_name || (loc.shift && (loc.shift.site_name || (loc.shift.site && loc.shift.site.site_name))) || (loc.current_shift && (loc.current_shift.site_name || (loc.current_shift.site && loc.current_shift.site.site_name))) || (loc.site && loc.site.site_name) || null;
                        const siteLine = siteNameRaw ? `<div style="font-size:13px;color:#444;margin-bottom:4px;"><strong>Site:</strong> ${siteNameRaw}</div>` : '';
                        const serviceLine = serviceName ? `<div style="font-size:13px;color:#444;margin-bottom:4px;"><strong>Service:</strong> ${serviceName}</div>` : '';
                        const onDutyLine = `<div style="font-size:13px;color:#444;margin-bottom:2px;"><strong>On Duty:</strong> ${loc.on_duty ? 'Yes' : 'No'}</div>`;
                        const lastSeenLine = `<div style="font-size:13px;color:#444;"><strong>Last seen:</strong> ${loc.timestamp ?? ''}</div>`;

                        const content = `
                            <div style="min-width:160px;max-width:260px;font-family:Segoe UI, sans-serif;color:#222;padding:6px 8px;position:relative;">
                                <button class="iw-close-btn" style="position:absolute;left:8px;top:6px;border:0;background:transparent;font-size:16px;line-height:1;color:#666;cursor:pointer;padding:0;margin:0;">&times;</button>
                                <div style="margin-left:28px;font-weight:700;margin-bottom:4px;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${displayName}</div>
                                ${siteLine}
                                ${serviceLine}
                                ${onDutyLine}
                                ${lastSeenLine}
                            </div>
                        `;

                        if (currentInfoWindow) currentInfoWindow.close();
                        currentInfoWindow = new google.maps.InfoWindow({ content: content, pixelOffset: new google.maps.Size(0, -30) });
                        currentInfoWindow.setPosition(this.position);
                        currentInfoWindow.open(map);

                        // Make the InfoWindow "sticky" so hovering between marker and the InfoWindow
                        // doesn't immediately close it. Attach listeners after DOM ready.
                        google.maps.event.addListenerOnce(currentInfoWindow, 'domready', function () {
                            const iwOuter = document.querySelector('.gm-style-iw');
                            if (!iwOuter) return;
                            // try to find a stable container to attach mouse events
                            let iwContainer = iwOuter.closest('.gm-style') || iwOuter.parentElement || iwOuter;

                            // Attempt to hide the default Google close button (reduces empty space)
                            try {
                                const defaultClose = iwContainer.querySelector('[title="Close"]') || iwContainer.querySelector('.gm-ui-hover-effect');
                                if (defaultClose) { defaultClose.style.display = 'none'; }
                            } catch (e) {
                                // ignore if structure differs
                            }

                            // No hover-based hide/show; InfoWindow opens on click and is closed
                            // via the inline close button or by clicking the map background.

                            // Wire up our inline close button (inside the content)
                            const ourClose = iwContainer.querySelector('.iw-close-btn') || iwOuter.querySelector('.iw-close-btn');
                            if (ourClose) {
                                ourClose.addEventListener('click', function (ev) {
                                    ev.stopPropagation();
                                    if (currentInfoWindow) { currentInfoWindow.close(); currentInfoWindow = null; }
                                });
                            }
                        });
                    };

                    const hideInfo = () => {
                        closeTimeout = setTimeout(() => {
                            if (currentInfoWindow) { currentInfoWindow.close(); currentInfoWindow = null; }
                        }, 400);
                    };

                    // Only open InfoWindow on click (no hover)
                    this.div.addEventListener('click', (e) => { e.stopPropagation(); showInfo(); });

                    const panes = this.getPanes();
                    panes.overlayMouseTarget.appendChild(this.div);
                }

                draw() {
                    const projection = this.getProjection();
                    const pos = projection.fromLatLngToDivPixel(this.position);
                    if (pos && this.div) {
                        this.div.style.left = pos.x + "px";
                        this.div.style.top = (pos.y - 18) + "px";
                    }
                }

                onRemove() {
                    if (this.div && this.div.parentNode) this.div.parentNode.removeChild(this.div);
                    this.div = null;
                }
            }

            const marker = new CustomMarker(latLng);
            marker.setMap(map);
            customMarkers.push(marker);
        }
    </script>

    <style>
        .custom-marker {
            position: absolute;
            cursor: pointer;
            transform: translate(-50%, -100%);
            display: flex;
            align-items: center;
            pointer-events: auto;
            z-index: 10;
        }

        .marker-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .marker-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
            background: linear-gradient(180deg, #ffffff 0%, #f3f6f9 100%);
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.9);
        }

        .marker-circle img { width: 24px; height: 24px; display:block }

        .marker-label {
            background: rgba(255,255,255,0.96);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 13px;
            color: #233142;
            box-shadow: 0 1px 4px rgba(0,0,0,0.12);
            max-width: 180px;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .default-dot { width: 12px; height: 12px; border-radius: 50%; background: #2c3e50 }
    </style>

    <!-- Google Maps JS API (with Visualization library for heatmap) -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=visualization&callback=initMap"
        async defer></script>

@endsection
