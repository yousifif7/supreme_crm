<div class="modal fade" id="multiEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Multi Edit to Selected Shifts</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form id="multiEditForm" method="POST">
                @csrf
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                        <div class="modal-body pb-0">
                            <div class="shift-wrapper">
                                <div class="shift-group">
                                    <div class="row">

                                        <!-- Staff -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="staff_id" class="form-label">Staff</label>
                                                <select name="staff_id" id="staff_id"
                                                    class="form-select select2_edit_modal">
                                                    <option value="">-- Choose --</option>
                                                    @php
                                                        $sortedStaffs = $staffs->sortBy(function($s) {
                                                            return strtolower(trim($s->first_name . ' ' . ($s->last_name ?? '')));
                                                        });
                                                    @endphp
                                                    @foreach ($sortedStaffs as $staff)
                                                        <option value="{{ $staff->id }}"
                                                            data-first="{{ strtolower($staff->first_name) }}"
                                                            data-last="{{ strtolower($staff->last_name) }}">
                                                            {{ $staff->first_name }} {{ $staff->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_staff_id"></span>
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

                                        <!-- Shift Date -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Shift Date</label>
                                                <input type="date" name="shift_dates" id="multiAssignDate"
                                                    class="form-control">
                                                <span class="text-danger form-error error_shift_dates"></span>
                                            </div>
                                        </div>

                                        <!-- Start -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Start</label>
                                                <input type="text" name="start_time" id="multiAssignStartTime"
                                                    placeholder="HH:MM" class="form-control time-input">
                                                <span class="text-danger form-error error_start_time"></span>
                                            </div>
                                        </div>

                                        <!-- End -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">End</label>
                                                <input type="text" name="end_time" id="multiAssignEndTime"
                                                    placeholder="HH:MM" class="form-control time-input">
                                                <span class="text-danger form-error error_end_time"></span>
                                            </div>
                                        </div>

                                        {{-- <!-- Book On -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Book On</label>
                                                <input type="text" name="book_on" id="multiAssignBookOn"
                                                    placeholder="HH:MM" class="form-control time-input">
                                                <span class="text-danger form-error error_book_on"></span>
                                            </div>
                                        </div>

                                        <!-- Book Off -->
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Book Off</label>
                                                <input type="text" name="book_off" id="multiAssignBookOff"
                                                    placeholder="HH:MM" class="form-control time-input">
                                                <span class="text-danger form-error error_book_off"></span>
                                            </div>
                                        </div> --}}

                                    </div> <!-- .row -->
                                </div> <!-- .shift-group -->
                            </div>
                        </div>

                        <!-- Hidden shift IDs -->
                        <div id="multiEditShiftInputs"></div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
</div>
