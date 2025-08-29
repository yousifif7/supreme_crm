@extends('layouts.app')
@section('title', 'SP CRM')

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
        <div class="content">

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Dashboard</h2>

                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="row">
                <div class="col-xl-3 col-md-6">
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
                                        <h5 class="fs-18">{{ $staffs->count() }}</h5>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
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
                                        <h5 class="fs-18">{{ $clients->count() }}</h5>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
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
                                        <h5 class="fs-18">{{ $invoices->count() }}</h5>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
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
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Live Tracking</div>
                        </div>
                        <div class="card-body">
                            <div id="map" style="height: 700px; width: 100%; margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">

            <div class="col-xxl-12 col-12 col-xl-12 d-flex">
                <div class="card w-100">
                    <div class="card-header">
                        <h5 class="fs-18">Check Calls Monitoring</h5>
                        <small>Filter by status: Pending | Missed | Completed</small>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Shift</th>
                                    <th>Staff</th>
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
                                        $employee = App\Models\Employee::find($checkCall->employee_id);
                                    @endphp
                                    <tr>
                                        <td>{{ $checkCall->shift->id ?? 'N/A' }}</td>
                                        <td>{{ $employee?->fore_name }} {{ $employee?->sur_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($checkCall->scheduled_time)->format('Y-m-d H:i') }}
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
                                                    onclick="updateStatus({{ $checkCall->id }}, 'completed')">Completed</button>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="updateStatus({{ $checkCall->id }}, 'missed')">Missed</button>
                                            @endif
                                            <button class="btn btn-secondary btn-sm"
                                                onclick="openCommentModal({{ $checkCall->id }})">Comment</button>
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
                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

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
                                    @foreach ($shifts as $shift)
                                        @php
                                            $employee = App\Models\User::role('security_staff')
                                                ->where('id', $shift->staff_id)
                                                ->first();
                                        @endphp
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}
                                            </td>
                                            <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                                            <td>X</td>
                                            <td>{{ $shift->break_time }}
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                                            </td>
                                        </tr>
                                    @endforeach



                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                            <h5 class="fs-18">Upcomming Shifts</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                @php
                                    use Carbon\Carbon;
                                    use App\Models\ShiftDate;

                                    $today = Carbon::today();
                                    $inThreeDays = Carbon::today()->addDays(3);

                                    $upcomingShifts = ShiftDate::whereDate('shift_date', '>=', $today)
                                        ->whereDate('shift_date', '<=', $inThreeDays)
                                        ->with('staff') // eager load staff if you want to access it
                                        ->get();
                                @endphp

                                <table class="table table-nowrap mb-0">
                                    <thead>
                                        <tr class="text-center">
                                            <th>DATE</th>
                                            <th>PERSON</th>
                                            <th>IN</th>
                                            <th>BREAK</th>
                                            <th>OUT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($upcomingShifts as $shift)
                                            @php
                                                $employee = App\Models\User::role('security_staff')
                                                    ->where('id', $shift->staff_id)
                                                    ->first();
                                            @endphp
                                            <tr class="text-center">
                                                <td>{{ Carbon::parse($shift->shift_date)->format('D, M j') }}</td>
                                                <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                                                <td>{{ Carbon::parse($shift->start_time)->format('h:i A') }}</td>
                                                <td>{{ $shift->break_time ?? '-' }}</td>
                                                <td>{{ Carbon::parse($shift->end_time)->format('h:i A') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No upcoming shifts in the next 3
                                                    days.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex">
                <div class="card mt-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                            <h5 class="fs-18">SIA License Check</h5>
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
                                        <td>{{ \Carbon\Carbon::parse($doc->sia_expiry)->format('Y-m-d') }}</td>
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

            <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex">
                <div class="card mt-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                            <h5 class="fs-18">Book on | Book off</h5>
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
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookingAlarms as $alarm)
                                    @php
                                        // $shift= App\Models\Shift::find($alarm->shift_id);
                                        $staff = App\Models\Employee::find($alarm->staff_id);
                                    @endphp
                                    <tr>
                                        <td>{{ $staff->fore_name ?? 'N/A' }}
                                            {{ $staff->sur_name ?? '' }}</td>
                                        <td>{{ $alarm->shift_id }}</td>
                                        <td>{{ ucfirst($alarm->type) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($alarm->scheduled_time)->format('Y-m-d H:i') }}
                                        </td>
                                        <td>{{ $alarm->status }}</td>
                                        <td>
                                            @if ($alarm->status !== 'Submitted')
                                                <button class="btn btn-success btn-sm"
                                                    onclick="acknowledgeAlarm({{ $alarm->id }})">
                                                    Acknowledge
                                                </button>
                                            @else
                                                ✔️
                                            @endif
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




    <script>
        const locations = @json($locations ?? []);

        const iconByServiceId = {
            1: '/guard_icons/alarm_response.svg',
            2: '/guard_icons/doghandlers.svg',
            3: '/guard_icons/event_staff.svg',
            4: '/guard_icons/key_holding.svg',
            5: '/guard_icons/mobile_patrol.svg',
            6: '/guard_icons/static_guard.svg',
            7: '/guard_icons/fire_warden.svg',
            8: '/guard_icons/close_protection.svg',
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
        let userMarkers = [];
        let markerLabels = [];
        let currentInfoWindow = null;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 4,
                center: {
                    lat: 0,
                    lng: 0
                },
            });

            const bounds = new google.maps.LatLngBounds();

            locations.forEach(loc => {
                if (!loc.user_id || isNaN(parseFloat(loc.latitude)) || isNaN(parseFloat(loc.longitude))) return;

                const latLng = new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude));
                const username = `${loc.user.first_name} ${loc.user.last_name}` ?? 'Unknown';
                const serviceTypeId = loc.service_type_id ?? 6; // default static guard
                const iconUrl = iconByServiceId[serviceTypeId];
                const serviceTypeName = nameByServiceId[serviceTypeId];

                // Marker
                const marker = new google.maps.Marker({
                    position: latLng,
                    map,
                    title: username,
                    icon: {
                        url: iconUrl,
                        scaledSize: new google.maps.Size(50, 50), // adjust size
                        scaledSize: new google.maps.Size(80, 80), // bigger icon
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(30, 60) // bottom center for bigger icon
                    }
                });

                // Label overlay
                const labelDiv = document.createElement("div");
                labelDiv.className = "user-label";
                labelDiv.innerHTML = `<span style="margin-left:10px;">${username}</span>`; // padding

                const label = new google.maps.OverlayView();
                label.onAdd = function() {
                    this.getPanes().overlayImage.appendChild(labelDiv);
                };
                label.draw = function() {
                    const projection = this.getProjection();
                    const pos = projection.fromLatLngToDivPixel(marker.getPosition());
                    if (pos) {
                        labelDiv.style.left = (pos.x) + "px";
                        labelDiv.style.top = (pos.y - 40) + "px"; // above icon
                    }
                };
                label.onRemove = function() {
                    if (labelDiv.parentNode) labelDiv.parentNode.removeChild(labelDiv);
                };
                label.setMap(map);

                marker.addListener("click", () => {
                    if (currentInfoWindow) currentInfoWindow.close();

                    currentInfoWindow = new google.maps.InfoWindow({
                        content: `
          <div style="min-width:220px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
              <img src="${iconUrl}" class="user-icon" alt="${serviceTypeName}" width="32" height="32">
              <div>
                <div><strong>${username}</strong></div>
                <div style="font-size:12px;color:#444;">${serviceTypeName}</div>
              </div>
            </div>
            <div style="font-size:12px;line-height:1.4;">
              <div><strong>Accuracy:</strong> ${loc.accuracy ?? 'N/A'} m</div>
              <div><strong>On Duty:</strong> ${loc.on_duty ? 'Yes' : 'No'}</div>
              <div><strong>Timestamp:</strong> ${loc.timestamp ?? ''}</div>
            </div>
          </div>
        `,
                        position: latLng
                    });
                    currentInfoWindow.open(map);

                    currentInfoWindow.addListener("closeclick", () => {
                        currentInfoWindow = null;
                    });
                });

                userMarkers.push(marker);
                markerLabels.push(label);
                bounds.extend(latLng);
            });

            map.fitBounds(bounds);
        }
    </script>

    <style>
        .user-label {
            position: absolute;
            font-weight: bold;
            color: red;
            white-space: nowrap;
            pointer-events: none;
        }
    </style>


    <!-- Google Maps JS API (with Visualization library for heatmap) -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=visualization&callback=initMap"
        async defer></script>

@endsection
