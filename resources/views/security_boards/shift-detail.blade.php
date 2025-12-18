@extends('layouts.app')
@section('title')
    CRM | Shift detail
@endsection

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        .profile-map {
            width: 100px;
            /* Fixed width */
            height: 100px;
            /* Fixed height */
            overflow: hidden;
            /* Hide anything outside the circle */
            position: relative;
            /* Ensure child elements (map tiles) are contained */
        }

        .leaflet-control-attribution {
            display: none !important;
        }
    </style>
    <style>
        .document-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .document-image-wrapper {
            position: relative;
            width: 70%;
            margin: 0 auto;
            aspect-ratio: 1;
            overflow: hidden;
            background: #f8f9fa;
        }

        .document-image {
            width: 100%;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .document-card:hover .document-image {
            transform: scale(1.05);
        }

        .document-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .document-card:hover .document-overlay {
            opacity: 1;
        }

        .view-btn {
            background: #fff;
            color: #333;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background: #007bff;
            color: #fff;
            transform: scale(1.1);
        }

        .document-label {
            padding: 10px;
            background: #fff;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .document-label h6 {
            color: #333;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .document-card {
                margin-bottom: 15px;
            }

            .document-label {
                padding: 8px;
            }

            .document-label h6 {
                font-size: 10px;
            }

            .document-image-wrapper {
                width: 80%;
            }
        }

        @media (max-width: 576px) {
            .col-md-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        .toast-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            flex: 1 1 auto;
            /* Make buttons responsive */
            min-width: 100px;
        }

        .override-btn {
            background-color: #ffc107;
            color: #212529;
        }

        .confirm-btn {
            background-color: #28a745;
            color: #fff;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: #fff;
        }

        /* Optional: small screens tweaks */
        @media (max-width: 480px) {
            #custom-toast-container {
                width: 100%;
            }

            .toast-actions button {
                font-size: 14px;
                padding: 8px;
            }
        }

        /* Inline actions: close + delete aligned */
        .inline-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .inline-actions form {
            margin: 0;
        }
    </style>
