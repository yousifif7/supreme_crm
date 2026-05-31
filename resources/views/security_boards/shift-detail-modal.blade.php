<style>
    .btn-assign {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);
        transition: all 0.2s ease-in-out;
    }

    .btn-assign:hover {
        background: linear-gradient(135deg, #20c997, #28a745);
        box-shadow: 0 6px 14px rgba(40, 167, 69, 0.6);
        transform: translateY(-2px);
    }
</style>
<div class="row" id="eventModal">
    <div class="tabs-parent_main">
        <div class="tabs-parent nav nav-tabs" role="tablist">
            <button class="nav-link active" id="info-tab2" data-bs-toggle="tab" data-bs-target="#basic-info2" type="button"
                role="tab" aria-controls="basic-info2" aria-selected="true">Rota Detail</button>
            <button class="nav-link" id="address-tab2" data-bs-toggle="tab" data-bs-target="#address2" type="button"
                role="tab" aria-controls="address2" aria-selected="false">Office Validation</button>
            <button class="nav-link" id="logs-tab2" data-bs-toggle="tab" data-bs-target="#logs2" type="button"
                role="tab" aria-controls="logs2" aria-selected="false">Logs</button>
            <button class="nav-link" id="checkcalls-tab2" data-bs-toggle="tab" data-bs-target="#checkcalls"
                type="button" role="tab" aria-controls="checkcalls" aria-selected="false">Check Calls</button>
        </div>

        <div class="expiry_date">
            <div class="form-check form-check-lg form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="switch-lg">
                <label class="form-check-label" for="switch-lg">
                    Stand-downSIA Number : <span id="sia_number"> {{ $shiftDate->staff?->sia_licence ?? '' }}</span>
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
                                    <h6 id="name">{{ $shiftDate->staff?->first_name ?? '' }}
                                        {{ $shiftDate->staff?->last_name ?? '' }} <a href="#"
                                            onclick="editShift({{ $shiftDate->id }})">Edit</a></h6>
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
                                    <span id="site_address">{{ $shiftDate->shift->site->address ?? '' }}</span>
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
                                    $firstLocation = App\Models\Location::where('shiftdate_id', $shiftDate->id)
                                        ->orderBy('timestamp', 'asc')
                                        ->first();

                                    $lastLocation = App\Models\Location::where('shiftdate_id', $shiftDate->id)
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
                                            id="book_on">{{ (format_date($shiftDate->shift_date) ?: '') . ', at  ' . ($shiftDate->absentee_start_time ?? '') }}</span>
                                    </div>
                                    <div>
                                        <i class="ti ti-map-pin"></i>
                                        <span id="site_address1">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                    </div>
                                </div>

                            </div>
                            <form id="bookonForm" action="{{ route('shift.bookon.store') }}">
                                @csrf
                                <input type="hidden" id="book_on_id" name="book_on_id"
                                    value="{{ $shiftDate->id }}">

                                <input type="text" id="absentee_start_time" name="absentee_start_time"
                                    placeholder="HH:MM" class="form-control mb-2">

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
                                        <span id="book_off">
                                            {{ (format_date($shiftDate->shift_date) ?: '') . ', at  ' . ($shiftDate->absentee_end_time ?? '') }}</span>
                                    </div>
                                    <div>
                                        <i class="ti ti-map-pin"></i>
                                        <span id="site_address2">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                            <form id="bookoffForm" action="{{ route('shift.bookoff.store') }}">
                                @csrf
                                <input type="hidden" id="book_off_id" name="book_off_id"
                                    value="{{ $shiftDate->id }}">
                                <input type="text" id="absentee_end_time" name="absentee_end_time"
                                    placeholder="HH:MM" class="form-control mb-2">
                                <button type="submit" class="btn btn-danger">Set book off time</button>
                            </form>
                        </div>
                    </div>
                    @if ($shiftDate->staff_id)
                        @php
                            // $employee= App\Models\Employee::find($shiftDate->staff_id);
                            $user = App\Models\User::role('security_staff')->where('id', $shiftDate->staff_id)->first();
                        @endphp
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
                                            <a href="{{ $staff?->fileUrl('sia_licence_file') }}" target="_blank"
                                                class="view-btn">
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
                                            <a href="{{ $staff?->fileUrl('act_certificate_file') }}" target="_blank"
                                                class="view-btn">
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
                                            alt="Proof of Address 1" class="document-image" />
                                        <div class="document-overlay">
                                            <a href="{{ $staff?->fileUrl('proof_of_address_file') }}" target="_blank"
                                                class="view-btn">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="document-label">
                                        <h6 class="mb-0">Proof of Address 1</h6>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($staff->proof_of_address_file_2)
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="document-card">
                                    <div class="document-image-wrapper">
                                        <img src="{{ asset($staff?->fileUrl('proof_of_address_file_2', true)) }}"
                                            alt="Proof of Address 2" class="document-image" />
                                        <div class="document-overlay">
                                            <a href="{{ $staff?->fileUrl('proof_of_address_file_2') }}" target="_blank"
                                                class="view-btn">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="document-label">
                                        <h6 class="mb-0">Proof of Address 2</h6>
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
                                            <a href="{{ $staff?->fileUrl('ni_letter_file') }}" target="_blank"
                                                class="view-btn">
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
                                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
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
                                    \App\Models\CheckCallMedia::where('check_call_id', $checkcall->id)->get() ??
                                    collect();
                            @endphp
                            <tr>
                                <td>{{ $checkcall?->name }}</td>
                                <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($scheduled_time->started_at ?? '')->format('H:i') }}
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
                            <input type="text" class="form-control" name="name" id="checkpoint_name" required>
                        </div>
                        <div class="mb-3">
                            <label>Scheduled Time</label>
                            <input type="datetime" class="form-control" name="scheduled_time" id="scheduled_time"
                                required>
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


    @php
        $apiKey = env('GOOGLE_MAPS_API_KEY');
    @endphp
    <script>
        $(document).off('submit', '#bookonForm, #bookoffForm').on('submit', '#bookonForm, #bookoffForm', function(e) {
            e.preventDefault();
            var actionUrl = $(this).attr('action');

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json', // ensures proper parsing
                success: function(response) {
                    if (response.success) {
                        // Use a toast or alert instead of hidden div
                        toast_success(response
                            .success); // create a toast_success function if you don't have one
                        closeBsModal('#eventModal'); // close the modal AFTER showing toast
                    } else {
                        toast_danger('Unexpected response from server.');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON) {
                        if (xhr.responseJSON.error) {
                            toast_danger(xhr.responseJSON.error);
                        } else if (xhr.responseJSON.errors) {
                            let messages = Object.values(xhr.responseJSON.errors).flat().join('\n');
                            toast_danger(messages);
                        }
                    } else {
                        toast_danger('An unexpected error occurred while assigning the shift.');
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
                    location.reload(); // Refresh table
                },
                error: function(xhr) {
                    alert('Error updating check call');
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
                    location.reload();
                },
                error: function() {
                    alert('Error deleting check call');
                }
            });
        });
    </script>

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
    </style>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=visualization"
        async defer></script>
