<!-- Edit Shift -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="modal fade" id="edit_shift">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Shift</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form method="POST" id="edit_shift-form">
                @csrf
                <input type="hidden" name="shift_id" id="shift_id">
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                        tabindex="0">
                        <div class="modal-body pb-0">
                            <div class="shift-wrapper">
                                <div class="shift-group">
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Staff</label>
                                                <select name="staff_id" class="form-select select2_modal select2" id="staff_id">
                                                    <option value="">--choose--</option>
                                                        @php
                                                            $sortedStaffs = $staffs->sortBy(function ($s) {
                                                                return strtolower(trim($s->first_name . ' ' . ($s->last_name ?? '')));
                                                            });
                                                        @endphp
                                                    @foreach ($sortedStaffs as $staff)
                                                        <option value="{{ $staff->id }}">
                                                            {{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_client_id"></span>
                                            </div>
                                        </div>

                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Subcontractor</label>
                                                <select name="subcontractor_id" id="subcontractor" class="form-select SubcontractorSelectEdit">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_subcontractor_id"></span>
                                            </div>
                                        </div>

                                        @hasanyrole('superadmin|admin')       
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Shift Date</label>
                                                <input type="date" name="shift_date" id="shift_date"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_shift_date_shift"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Start</label>
                                                <input type="text" name="start_shift" id="start_shift"
                                                    placeholder="HH:MM" class="form-control time-input"
                                                    value="{{ old('start_shift.0') }}">
                                                <span class="text-danger form-error error_start_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">End</label>
                                                <input type="text" name="end_shift" id="end_shift"
                                                    class="form-control time-input" placeholder="HH:MM">
                                                <span class="text-danger form-error error_end_shift"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Guard Rate</label>
                                                <input type="text" name="guard_rate" id="guard_rate" placeholder="£"
                                                    class="form-control numeric-input">
                                                <span class="text-danger form-error" id="error_guard_rate"></span>
                                            </div>
                                        </div>
										<!--
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Book on</label>
                                                <input type="text" name="book_on" id="book_on" class="form-control"
                                                  //  value="{{ isset($shiftDate) ? \Carbon\Carbon::parse($shiftDate->absentee_start_time ?? $shiftDate->start_time)->format('H:i') : '' }}">
                                                <span class="text-danger form-error time-input"
                                                    id="error_book_on"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Book off</label>
                                                <input type="text" name="book_off" id="book_off"
                                                    class="form-control"
                                                    value="{{ isset($shiftDate) ? \Carbon\Carbon::parse($shiftDate->absentee_end_time ?? $shiftDate->end_time)->format('H:i') : '' }}">
                                                <span class="text-danger form-error time-input"
                                                    id="error_book_off"></span>
                                            </div>
                                        </div>
-->
                                        @endhasanyrole

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Status </label>
                                                <select class="form-select" id="status_id" name="status_id">
                                                    <option value="">--choose--</option>
                                                    @php
                                                        $statusLabels = [
                                                            0 => 'Pending',
                                                            1 => 'Dispatched',
                                                            2 => 'Accepted',
                                                            3 => 'Started',
                                                            4 => 'Ended',
                                                            5 => 'Rejected',
                                                            6 => 'Cancelled',
                                                            7 => 'Pre-start',
                                                            8 => 'Await-finish',
                                                        ];
                                                    @endphp
                                                    @foreach ($statusLabels as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_status_id"></span>
                                            </div>
                                        </div>
                                    </div> <!-- .row -->
                                </div> <!-- .shift-group -->
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="edit_shift-form" id="editshift"
                                class="btn btn-primary">Update </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>


    document.addEventListener("DOMContentLoaded", function() {
        // Apply Flatpickr to all inputs with class .time-input
        flatpickr("input.time-input", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i", // Save as 24h format
            time_24hr: true,
            minuteIncrement: 5,
            allowInput: true
        });

    });


</script>