@endsection
@section('contents')
    @php
        $staffs = App\Models\User::role('security_staff')->get();
    @endphp

    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shift details</h2>
                </div>
                <div class="my-auto mb-2 inline-actions">
                    <form action="/deleteshift/{{$shiftDate->id}}" method="post" class="delete-shift-form">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                    <button class="btn btn-primary" onclick="closeTab()">× Close</button>
                </div>
            </div>


            <div class="row" id="eventModal">
                <div class="tabs-parent_main">
                    <div class="tabs-parent nav nav-tabs" role="tablist">
                        <button class="nav-link active" id="info-tab2" data-bs-toggle="tab" data-bs-target="#basic-info2"
                            type="button" role="tab" aria-controls="basic-info2" aria-selected="true">Rota
                            Detail</button>
                        <button class="nav-link" id="address-tab2" data-bs-toggle="tab" data-bs-target="#address2"
                            type="button" role="tab" aria-controls="address2" aria-selected="false">Office
                            Validation</button>
                        <button class="nav-link" id="logs-tab2" data-bs-toggle="tab" data-bs-target="#logs2" type="button"
                            role="tab" aria-controls="logs2" aria-selected="false">Logs</button>
                        <button class="nav-link" id="checkcalls-tab2" data-bs-toggle="tab" data-bs-target="#checkcalls"
                            type="button" role="tab" aria-controls="checkcalls" aria-selected="false">Check
                            Calls</button>
                        <button class="nav-link" id="patrols-tab2" data-bs-toggle="tab" data-bs-target="#patrols"
                            type="button" role="tab" aria-controls="patrols" aria-selected="false">Patrols</button>
                    </div>

                    <div class="expiry_date">
                        <div class="form-check form-check-lg form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="switch-lg">
                            <label class="form-check-label" for="switch-lg">
                                Stand-downSIA Number : <span id="sia_number">
                                    {{ $shiftDate->staff?->sia_licence ?? '' }}</span>
                                &nbsp;&nbsp;Expiry: <span id="sia_expiry">{{ $shiftDate->staff?->sia_expiry ?? '' }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="shift_id" value="{{ $shiftDate->id }}">
                <input type="hidden" name="user_id" value="{{ $shiftDate->staff->id ?? '' }}">

                <div class="tab-content rota-detail_tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="basic-info2" role="tabpanel" aria-labelledby="info-tab2">
                        <div class="modal-body pb-0 ">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="upper-stats-box">
                                        <div class="profile-detail">
                                            <div class="avater">
                                                <img src="{{ $shiftDate->staff?->profile_picture ?? 'https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png' }}"
                                                    class="profile-avater profile_picture" id="profile_picture">
                                            </div>

                                            <div class="profile-details">
                                                <h6 id="name">
                                                    {{ $shiftDate->staff?->first_name ?? '' }}
                                                    {{ $shiftDate->staff?->last_name ?? '' }}

                                                    <!-- Edit Icon -->
                                                    <a href="#" onclick="editShift({{ $shiftDate->id }})"
                                                        title="Edit Shift">
                                                        <i class="fas fa-edit"></i> <!-- FontAwesome Edit Icon -->
                                                    </a>

                                                    <!-- Unassign Icon -->
                                                    @if ($shiftDate->staff)
                                                        <a href="#" onclick="unassignShift({{ $shiftDate->id }})"
                                                            title="Unassign Shift">
                                                            <i class="fas fa-user-slash"></i>
                                                            <!-- FontAwesome Unassign Icon -->
                                                        </a>
                                                    @endif
                                                </h6>

                                                <div class="mb-1">
                                                    <i class="ti ti-phone"></i>
                                                    <span id="phone_number">{{ $shiftDate->staff?->contact ?? '' }}</span>
                                                </div>
                                                <div>
                                                    <i class="ti ti-mail"></i>
                                                    <span id="email">{{ $shiftDate->staff?->email ?? '' }}</span>
                                                </div>
                                                <button id="assignShiftBtn" type="button"
                                                    class="btn btn-assign {{ in_array($shiftDate->is_assign, [0, 5, 6]) ? '' : 'd-none' }}">
                                                    <i class="bi bi-person-plus"></i> Assign Shift
                                                </button>

                                            </div>
                                        </div>
                                        <div class="partner-details">
                                            <h6>Partner</h6>
                                            <span id="subcontractor">{{ $shiftDate->staff?->subcontractor ?? '' }}</span>
                                        </div>
                                    </div>
                                    <div class="bottom-stats-box">
                                        <div class="other-detail_boxes">
                                            <div class="box">
                                                <h6>Site Address</h6>
                                                <span
                                                    id="site_address">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                            </div>
                                            <div class="box">
                                                <h6>Date</h6>
                                                <span id="date">{{ format_date($shiftDate->shift_date) }}</span>
                                            </div>
                                            <div class="box">
                                                <h6>Shift Time</h6>
                                                <span id="shift_time">
                                                    @if (!empty($shiftDate->start_time) && !empty($shiftDate->end_time))
                                                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('H:i') }}
                                                        -
                                                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('H:i') }}
                                                        {{ sprintf('%02d hr %02d min', floor($shiftDate->total_hours), round(($shiftDate->total_hours - floor($shiftDate->total_hours)) * 60)) }}
                                                    @else
                                                        Not available
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="box">
                                                @php
                                                    $client = App\Models\User::find($shiftDate->shift->client_id);
                                                @endphp
                                                <h6>Customer</h6>
                                                <span id="client_name">{{ $client->name ?? '' }}</span>
                                            </div>
                                            <div class="box">
                                                <h6>Site Name</h6>
                                                <span id="site_name">{{ $shiftDate->shift->site->site_name ?? '' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="book-on_box">
                                        <div class="profile-detail">
                                            @php
                                                $firstLocation = App\Models\Location::where(
                                                    'shiftdate_id',
                                                    $shiftDate->id,
                                                )
                                                    ->orderBy('timestamp', 'asc')
                                                    ->first();

                                                $lastLocation = App\Models\Location::where(
                                                    'shiftdate_id',
                                                    $shiftDate->id,
                                                )
                                                    ->orderBy('timestamp', 'desc')
                                                    ->first();
                                            @endphp
                                            <div class="avater">
                                                @if ($firstLocation)
                                                    <div id="map-on-{{ $shiftDate->id }}" class="profile-map"></div>
                                                @else
                                                    <p class="bg-light">No starting location found.</p>
                                                @endif
                                            </div>
                                            <div class="profile-details">
                                                <h6>Book on</h6>
                                                <div class="mb-1">
                                                    <i class="ti ti-calendar"></i>
                                                    <span
                                                        id="book_on">{{ (format_date($shiftDate->shift_date) ?: '') . ', at ' . ($shiftDate->absentee_start_time ?? '') }}</span>
                                                </div>
                                                <div>
                                                    <i class="ti ti-map-pin"></i>
                                                    <span
                                                        id="site_address1">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                                </div>
                                            </div>

                                        </div>
                                        <form id="bookonForm" action="{{ route('shift.bookon.store') }}" method="post">
                                            @csrf
                                            <input type="hidden" id="book_on_id" name="book_on_id"
                                                value="{{ $shiftDate->id }}">

                                            <input type="text" id="absentee_start_time" name="absentee_start_time"
                                                placeholder="HH:MM" class="form-control mb-2"
                                                value="{{ \Carbon\Carbon::parse($shiftDate->absentee_start_time ?? $shiftDate->start_time)->format('H:i') }}">

                                            <button type="submit" class="btn btn-primary">Set book on time</button>
                                        </form>
                                    </div>
                                    <div class="book-off_box">
                                        <div class="profile-detail">
                                            <div class="avater">
                                                @if ($lastLocation)
                                                    <div id="map-off-{{ $shiftDate->id }}" class="profile-map"></div>
                                                @else
                                                    <p class="bg-white">No ending location found.</p>
                                                @endif
                                            </div>
                                            <div class="profile-details">
                                                <h6>Book Off </h6>
                                                <div class="mb-1">
                                                    <i class="ti ti-calendar"></i>
                                                    <span
                                                        id="book_off">{{ (format_date($shiftDate->shift_date) ?: '') . ', at ' . ($shiftDate->absentee_end_time ?? '') }}</span>
                                                </div>
                                                <div>
                                                    <i class="ti ti-map-pin"></i>
                                                    <span
                                                        id="site_address2">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <form id="bookoffForm" action="{{ route('shift.bookoff.store') }}"
                                            method="post">
                                            @csrf
                                            <input type="hidden" id="book_off_id" name="book_off_id"
                                                value="{{ $shiftDate->id }}">
                                            <input type="text" id="absentee_end_time" name="absentee_end_time"
                                                placeholder="HH:MM" class="form-control mb-2"
                                                value="{{ \Carbon\Carbon::parse($shiftDate->absentee_end_time ?? $shiftDate->end_time)->format('H:i') }}">
                                            <button type="submit" class="btn btn-danger">Set book off time</button>
                                        </form>
                                    </div>
                                </div>
                                @if ($shiftDate->staff_id)
                                    @php
                                        // $employee= App\Models\Employee::find($shiftDate->staff_id);
                                        $user = App\Models\User::role('security_staff')
                                            ->where('id', $shiftDate->staff_id)
                                            ->first();
                                    @endphp
                                    <div class="col-12">
                                        {{-- <div class="tab-pane fade show active" id="basic-info2" role="tabpanel">
                                            <a href="{{ route('shift.map', ['shiftId' => $shiftDate->id]) }}"
                                                class="btn btn-primary" target="_blank">
                                                View Heatmap
                                            </a>
                                        </div> --}}
                                        <div>
                                            @include('map')
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="address2" role="tabpanel" aria-labelledby="address-tab2">
                        @if ($shiftDate->staff)
                            @php
                                $staff = App\Models\Employee::where('user_id', $shiftDate->staff_id)->first();
                            @endphp
                            <div class="container-fluid p-3">
                                <!-- First Row - 3 Images -->
                                <div class="row mb-4">
                                    @if ($staff->sia_licence_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('sia_licence_file', true)) }}"
                                                        alt="SIA Licence" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('sia_licence_file') }}"
                                                            target="_blank" class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">SIA Licence</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($staff->passport_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('passport_file', true)) }}"
                                                        alt="Passport" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('passport_file') }}" target="_blank"
                                                            class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">Passport</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($staff->act_certificate_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('act_certificate_file', true)) }}"
                                                        alt="ACT Certificate" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('act_certificate_file') }}"
                                                            target="_blank" class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">ACT Certificate</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Second Row - 3 Images -->
                                <div class="row mb-4">
                                    @if ($staff->proof_of_address_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('proof_of_address_file', true)) }}"
                                                        alt="Proof of Address" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('proof_of_address_file') }}"
                                                            target="_blank" class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">Proof of Address</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($staff->ni_letter_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('ni_letter_file', true)) }}"
                                                        alt="NI Letter" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('ni_letter_file') }}"
                                                            target="_blank" class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">NI Letter</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($staff->first_aid_certificate_file)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="document-card">
                                                <div class="document-image-wrapper">
                                                    <img src="{{ asset($staff?->fileUrl('first_aid_certificate_file', true)) }}"
                                                        alt="First Aid Certificate" class="document-image" />
                                                    <div class="document-overlay">
                                                        <a href="{{ $staff?->fileUrl('first_aid_certificate_file') }}"
                                                            target="_blank" class="view-btn">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="document-label">
                                                    <h6 class="mb-0">Right to work</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning" role="alert">
                                No staff assigned to this shift.
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="progress2" role="tabpanel" aria-labelledby="progress-tab2">
                        <div class="modal-body">Job Progress content goes here.</div>
                    </div>
                    <div class="tab-pane fade" id="logs2" role="tabpanel" aria-labelledby="logs-tab2">
                        @if (collect($shiftDate->logs)->isNotEmpty())
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        {{-- <th>Action</th> --}}
                                        <th>Description</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shiftDate->logs as $log)
                                        <tr>
                                            <td>{{ $log->user_name ?? 'N/A' }}</td>
                                            {{-- <td>{{ $log->action }}</td> --}}
                                            <td>{!! $log->description !!}</td>
                                            <td>{{ $log->created_at->format('m-d-Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info" role="alert">
                                No logs available for this shift.
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="checkcalls" role="tabpanel" aria-labelledby="checkcalls-tab2">
                        @php
                            // Make sure $shiftDate is set and has shift_id before querying
                            $checkcalls = collect();
                            if (!empty($shiftDate?->id)) {
                                $checkcalls = \App\Models\CheckCall::where('shift_id', $shiftDate->id)
                                    ->orderBy('scheduled_time', 'asc')
                                    ->get();
                            }
                        @endphp

                        @if ($checkcalls->isNotEmpty())
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Staff</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Media</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($checkcalls as $checkcall)
                                        @php
                                            $employee = \App\Models\User::find($checkcall->employee_id);
                                            $checkCallMedia =
                                                \App\Models\CheckCallMedia::where(
                                                    'check_call_id',
                                                    $checkcall->id,
                                                )->get() ?? collect();
                                        @endphp
                                        <tr>
                                            <td>{{ $checkcall?->name }}</td>
                                            <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($checkcall->scheduled_time)->format('d-m-Y H:i') }}
                                            </td>
                                            <td>
                                                @if ($checkcall->status == 'pending')
                                                    <p class="bg-warning text-center">Pending</p>
                                                @elseif ($checkcall->status == 'missed')
                                                    <p class="bg-danger text-center">Missed</p>
                                                @elseif($checkcall->status == 'completed')
                                                    <p class="bg-success text-center">Completed</p>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse ($checkCallMedia as $media)
                                                    <a href="{{ asset($media->file_path) }}" target="_blank"
                                                        class="btn btn-sm btn-primary">
                                                        View File
                                                    </a><br>
                                                @empty
                                                    No media
                                                @endforelse
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-checkcall-btn"
                                                    data-id="{{ $checkcall->id }}" data-name="{{ $checkcall->name }}"
                                                    data-time="{{ $checkcall->scheduled_time }}"
                                                    data-status="{{ $checkcall->status }}"> <!-- Add this -->
                                                    Edit
                                                </button>

                                                <button class="btn btn-sm btn-danger delete-checkcall-btn"
                                                    data-id="{{ $checkcall->id }}">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info" role="alert">
                                No check calls available for this shift.
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="patrols" role="tabpanel" aria-labelledby="patrols-tab2">
                        @php
                            $patrols = App\Models\Patrol::where('shift_id', $shiftDate->id)->get();
                            $site = \App\Models\Site::with('checkpoints')->find($shiftDate->shift->site_id);
                            $checkpoints = \App\Models\PatrolCheckPoint::where('site_id', $site->id) // adjust if necessary
                                ->get(['id', 'name', 'latitude', 'longitude']);                               
                        @endphp

                        @if ($patrols->isNotEmpty())
                            <script>
                                // Declare once for use by initPatrolMap calls below
                                const siteCheckpoints = @json($checkpoints);
                            </script>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Start time</th>
                                        <th>Total Checkpoints</th>
                                        <th>Completed</th>
                                        <th>Issues</th>
                                        <th>Started at</th>
                                        <th>completed at</th>
                                        <th>Status</th>
                                        <th>Media</th>
                                        <th>Action</th>
                                        <th>Map</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($patrols as $patrol)
                                    @php
                                            $patrolMedia =\App\Models\PatrolMedia::where('patrol_id',$patrol->id)->get() ?? collect(); 
                                    @endphp
                                        <tr>
                                            <td>{{ $patrol->name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($patrol->start_time)->format('d-m-Y H:i') }}</td>
                                            <td>{{ $patrol->total_checkpoints }}</td>
                                            <td>{{ $patrol->completed_checkpoints }}</td>
                                            <td>{{ $patrol->issues_reported }}</td>
                                            @if ($patrol->started_at)
                                                <td>{{ \Carbon\Carbon::parse($patrol->started_at ?? '')->format('H:i') }}
                                                </td>
                                            @else
                                                <td></td>
                                            @endif
                                            @if ($patrol->completed_at)
                                                <td>{{ \Carbon\Carbon::parse($patrol->completed_at ?? '')->format('H:i') }}
                                                </td>
                                            @else
                                                <td></td>
                                            @endif
                                            <td>
                                                @if ($patrol->status == 'pending')
                                                    <p class="bg-warning text-center">Pending</p>
                                                @elseif ($patrol->status == 'in_progress')
                                                    <p class="bg-primary text-center">In Progress</p>
                                                @elseif($patrol->status == 'completed')
                                                    <p class="bg-success text-center">Completed</p>
                                                @elseif($patrol->status == 'missed')
                                                    <p class="bg-danger text-center">Missed</p>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse ($patrolMedia as $media)
                                                    <a href="{{ asset($media->file_path) }}" target="_blank"
                                                        class="btn btn-sm btn-primary">
                                                        View File
                                                    </a><br>
                                                @empty
                                                    No media
                                                @endforelse
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-patrol-btn"
                                                    data-id="{{ $patrol->id }}" data-name="{{ $patrol->name }}"
                                                    data-time="{{ \Carbon\Carbon::parse($patrol->start_time)->format('H:i') }}"
                                                    data-status="{{ $patrol->status }}">
                                                    Edit
                                                </button>

                                                <button class="btn btn-sm btn-danger delete-patrol-btn"
                                                    data-id="{{ $patrol->id }}">
                                                    Delete
                                                </button>
                                            </td>
                                            <td style="min-width:350px">
                                                <div id="patrol-map-{{ $patrol->id }}"
                                                    style="height:250px; width:100%; border-radius:8px;"></div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info" role="alert">
                                No patrols available for this shift.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editCheckCallModal" tabindex="-1" aria-labelledby="editCheckCallLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <form id="editCheckCallForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editCheckCallLabel">Edit Check Call</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="checkcall_id">
                                <div class="mb-3">
                                    <label>Name</label>
                                    <input type="text" class="form-control" name="name" id="checkpoint_name"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label>Scheduled Time</label>
                                    <input type="datetime" class="form-control" name="scheduled_time"
                                        id="scheduled_time" required>
                                </div>
                                <div class="mb-3">
                                    <label>Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="missed">Missed</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Patrol Modal -->
            <div class="modal fade" id="editPatrolModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <form id="editPatrolForm">
                        @csrf
                        <input type="hidden" name="id" id="edit_patrol_id">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Patrol</h5>
                                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Patrol Name</label>
                                    <input type="text" id="edit_patrol_name" name="name" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" id="edit_patrol_time" name="start_time" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select id="edit_patrol_status" name="status" class="form-control">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="missed">Missed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Assign Shift Modal -->
            @include('security_boards.assign-shift-modal')
            @include('security_boards.edit')
        </div>

    </div>
@endsection
@section('scripts')
    @php
        $apiKey = env('GOOGLE_MAPS_API_KEY');
    @endphp

    <script>
        window.isSuperAdmin = @json(auth()->check() &&
                auth()->user() &&
                auth()->user()->getRoleNames() &&
                auth()->user()->getRoleNames()->contains('superadmin'));
    </script>

    <script>
        function showRestrictionToast(message, onOverride) {
            let container = document.getElementById('custom-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'custom-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'custom-toast';

            toast.innerHTML = `
        <div class="toast-icon">⚠</div>
        <div class="toast-content">
            <p>${message}</p>
            <div class="toast-actions">
                <button class="override-btn">Override Restriction</button>
            </div>
        </div>
    `;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 50);

            // Step 1: Override clicked
            toast.querySelector('.override-btn').addEventListener('click', function() {
                // Replace actions with confirmation buttons
                const actions = toast.querySelector('.toast-actions');
                actions.innerHTML = `
            <button class="confirm-btn">Yes, Override</button>
            <button class="cancel-btn">Cancel</button>
        `;

                // Step 2: Confirm override
                actions.querySelector('.confirm-btn').addEventListener('click', function() {
                    if (typeof onOverride === 'function') {
                        onOverride();
                    }
                    closeToast();
                });

                // Step 2: Cancel override
                actions.querySelector('.cancel-btn').addEventListener('click', function() {
                    closeToast();
                });
            });

            function closeToast() {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) container.removeChild(toast);
                }, 300);
            }
        }

        $(document).off('submit', '#bookonForm, #bookoffForm')
            .on('submit', '#bookonForm, #bookoffForm', function(e) {
                e.preventDefault(); // prevent default form submission

                const $form = $(this);
                const actionUrl = $form.attr('action');

                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json', // ensures proper JSON parsing
                    success: function(response) {
                        if (response.success) {
                            // Show success toast
                            showToast(response.success, 'success', 5000);
                            // Close modal if needed
                            closeBsModal('#eventModal');
                        } else if (response.error) {
                            // Show error toast
                            showToast(response.error, 'error', 5000);
                        } else {
                            // Unexpected response
                            showToast('Unexpected response from server.', 'error', 5000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            if (errors) {
                                const messages = Object.values(errors).flat().join('\n');
                                showToast(messages, 'error', 5000);
                            } else if (xhr.responseJSON.error) {
                                showToast(xhr.responseJSON.error, 'error', 5000);
                            }
                        } else {
                            // Other errors
                            showToast('An unexpected error occurred while assigning the shift.', 'error',
                                5000);
                        }
                    }
                });
            });


        $(document).off('click', '#assignShiftBtn').on('click', '#assignShiftBtn', function() {
            $('#assign_shift_modal_shift_id').val({{ $shiftDate->id }});
            $('#assignShiftModal').modal('show');
            $(".selec2_assign_modal").select2({
                dropdownParent: $("#assignShiftModal")
            });
        });

        $(document).ready(function() {
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
        });


        $(document).on('click', '.edit-checkcall-btn', function() {
            $('#checkcall_id').val($(this).data('id'));
            $('#checkpoint_name').val($(this).data('name'));
            $('#scheduled_time').val($(this).data('time').replace(' ', 'T')); // for datetime-local

            // Set the status select based on the data-status attribute
            $('#status').val($(this).data('status'));

            $('#editCheckCallModal').modal('show');
        });
        // Handle update form submit
        $('#editCheckCallForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#checkcall_id').val();

            $.ajax({
                url: `/checkcalls/${id}`, // Your update route
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    toast_success('Check call updated sucessfully');
                    location.reload(); // Refresh table
                },
                error: function(xhr) {
                    toast_danger('Error updating check call');
                }
            });
        });

        $(document).on('click', '.delete-checkcall-btn', function() {
            if (!confirm('Are you sure you want to delete this check call?')) return;
            let id = $(this).data('id');

            $.ajax({
                url: `/checkcalls/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    showToast(
                        "Check call deleted succesfully", // message
                        'success', // type
                        5000 // duration in ms
                    );
                    location.reload();
                },
                error: function() {
                    alert('Error deleting check call');
                }
            });
        });
    </script>


    <script>
        $(document).off('submit', '#assignShiftForm').on('submit', '#assignShiftForm', function(e) {
            e.preventDefault();

            $.ajax({
                url: `${baseUrl}/assign-shift`,
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    showToast(response.success, 'success', 5000);
                    location.reload();
                },
                error: function(xhr) {
                    // $('#assignShiftErrors').addClass('d-none').empty(); // clear old errors

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        let messages = Object.values(xhr.responseJSON.errors).flat();
                        const restrictionMsg = messages[0]; // first error

                        if (window.isSuperAdmin) {
                            showRestrictionToast(restrictionMsg, () => {
                                // Clear errors before override
                                // $('#assignShiftErrors').addClass('d-none').empty();

                                // Send override request
                                $.ajax({
                                    url: `${baseUrl}/assign-shift-override`,
                                    type: 'POST',
                                    data: $('#assignShiftForm').serialize(),
                                    success: function(res) {
                                        showToast(res.success, 'success', 5000);
                                        location.reload();
                                    },
                                    error: function(err) {
                                        showToast("Override failed. Try again.",
                                            "error", 5000);
                                    }
                                });
                            });
                        } else {
                            showToast(restrictionMsg, 'error', 5000);
                        }

                        // Optional fallback in error div
                        // messages.forEach(msg => $('#assignShiftErrors').append(`<div>${msg}</div>`));
                        // $('#assignShiftErrors').removeClass('d-none');
                    } else if (xhr.responseJSON?.error) {
                        showToast(xhr.responseJSON.error, 'error', 5000);
                    } else {
                        showToast('An unexpected error occurred while assigning the shift.', 'error',
                            5000);
                    }
                }
            });
        });
        setTimeout(() => {
            const alertBox = document.querySelector('.alert');
            if (alertBox) {
                alertBox.classList.remove('show');
                alertBox.classList.add('hide');
            }
        }, 3000); // hides after 5 seconds
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if ($firstLocation)
                var mapOn = L.map('map-on-{{ $shiftDate->id }}').setView(
                    [{{ $firstLocation->latitude }}, {{ $firstLocation->longitude }}],
                    15
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: ''
                }).addTo(mapOn);

                L.marker([{{ $firstLocation->latitude }}, {{ $firstLocation->longitude }}]).addTo(mapOn);
            @endif

            @if ($lastLocation)
                var mapOff = L.map('map-off-{{ $shiftDate->id }}').setView(
                    [{{ $lastLocation->latitude }}, {{ $lastLocation->longitude }}],
                    15
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: ''
                }).addTo(mapOff);

                L.marker([{{ $lastLocation->latitude }}, {{ $lastLocation->longitude }}]).addTo(mapOff);
            @endif
        });
    </script>

    <script>
        function initPatrolMap(patrolId, shiftDateId, checkpoints) {
            const mapDiv = document.getElementById("patrol-map-" + patrolId);
            if (!mapDiv) return;

            const map = new google.maps.Map(mapDiv, {
                zoom: 12,
                center: { lat: 0, lng: 0 },
                mapTypeId: "roadmap",
            });

            // State containers for dynamic layers
            const checkpointMarkers = [];
            const guardMarkers = [];
            let routePolyline = null;
            let heatmap = null;
            let lastBounds = null;
            const MAX_ZOOM = 18;
            const MIN_ZOOM = 8;

            // Pre-compute checkpoint markers & bounds
            const checkpointBounds = new google.maps.LatLngBounds();
            checkpoints.forEach(cp => {
                const lat = parseFloat(cp.latitude);
                const lng = parseFloat(cp.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const pos = new google.maps.LatLng(lat, lng);
                    const m = new google.maps.Marker({
                        position: pos,
                        map,
                        title: cp.name,
                        icon: {
                            url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png",
                            scaledSize: new google.maps.Size(40, 40)
                        }
                    });
                    checkpointMarkers.push(m);
                    checkpointBounds.extend(pos);
                }
            });

            function clearGuardLayers() {
                guardMarkers.forEach(m => m.setMap(null));
                guardMarkers.length = 0;
                if (routePolyline) { routePolyline.setMap(null); routePolyline = null; }
                if (heatmap) { heatmap.setMap(null); heatmap = null; }
            }

            function clampAndFit(bounds, zoomBoost = 1) {
                if (!bounds) return;
                lastBounds = bounds;
                map.fitBounds(bounds);
                google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
                    let currentZoom = map.getZoom();
                    if (currentZoom > MAX_ZOOM) currentZoom = MAX_ZOOM;
                    if (currentZoom < MIN_ZOOM) currentZoom = MIN_ZOOM;
                    // apply a small boost so the fit is a bit tighter
                    const boosted = Math.min(currentZoom + Math.max(0, zoomBoost), MAX_ZOOM);
                    // delay to ensure fitBounds finished
                    setTimeout(() => map.setZoom(boosted), 100);
                });
            }

            async function updateLocations() {
                try {
                    const res = await fetch(`/patrol/${patrolId}/locations?shiftDateId=${shiftDateId}`);
                    const data = await res.json();
                    const locations = data.locations || [];

                    const valid = locations.filter(loc => {
                        const lat = parseFloat(loc.latitude);
                        const lng = parseFloat(loc.longitude);
                        return !isNaN(lat) && !isNaN(lng);
                    });

                    clearGuardLayers();

                    // If we have guard locations, draw polyline + markers + heatmap
                    if (valid.length > 0) {
                        const coords = valid.map(loc => new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude)));

                        routePolyline = new google.maps.Polyline({
                            path: coords,
                            geodesic: true,
                            strokeColor: "#FF0000",
                            strokeOpacity: 0.6,
                            strokeWeight: 2,
                            map: map
                        });

                        // start / end markers
                        const first = coords[0];
                        const last = coords[coords.length - 1];
                        guardMarkers.push(new google.maps.Marker({ position: first, map, label: { text: 'START', color: '#FFF' }, icon: { url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png', scaledSize: new google.maps.Size(45,45) } }));
                        if (coords.length > 1) {
                            guardMarkers.push(new google.maps.Marker({ position: last, map, label: { text: 'END', color: '#FFF' }, icon: { url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png', scaledSize: new google.maps.Size(45,45) } }));
                        }

                        // optional heatmap
                        if (typeof google.maps.visualization !== 'undefined') {
                            heatmap = new google.maps.visualization.HeatmapLayer({
                                data: coords,
                                radius: 20,
                                opacity: 0.7,
                                dissipating: true,
                                maxIntensity: 5,
                                map: map
                            });
                        }

                        coords.forEach(c => {
                            // small circle markers along route (optional): commented out to reduce clutter
                            // guardMarkers.push(new google.maps.Marker({ position: c, map }));
                        });
                    }

                    // Determine combined bounds (checkpoints + guard locations)
                    const combinedBounds = new google.maps.LatLngBounds();
                    let points = 0;
                    // include checkpoint markers in combined bounds
                    if (checkpointMarkers.length > 0) {
                        checkpointMarkers.forEach(m => {
                            combinedBounds.extend(m.getPosition());
                        });
                        points += checkpointMarkers.length;
                    }
                    if (valid.length > 0) {
                        valid.forEach(loc => {
                            combinedBounds.extend(new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude)));
                        });
                        points += valid.length;
                    }

                    if (points === 0) {
                        // nothing to show
                        map.setCenter({ lat: 51.5074, lng: -0.1278 });
                        map.setZoom(12);
                    } else if (points === 1) {
                        // Single point: center & zoom in tightly
                        const ne = combinedBounds.getNorthEast();
                        map.setCenter({ lat: ne.lat(), lng: ne.lng() });
                        // pick a tight zoom but clamp to MAX_ZOOM
                        map.setZoom(Math.min(MAX_ZOOM, 17));
                    } else {
                        // For multiple points, fit and apply a small boost to zoom tighter
                        clampAndFit(combinedBounds, 1);
                    }
                } catch (err) {
                    console.error('Error fetching patrol locations:', err);
                    // fallback: if checkpoints exist, fit them
                    if (checkpointMarkers.length > 0) {
                        clampAndFit(checkpointBounds);
                    } else {
                        map.setCenter({ lat: 51.5074, lng: -0.1278 });
                        map.setZoom(12);
                    }
                }
            }

            // initial render
            if (checkpointMarkers.length > 0) {
                // if there are only checkpoints, use them as baseline until guard data arrives
                clampAndFit(checkpointBounds);
            }
            updateLocations();

            // Auto-refresh every 15s
            const refreshInterval = 15000;
            const intervalId = setInterval(updateLocations, refreshInterval);

            // If the patrols tab is shown later, trigger resize & re-fit
            const patrolsTabBtn = document.getElementById('patrols-tab2');
            if (patrolsTabBtn) {
                patrolsTabBtn.addEventListener('shown.bs.tab', () => {
                    google.maps.event.trigger(map, 'resize');
                    if (lastBounds) clampAndFit(lastBounds);
                });
            }
        }

        // 🔸 Adds toggle, radius, opacity, gradient controls
        function addHeatmapControls(map, heatmap) {
            const controlDiv = document.createElement("div");
            controlDiv.style.background = "#fff";
            controlDiv.style.border = "1px solid #999";
            controlDiv.style.borderRadius = "6px";
            controlDiv.style.padding = "6px";
            controlDiv.style.margin = "10px";
            controlDiv.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
            controlDiv.innerHTML = `
        <button id="toggleHeatmap">Toggle Heatmap</button>
        <button id="changeGradient">Change Gradient</button>
        <button id="changeRadius">Change Radius</button>
        <button id="changeOpacity">Change Opacity</button>
    `;
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(controlDiv);

            // Default gradient from Google demo
            const gradient = [
                "rgba(0, 255, 255, 0)",
                "rgba(0, 255, 255, 1)",
                "rgba(0, 191, 255, 1)",
                "rgba(0, 127, 255, 1)",
                "rgba(0, 63, 255, 1)",
                "rgba(0, 0, 255, 1)",
                "rgba(0, 0, 223, 1)",
                "rgba(0, 0, 191, 1)",
                "rgba(0, 0, 159, 1)",
                "rgba(0, 0, 127, 1)",
                "rgba(63, 0, 91, 1)",
                "rgba(127, 0, 63, 1)",
                "rgba(191, 0, 31, 1)",
                "rgba(255, 0, 0, 1)"
            ];

            // Button actions
            controlDiv.querySelector("#toggleHeatmap").addEventListener("click", () => {
                heatmap.setMap(heatmap.getMap() ? null : map);
            });

            controlDiv.querySelector("#changeGradient").addEventListener("click", () => {
                heatmap.set("gradient", heatmap.get("gradient") ? null : gradient);
            });

            controlDiv.querySelector("#changeRadius").addEventListener("click", () => {
                heatmap.set("radius", heatmap.get("radius") === 20 ? 40 : 20);
            });

            controlDiv.querySelector("#changeOpacity").addEventListener("click", () => {
                heatmap.set("opacity", heatmap.get("opacity") === 0.7 ? 0.3 : 0.7);
            });
        }


        // 🔹 Initialize maps
        window.onload = function() {
            @foreach ($patrols as $patrol)
                initPatrolMap({{ $patrol->id }}, {{ $shiftDate->id }}, siteCheckpoints);
            @endforeach
        };

        $(document).ready(function() {
            // Open edit modal
            $(document).on("click", ".edit-patrol-btn", function() {
                $("#edit_patrol_id").val($(this).data("id"));
                $("#edit_patrol_name").val($(this).data("name"));
                $("#edit_patrol_time").val($(this).data("time"));
                $("#edit_patrol_status").val($(this).data("status"));
                $("#editPatrolModal").modal("show");
            });

            // Submit edit form
            $("#editPatrolForm").on("submit", function(e) {
                e.preventDefault();

                let id = $("#edit_patrol_id").val();
                let formData = $(this).serialize();

                $.ajax({
                    url: "/patrols/" + id,
                    type: "PUT",
                    data: formData,
                    success: function(response) {
                        $("#editPatrolModal").modal("hide");

                        let patrol = response.patrol;
                        let row = $("button.edit-patrol-btn[data-id='" + patrol.id + "']")
                            .closest("tr");

                        // Update row values directly
                        row.find("td:eq(0)").text(patrol.name); // assuming first td is name
                        row.find("td:eq(1)").text(patrol.start_time); // second column = time

                        let statusCell = row.find("td:eq(7)"); // third column = status
                        if (patrol.status === "pending") {
                            statusCell.html('<p class="bg-warning text-center">Pending</p>');
                        } else if (patrol.status === "in_progress") {
                            statusCell.html(
                                '<p class="bg-primary text-center">In Progress</p>');
                        } else if (patrol.status === "completed") {
                            statusCell.html('<p class="bg-success text-center">Completed</p>');
                        }
                        showToast(
                            "Updated successfully!", // message
                            'success', // type
                            5000 // duration in ms
                        );

                    },
                    error: function() {
                        showToast(
                            "Update failed!", // message
                            'error', // type
                            5000 // duration in ms
                        );
                    }
                });
            });

            // Delete patrol
            $(document).on("click", ".delete-patrol-btn", function() {
                if (!confirm("Are you sure you want to delete this patrol?")) return;

                let id = $(this).data("id");

                $.ajax({
                    url: "/patrols/" + id,
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function() {
                        $("button.delete-patrol-btn[data-id='" + id + "']").closest("tr")
                            .remove();
                    },
                    error: function() {
                        alert("Delete failed!");
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const timeInput = document.getElementById("absentee_start_time");

            // Set default current time (24h)
            function setCurrentTime() {
                const now = new Date();
                const h = ("0" + now.getHours()).slice(-2);
                const m = ("0" + now.getMinutes()).slice(-2);
                timeInput.value = `${h}:${m}`;
            }
            if (!timeInput.value) {
                setCurrentTime();
            }

            // Allow only HH:MM format
            timeInput.addEventListener("input", function() {
                this.value = this.value.replace(/[^0-9:]/g, ""); // only numbers + colon
            });

            // Validate on blur
            timeInput.addEventListener("blur", function() {
                const match = this.value.match(/^([01]?\d|2[0-3]):([0-5]\d)$/);
                if (!match) {
                    alert("Please enter time in 24-hour format (HH:MM). Example: 08:30 or 15:45");
                    setCurrentTime();
                    return;
                }

                // Format to HH:mm
                const h = ("0" + match[1]).slice(-2);
                const m = ("0" + match[2]).slice(-2);
                this.value = `${h}:${m}`;
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function setup24hTimeInput(inputId) {
                const input = document.getElementById(inputId);

                if (!input) return; // skip if not found

                // Default value = now (HH:mm)
                function setCurrentTime() {
                    const now = new Date();
                    const h = ("0" + now.getHours()).slice(-2);
                    const m = ("0" + now.getMinutes()).slice(-2);
                    input.value = `${h}:${m}`;
                }
                if (!input.value) {
                    setCurrentTime();
                }

                // Allow only digits and colon
                input.addEventListener("input", function() {
                    this.value = this.value.replace(/[^0-9:]/g, "");
                });

                // Validate on blur
                input.addEventListener("blur", function() {
                    const match = this.value.match(/^([01]?\d|2[0-3]):([0-5]\d)$/);
                    if (!match) {
                        alert("Please enter time in 24-hour format (HH:MM). Example: 08:30 or 15:45");
                        setCurrentTime();
                        return;
                    }
                    const h = ("0" + match[1]).slice(-2);
                    const m = ("0" + match[2]).slice(-2);
                    this.value = `${h}:${m}`;
                });
            }

            // Apply for both Book On & Book Off
            setup24hTimeInput("absentee_start_time");
            setup24hTimeInput("absentee_end_time");
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function closeTab() {
            window.close(); // Attempts to close the current tab
        }

        function unassignShift(shiftId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to unassign this shift?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Unassign',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/shifts/${shiftId}/unassign`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Unassigned!',
                                    'Shift has been unassigned successfully.',
                                    'success'
                                ).then(() => location.reload()); // refresh after success
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Failed to unassign shift.',
                                    'error'
                                );
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        });
                }
            });
        }
    </script>



    <!-- Google Maps API is loaded dynamically by the included map partial (map.blade.php) -->

    <!-- Leaflet JS -->
    <script>
        // Confirmation toast for delete actions. If `anchorEl` provided, place toast under it.
        function positionToastAtAnchor(toast, anchorEl) {
            if (!anchorEl) return false;
            const rect = anchorEl.getBoundingClientRect();

            // measure toast after it's in the DOM
            const padding = 8;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            toast.style.position = 'absolute';
            toast.style.zIndex = '999999';

            // default place below anchor
            let left = rect.left + window.scrollX;
            let top = rect.bottom + window.scrollY + padding;

            // compute size
            const box = toast.getBoundingClientRect();
            const toastWidth = box.width || toast.offsetWidth || 200;
            const toastHeight = box.height || toast.offsetHeight || 40;

            // if toast would overflow right edge, shift left so it fits
            const maxRight = window.scrollX + viewportWidth - padding;
            if (left + toastWidth > maxRight) {
                left = Math.max(padding + window.scrollX, maxRight - toastWidth);
            }

            // if toast would overflow left edge, clamp
            if (left < padding + window.scrollX) {
                left = padding + window.scrollX;
            }

            // if toast would overflow bottom edge, place above the anchor instead
            const maxBottom = window.scrollY + viewportHeight - padding;
            if (top + toastHeight > maxBottom) {
                // place above anchor
                top = rect.top + window.scrollY - toastHeight - padding;
                // if still off-screen at top, clamp to padding
                if (top < padding + window.scrollY) top = padding + window.scrollY;
            }

            toast.style.left = left + 'px';
            toast.style.top = top + 'px';
            return true;
        }

        function showConfirmToast(message, onConfirm, anchorEl) {
            const container = document.createElement('div');
            container.className = 'confirm-toast-wrapper';

            const toast = document.createElement('div');
            toast.className = 'confirm-toast';
            toast.style.background = '#fff';
            toast.style.border = '1px solid rgba(0,0,0,0.08)';
            toast.style.padding = '12px';
            toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
            toast.style.borderRadius = '8px';
            toast.style.minWidth = '220px';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '12px';

            toast.innerHTML = `
                <div style="flex:1">${message}</div>
                <div style="display:flex;gap:8px"> 
                    <button class="btn btn-sm btn-danger confirm-yes">Yes</button>
                    <button class="btn btn-sm btn-secondary confirm-no">No</button>
                </div>
            `;

            container.appendChild(toast);
            document.body.appendChild(container);

            // position under anchor if provided, otherwise bottom-right
            const placed = positionToastAtAnchor(container, anchorEl);
            if (!placed) {
                container.style.position = 'fixed';
                container.style.bottom = '20px';
                container.style.right = '20px';
                container.style.zIndex = '99999';
            }

            function cleanup() {
                if (container.parentNode) container.parentNode.removeChild(container);
            }

            toast.querySelector('.confirm-yes').addEventListener('click', function() {
                try { onConfirm(); } catch (e) { console.error(e); }
                cleanup();
            });

            toast.querySelector('.confirm-no').addEventListener('click', function() {
                cleanup();
            });
        }

        // show a short success toast under the same anchor then run callback (optional)
        function showSuccessToast(message, anchorEl, cb) {
            const container = document.createElement('div');
            container.className = 'success-toast-wrapper';

            const toast = document.createElement('div');
            toast.className = 'success-toast';
            toast.style.background = '#28a745';
            toast.style.color = '#fff';
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '6px';
            toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
            toast.style.minWidth = '160px';
            toast.style.textAlign = 'center';
            toast.textContent = message;

            container.appendChild(toast);
            document.body.appendChild(container);

            const placed = positionToastAtAnchor(container, anchorEl);
            if (!placed) {
                container.style.position = 'fixed';
                container.style.bottom = '20px';
                container.style.right = '20px';
                container.style.zIndex = '99999';
            }

            setTimeout(() => {
                if (container.parentNode) container.parentNode.removeChild(container);
                if (typeof cb === 'function') cb();
            }, 1100);
        }

        // Intercept delete shift form and show confirmation toast.
        // If the form is not marked for AJAX (data-ajax="1"), perform the DELETE via fetch,
        // then close the tab and redirect to scheduling (or a form-provided data-redirect).
        $(document).on('submit', '.delete-shift-form', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            showConfirmToast('Are you sure you want to delete this shift?', function() {
                // If another script intends to handle via AJAX, honor it when explicit
                if (form.dataset.ajax === '1') {
                    form.submit();
                    return;
                }

                const url = form.action;
                // build FormData (includes _token and _method hidden inputs)
                const fd = new FormData(form);

                // ensure method override for DELETE is present
                if (!fd.has('_method')) fd.append('_method', 'DELETE');

                const csrfInput = form.querySelector('input[name="_token"]');
                const csrf = csrfInput ? csrfInput.value : null;

                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: csrf ? { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } : { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                }).then(async res => {
                    // If server performs a redirect, follow it
                    if (res.redirected) {
                        // show success toast then redirect/close
                        showSuccessToast('Shift deleted', submitBtn, function() {
                            try { closeTab(); } catch (e) { /* ignore */ }

                            // Signal other tabs to refresh scheduling
                            try {
                                localStorage.setItem('scheduling:refresh', Date.now());
                            } catch (e) { /* ignore */ }

                            // Try to refresh opener/parent if present
                            try {
                                const redirect = res.url + (res.url.includes('?') ? '&' : '?') + 't=' + Date.now();
                                if (window.opener && !window.opener.closed) {
                                    try { window.opener.location.href = redirect; } catch (e) { /* ignore cross-origin */ }
                                } else if (window.parent && window.parent !== window) {
                                    try { window.parent.location.href = redirect; } catch (e) { /* ignore cross-origin */ }
                                }
                            } catch (e) { /* ignore */ }

                            // Finally navigate this window to the scheduling page
                            window.location = res.url + (res.url.includes('?') ? '&' : '?') + 't=' + Date.now();
                        });
                        return;
                    }

                    if (res.ok) {
                        // try JSON response for redirect
                        try {
                            const data = await res.json();
                            const redirect = data?.redirect || form.dataset.redirect || '/scheduling';
                            showSuccessToast('Shift deleted', submitBtn, function() {
                                try { closeTab(); } catch (e) { /* ignore */ }

                                // Signal other tabs to refresh scheduling
                                try {
                                    localStorage.setItem('scheduling:refresh', Date.now());
                                } catch (e) { /* ignore */ }

                                // Try to refresh opener/parent if present
                                try {
                                    const dest = redirect + (redirect.includes('?') ? '&' : '?') + 't=' + Date.now();
                                    if (window.opener && !window.opener.closed) {
                                        try { window.opener.location.href = dest; } catch (e) { /* ignore cross-origin */ }
                                    } else if (window.parent && window.parent !== window) {
                                        try { window.parent.location.href = dest; } catch (e) { /* ignore cross-origin */ }
                                    }
                                } catch (e) { /* ignore */ }

                                // Finally navigate this window to the scheduling page
                                window.location = redirect + (redirect.includes('?') ? '&' : '?') + 't=' + Date.now();
                            });
                            return;
                        } catch (e) {
                            // not JSON, fallback to dataset redirect
                            const redirect = form.dataset.redirect || '/scheduling';
                            showSuccessToast('Shift deleted', submitBtn, function() {
                                try { closeTab(); } catch (e) { /* ignore */ }

                                // Signal other tabs to refresh scheduling
                                try {
                                    localStorage.setItem('scheduling:refresh', Date.now());
                                } catch (e) { /* ignore */ }

                                // Try to refresh opener/parent if present
                                try {
                                    const dest = redirect + (redirect.includes('?') ? '&' : '?') + 't=' + Date.now();
                                    if (window.opener && !window.opener.closed) {
                                        try { window.opener.location.href = dest; } catch (e) { /* ignore cross-origin */ }
                                    } else if (window.parent && window.parent !== window) {
                                        try { window.parent.location.href = dest; } catch (e) { /* ignore cross-origin */ }
                                    }
                                } catch (e) { /* ignore */ }

                                // Finally navigate this window to the scheduling page
                                window.location = redirect + (redirect.includes('?') ? '&' : '?') + 't=' + Date.now();
                            });
                            return;
                        }
                    }

                    // Non-ok response: fallback to normal submit to allow server to render errors
                    form.submit();
                }).catch(err => {
                    console.error('Delete request failed, falling back to normal submit', err);
                    form.submit();
                });
            }, submitBtn);
        });
    </script>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
@endsection
