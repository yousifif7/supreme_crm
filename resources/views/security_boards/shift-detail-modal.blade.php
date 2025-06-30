<div class="row" id="eventModal">
    <div class="tabs-parent_main">
        <div class="tabs-parent nav nav-tabs" role="tablist">
            <button class="nav-link active" id="info-tab2" data-bs-toggle="tab" data-bs-target="#basic-info2" type="button"
                role="tab" aria-controls="basic-info2" aria-selected="true">Rota Detail</button>
            <button class="nav-link" id="address-tab2" data-bs-toggle="tab" data-bs-target="#address2" type="button"
                role="tab" aria-controls="address2" aria-selected="false">Office Validation</button>

            <button class="nav-link" id="logs-tab2" data-bs-toggle="tab" data-bs-target="#logs2" type="button"
                role="tab" aria-controls="logs2" aria-selected="false">Logs</button>
        </div>

        <div class="expiry_date">
            <div class="form-check form-check-lg form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="switch-lg">
                <label class="form-check-label" for="switch-lg">
                    Stand-downSIA Number : <span id="sia_number"> {{ $shiftDate->staff?->sia_licence ?? '' }}</span> &nbsp;&nbsp;Expiry: <span
                        id="sia_expiry">{{ $shiftDate->staff?->sia_expiry ?? '' }}</span>
                </label>
            </div>
        </div>
    </div>


    <div class="tab-content rota-detail_tab-content" id="myTabContent2">
        <div class="tab-pane fade show active" id="basic-info2" role="tabpanel" aria-labelledby="info-tab2">
            <div class="modal-body pb-0 ">
                <div class="row">
                    <div class="col-md-6 col-12">
                        <div class="upper-stats-box">
                            <div class="profile-detail">
                                <div class="avater">
                                    <img src="{{ $shiftDate->staff?->profilePictureUrl() ?? 'uploads/no.png' }}" class="profile-avater profile_picture" id="profile_picture">
                                </div>


                                <div class="profile-details">
                                    <h6 id="name">{{ $shiftDate->staff?->fore_name ?? '' }}</h6>
                                    <div class="mb-1">
                                        <i class="ti ti-phone"></i>
                                        <span id="phone_number">{{ $shiftDate->staff?->contact ?? '' }}</span>
                                    </div>
                                    <div>
                                        <i class="ti ti-mail"></i>
                                        <span id="email">{{ $shiftDate->staff?->email ?? '' }}</span>
                                    </div>
                                    <button id="assignShiftBtn" type="button" class="btn btn-danger mt-2 {{ $shiftDate->is_assign ? 'd-none' : '' }}">
                                        Assign Shift
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
                                    <span id="date">{{ $shiftDate->shift_date ?? '' }}</span>
                                </div>
                                <div class="box">
                                    <h6>Shift Time</h6>
                                    <span id="shift_time">{{ \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('h:i A') ?? '' }} - {{ \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('h:i A') ?? '' }} ({{ sprintf('%02d hr %02d min', floor($shiftDate->total_hours), round(($shiftDate->total_hours - floor($shiftDate->total_hours)) * 60)) }})</span>
                                </div>
                                <div class="box">
                                    <h6>Customer</h6>
                                    <span id="client_name">{{ $shiftDate->shift->client->client_name ?? '' }}</span>
                                </div>
                                <div class="box">
                                    <h6>Site Name</h6>
                                    <span id="site_name">{{ $shiftDate->shift->site->site_name ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div id="map-first"></div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="book-on_box">
                            <div class="profile-detail">
                                <div class="avater">
                                    <img src="{{ $shiftDate->staff?->profilePictureUrl() ?? 'uploads/no.png' }}" class="profile-avater profile_picture">
                                </div>
                                <div class="profile-details">
                                    <h6>Book on</h6>
                                    <div class="mb-1">
                                        <i class="ti ti-calendar"></i>
                                        <span id="book_on">{{ $shiftDate->shift_date . ", at  " . $shiftDate->absentee_start_time }}</span>
                                    </div>
                                    <div>
                                        <i class="ti ti-map-pin"></i>
                                        <span id="site_address1">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                    </div>
                                </div>

                            </div>
                            <form id="bookonForm" action="{{ route('shift.bookon.store') }}">
                                @csrf
                                <input type="hidden" id="book_on_id" name="book_on_id" value="{{ $shiftDate->id }}">
                                <input type="time" id="absentee_start_time" name="absentee_start_time"
                                    value="{{ $shiftDate->absentee_start_time ?? date('h:i') }}" class="form-control mb-2">
                                <button type="submit" class="btn btn-primary">set book on time</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="book-off_box">
                            <div class="profile-detail">
                                <div class="avater">
                                    <img src="{{ $shiftDate->staff?->profilePictureUrl() ?? 'uploads/no.png' }}" class="profile-avater profile_picture">
                                </div>
                                <div class="profile-details">
                                    <h6>Book Off </h6>
                                    <div class="mb-1">
                                        <i class="ti ti-calendar"></i>
                                        <span id="book_off"> {{ $shiftDate->shift_date . ", at  " . $shiftDate->absentee_end_time }}</span>
                                    </div>
                                    <div>
                                        <i class="ti ti-map-pin"></i>
                                        <span id="site_address2">{{ $shiftDate->shift->site->address ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                            <form id="bookoffForm" action="{{ route('shift.bookoff.store') }}">
                                @csrf
                                <input type="hidden" id="book_off_id" name="book_off_id" value="{{ $shiftDate->id }}">
                                <input type="time" id="absentee_end_time" name="absentee_end_time"
                                    value="{{ $shiftDate->absentee_end_time ?? date('h:i') }}" class="form-control mb-2">
                                <button type="submit" class="btn btn-primary">set book off time</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="address2" role="tabpanel" aria-labelledby="address-tab2">
            @if($shiftDate->staff)
                <div class="images-grid">
                    <div class="parent_image-wrapper">
                        <div class="image-wrapper">
                            <div class="badge">Profile</div>
                            <img src="{{ $shiftDate->staff?->profilePictureUrl() ?? 'uploads/no.png' }}" class="profile_picture" alt="Selfie 1" />
                        </div>
                        {{-- $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file']; --}}
                        <div class=" id_card_wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('sia_licence_file') }}" target="_blank">
                                <div class="badge">SIA CARD</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('sia_licence_file', true) }}" style="max-width: 300px; max-height: 210px; object-fit: cover;"
                                    alt="SIA Card" />
                            </a>
                        </div>

                        <div class=" id_card_wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('passport_file') }}" target="_blank">
                                <div class="badge">Passport</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('passport_file', true) }}" style="max-width: 300px; max-height: 210px; object-fit: cover;"
                                    alt="SIA Card" />
                            </a>
                        </div>
                    </div>
                    <div class="parent_image-wrapper">
                        <div class="image-wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('proof_of_address_file') }}" target="_blank">
                                <div class="badge">Proof of Address</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('proof_of_address_file', true) }}" alt="Selfie 2" />
                            </a>
                        </div>

                        <div class="image-wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('ni_letter_file') }}" target="_blank">
                                <div class="badge">NI Letter</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('ni_letter_file', true) }}" alt="Selfie 2" />
                            </a>
                        </div>

                        <div class="image-wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('first_aid_certificate_file') }}" target="_blank">
                                <div class="badge">First Aid Certificate</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('first_aid_certificate_file', true) }}" alt="Selfie 2" />
                            </a>
                        </div>

                        <div class="image-wrapper">
                            <a href="{{ $shiftDate->staff?->fileUrl('act_certificate_file') }}" target="_blank">
                                <div class="badge">ACT Certificate</div>
                                <img src="{{ $shiftDate->staff?->fileUrl('act_certificate_file', true) }}" alt="Selfie 2" />
                            </a>
                        </div>
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
            <div class="modal-body">Logs content goes here.</div>
        </div>
    </div>
</div>

<script>
    $(document).on('submit', '#bookonForm, #bookoffForm', function(e) {
        e.preventDefault();
        var actionUrl = $(this).attr('action');
        $.ajax({
            url: `${actionUrl}`,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#success_message').html('Shift bookon updated successfully!');
                $('#eventModal').modal('hide');
                $('#success_modal').modal('show');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    // Show specific validation error
                    if (xhr.responseJSON.error) {
                        alert(xhr.responseJSON.error);
                    } else if (xhr.responseJSON.errors) {
                        // Multiple field errors
                        let messages = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        alert(messages);
                    }
                } else {
                    alert('An unexpected error occurred while assigning the shift.');
                }
            }
        });
    });

    $(document).on('click', '#assignShiftBtn', function() {
        $('#shift_id').val({{ $shiftDate->id }});
        $('#assignShiftModal').modal('show');
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
</script>
