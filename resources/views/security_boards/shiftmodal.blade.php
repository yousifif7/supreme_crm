<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="modal fade" id="add_shift">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Shift</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form method="POST" id="add_shift-form" action="{{ route('shifts.store') }}">
                @csrf
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                        tabindex="0">
                        <div class="modal-body pb-0">
                            <div class="shift-wrapper">
                                <div class="shift-group border rounded p-3 mb-3">
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Client <span
                                                        class="text-danger">*</span></label>
                                                <select name="client_id[]" class="form-select select2" id="clientSelect">
                                                    <option value="">--choose--</option>
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}">
                                                            {{ $client->first_name }} {{ $client->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_client_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Site <span
                                                        class="text-danger">*</span></label>
                                                <select name="site_id[]" class="form-select" id="siteSelect">
                                                    <option value="">--choose--</option>
                                                    {{-- @foreach ($sites as $site)
                                                        <option value="{{ $site->id }}">
                                                            {{ $site->site_name }}</option>
                                                    @endforeach --}}
                                                </select>
                                                <span class="text-danger form-error error_site_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Parent company</label>
                                                <select name="company_id[]" class="form-select">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_company_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Start <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="start_shift[]" id="start_shift"
                                                    placeholder="HH:MM" class="form-control time-input"
                                                    value="{{ old('start_shift.0') }}">
                                                <span class="text-danger form-error error_start_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">End <span class="text-danger">*</span></label>
                                                <input type="text" name="end_shift[]" id="end_shift"
                                                    class="form-control time-input" placeholder="HH:MM">
                                                <span class="text-danger form-error error_end_shift"></span>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Break (mins) </label>
                                                <input type="text" name="break-mins_shift[]"
                                                    class="form-control numeric-input">
                                                <span class="text-danger form-error error_break-mins_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Staff</label>
                                                <input type="text" name="number_shift[]" placeholder="number"
                                                    class="form-control">
                                                <span class="text-danger form-error error_number_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Site rate </label>
                                                <input type="text" name="site_rate[]" placeholder="$"
                                                    class="form-control numeric-input siteRate">
                                                <span class="text-danger form-error error_site_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Service Type</label>
                                                <select class="form-select" name="service_type_1[]">
                                                    <option value="">--choose--</option>
                                                    @foreach ($services as $service)
                                                        <option value="{{ $service->id }}">{{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_service_type_1"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Policies</label>
                                                <select class="form-select" name="training_id[]" multiple>
                                                    @php
                                                        $trainings = App\Models\TrainingMaterial::whereNotNull(
                                                            'pdf_url',
                                                        )->get();
                                                    @endphp
                                                    @foreach ($trainings as $training)
                                                        <option value="{{ $training->id }}">{{ $training->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_training_id"></span>
                                                <span><small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to
                                                        select multiple policies.</small></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">From <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="from_shift[]" class="form-control">
                                                <span class="text-danger form-error error_from_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">To <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="to_shift[]" class="form-control">
                                                <span class="text-danger form-error error_to_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Comment </label>
                                                <input type="text" name="comments[]" placeholder="Comment"
                                                    class="form-control">
                                                <span class="text-danger form-error error_comments"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label">Select Days</label>
                                            <div class="day-selector d-flex gap-2 flex-wrap">
                                                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                                    <div class="day-box" data-day="{{ $day }}">
                                                        {{ $day }} <span class="checkmark">✔</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <input type="hidden" name="days[]" id="selectedDays">
                                            <span class="text-danger form-error error_days"></span>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Staff</label>
                                                <select class="form-select StaffSelect" name="staff_id[]"
                                                    id="StaffSelect">
                                                    <option value="">--choose--</option>
                                                    @foreach ($staffs as $staff)
                                                        <option value="{{ $staff->id }}">
                                                            {{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_staff_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Employee Rate</label>
                                                <input type="text" name="employee_rate[]" placeholder="$"
                                                    class="form-control numeric-input staffRate">
                                                <span class="text-danger form-error error_employee_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Service Type </label>
                                                <select class="form-select" name="service_type_2[]">
                                                    <option value="">--choose--</option>
                                                    @foreach ($services as $service)
                                                        <option value="{{ $service->id }}">{{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error error_select_type_2"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Start</label>
                                                <input type="time" name="start[]" class="form-control">
                                                <span class="text-danger form-error error_start"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Subcontractor</label>
                                                <select class="form-select" name="subcontractor_id[]">
                                                    <option value="">--choose--</option>
                                                    @foreach ($subcontractors as $subcontractor)
                                                        <option value="{{ $subcontractor->id }}">
                                                            {{ $subcontractor->first_name }}
                                                            {{ $subcontractor->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error"
                                                    id="error_subcontractor_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">End </label>
                                                <input type="time" name="end[]" class="form-control">
                                                <span class="text-danger form-error error_end"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">PO Number </label>
                                                <input type="text" name="po_number[]" placeholder="PO Number"
                                                    class="form-control">
                                                <span class="text-danger form-error error_po_number"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Lost Time </label>
                                                <input type="text" name="lost_time[]" placeholder="Lost Time"
                                                    class="form-control">
                                                <span class="text-danger form-error error_lost_time"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">PO Rate </label>
                                                <input type="text" name="po_rate[]" placeholder="PO Rate"
                                                    class="form-control numeric-input">
                                                <span class="text-danger form-error error_po_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Manager (1) </label>
                                                <select class="form-select" name="manager_1_id[]">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_manager_1_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Manager (2) </label>
                                                <select class="form-select" name="manager_2_id[]">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_manager_2_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="restrict_start_time[]" value="1">
                                                    <label class="form-label mb-0">Restrict shift start time
                                                    </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="enforce_picture_check[]" value="1">
                                                    <label class="form-label mb-0">Enforce picture check </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="restrict_location_check[]" value="1">
                                                    <label class="form-label mb-0">Restrict start shift
                                                        location check </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="autoCheckcallToggle" checked>
                                                        <label class="form-check-label form-label"
                                                            for="autoCheckcallToggle">Enable Auto Checkcalls</label>
                                                    </div>
                                                </div>

                                                <div class="checkcall-section" id="checkcall-section0">
                                                    <h5>Check Calls</h5>
                                                    <div class="checkcall-rows"><!-- rows go here --></div>
                                                    <button type="button"
                                                        class="btn btn-sm btn-primary my-3 addCheckCallRow">
                                                        + Add Check Call
                                                    </button>
                                                </div>
                                                <div class="clear-fix"></div>
                                                <div class="col-md-12 text-end">
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-shift">Remove</button>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <button type="button" class="btn btn-success btn-sm addShiftGroup">+
                                                Add More Shifts</button>

                                        </div>

                                    </div> <!-- .row -->
                                </div> <!-- .shift-group -->
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="add_shift-form" id="saveshift" class="btn btn-primary">Save
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- <script>
    document.querySelectorAll('.time-input').forEach(input => {
        input.addEventListener('blur', function () {
            if (/^\d{1,2}$/.test(this.value)) {
                // e.g. "22" → "22:00"
                this.value = this.value.padStart(2, '0') + ':00';
            }
        });
    });
</script> --}}


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

    function customMatcher(params, data) {
        if ($.trim(params.term) === '') return data;

        let term = params.term.toLowerCase();
        let text = data.text.toLowerCase();

        return text.includes(term) ? data : null;
    }

    $(document).ready(function() {
        $('.StaffSelect').select2({
            placeholder: "--choose--",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#add_shift'), // make sure this matches your modal ID
            minimumResultsForSearch: 0 // force search bar for single select
        });
    });

    
</script>
