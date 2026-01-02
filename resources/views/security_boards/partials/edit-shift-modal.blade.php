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
                                <div class="shift-group border rounded p-3 mb-3">
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Client <span
                                                        class="text-danger">*</span></label>
                                                <select name="client_id" class="form-select select2_client" id="clientSelect">
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
                                                <select name="site_id" class="form-select select2_site" id="siteSelect">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_site_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Parent company</label>
                                                <select name="company_id" class="form-select">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_company_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Shift Date <span class="text-danger">*</span></label>
                                                <input type="date" name="shift_date" id="shift_date" class="form-control">
                                                <span class="text-danger form-error error_shift_date"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Start <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="start_shift" id="start_shift"
                                                    placeholder="HH:MM" class="form-control time-input"
                                                    value="{{ old('start_shift.0') }}">
                                                    <span class="text-danger form-error error_start_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">End <span class="text-danger">*</span></label>
                                                <input type="text" name="end_shift" id="end_shift"
                                                    class="form-control time-input" placeholder="HH:MM">
                                                <span class="text-danger form-error error_end_shift"></span>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Break (mins) </label>
                                                <input type="text" name="break-mins_shift"
                                                    class="form-control numeric-input">
                                                <span class="text-danger form-error error_break-mins_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Quantity <small class="text-muted">(number of identical shifts to create)</small></label>
                                                <input type="number" name="number_shift" min="1" value="1"
                                                    class="form-control" title="Enter how many identical shifts to create (default 1)">
                                                <span class="text-danger form-error error_number_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Site rate </label>
                                                <input type="text" name="site_rate" placeholder="£"
                                                    class="form-control numeric-input siteRate">
                                                <span class="text-danger form-error error_site_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Service Type</label>
                                                <select class="form-select" name="service_type_1">
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
                                                <label class="form-label">From <span class="text-danger">*</span></label>
                                                <input type="date" name="from_shift" class="form-control">
                                                <span class="text-danger form-error error_from_shift"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">To <span class="text-danger">*</span></label>
                                                <input type="date" name="to_shift" class="form-control">
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
                                            <input type="hidden" name="days" id="selectedDays">
                                            <span class="text-danger form-error error_days"></span>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Staff</label>
                                                <select class="form-select StaffSelect" name="staff_id"
                                                    id="staff_id">
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
                                                <input type="text" name="employee_rate" id="guard_rate" placeholder="£"
                                                    class="form-control numeric-input staffRate">
                                                <span class="text-danger form-error error_employee_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Select Service Type </label>
                                                <select class="form-select" name="service_type_2">
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
                                                <label class="form-label">Book On</label>
                                                <input type="time" name="book_on" id="book_on" class="form-control">
                                                <span class="text-danger form-error error_book_on"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Book Off</label>
                                                <input type="time" name="book_off" id="book_off" class="form-control">
                                                <span class="text-danger form-error error_book_off"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status_id" id="status_id">
                                                    <option value="0">Unassigned</option>
                                                    <option value="1">Pending</option>
                                                    <option value="2">Accepted</option>
                                                    <option value="3">Booked On</option>
                                                    <option value="4">Booked Off</option>
                                                    <option value="5">Completed</option>
                                                    <option value="6">Rejected</option>
                                                    <option value="7">Cancelled</option>
                                                    <option value="8">No Show</option>
                                                </select>
                                                <span class="text-danger form-error error_status_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">PO Number </label>
                                                <input type="text" name="po_number" placeholder="PO Number"
                                                    class="form-control">
                                                <span class="text-danger form-error error_po_number"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Lost Time </label>
                                                <input type="text" name="lost_time" placeholder="Lost Time"
                                                    class="form-control">
                                                <span class="text-danger form-error error_lost_time"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">PO Rate </label>
                                                <input type="text" name="po_rate" placeholder="PO Rate"
                                                    class="form-control numeric-input">
                                                <span class="text-danger form-error error_po_rate"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Manager (1) </label>
                                                <select class="form-select" name="manager_1_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_manager_1_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Manager (2) </label>
                                                <select class="form-select" name="manager_2_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                <span class="text-danger form-error error_manager_2_id"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="restrict_start_time" value="1">
                                                    <label class="form-label mb-0">Restrict shift start time
                                                    </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="enforce_picture_check" value="1">
                                                    <label class="form-label mb-0">Enforce picture check </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <input type="checkbox" class="form-check"
                                                        name="restrict_location_check" value="1">
                                                    <label class="form-label mb-0">Restrict start shift
                                                        location check </label>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input autoCheckcallToggle" type="checkbox"
                                                            name="auto_checkcall_enabled" checked>
                                                        <label class="form-check-label form-label"
                                                            >Enable Auto Checkcalls</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3 d-flex gap-2 align-items-center requireMediaToggleWrapper">
                                                    <div class="form-check form-switch mb-3">
                                                            <input class="form-check-input requireMediaToggle" type="checkbox"
                                                                name="require_media_upload" checked>
                                                            <label class="form-check-label form-label"
                                                                >Require Media Upload for checkcalls</label>
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
                            <button type="submit" form="edit_shift-form" id="editshift" class="btn btn-primary">Update
                            </button>
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
    
    function customMatcher(params, data) {
        if ($.trim(params.term) === '') return data;

        let term = params.term.toLowerCase();
        let text = data.text.toLowerCase();

        return text.includes(term) ? data : null;
    }

    $(document).ready(function() {
        // Initialize Select2 for edit shift modal
        $('#edit_shift .select2_client, #edit_shift .select2_site, #edit_shift .StaffSelect').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    placeholder: "--choose--",
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#edit_shift'),
                    minimumResultsForSearch: 0
                });
            }
        });

        // Handle client -> site population for edit shift modal
        $('#edit_shift').on("change select2:select", "#clientSelect, .select2_client", function(e) {
            var $target = $(e.target && e.target.nodeName === 'SELECT' ? e.target : this);
            const clientId = $target.val();

            var $shiftGroup = $target.closest('.shift-group');
            if (!$shiftGroup.length) $shiftGroup = $('#edit_shift .shift-group').first();

            var $siteSelect = $shiftGroup.find('#siteSelect, .select2_site').first();
            if (!$siteSelect.length) $siteSelect = $('#edit_shift #siteSelect');

            // Reset options
            $siteSelect.html('<option value="">--choose--</option>');

            if (!clientId) {
                try {
                    $shiftGroup.find('.siteRate').val('');
                } catch (err) {}
                try {
                    if ($siteSelect.hasClass('select2-hidden-accessible')) {
                        $siteSelect.trigger('change.select2');
                    } else {
                        $siteSelect.trigger('change');
                    }
                } catch (err) {}
                return;
            }

            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    try {
                        $shiftGroup.find('.siteRate').val(data.client.office_rate || '');
                    } catch (err) {}
                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function(index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site.site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }

                    try {
                        if ($siteSelect.hasClass('select2-hidden-accessible')) {
                            $siteSelect.trigger('change.select2');
                        } else {
                            $siteSelect.trigger('change');
                        }
                    } catch (err) {}
                },
                error: function(xhr, status, error) {
                    console.error('Fetch sites error:', error);
                }
            });
        });

        // Update staff guard rate when staff selection changes
        $('#edit_shift').on("change select2:select", ".StaffSelect", function(e) {
            var $target = $(e.target && e.target.nodeName === 'SELECT' ? e.target : this);
            const staffId = $target.val();

            var $shiftGroup = $target.closest('.shift-group');
            if (!$shiftGroup.length) $shiftGroup = $('#edit_shift .shift-group').first();

            if (!staffId) {
                $shiftGroup.find('.staffRate').val('');
                return;
            }

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.staff) {
                        const guardRate = data.staff.guard_rate || data.staff.employee?.guard_rate || '';
                        $shiftGroup.find('.staffRate').val(guardRate);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch staff error:', error);
                }
            });
        });

        // Day selector functionality
        function initDaySelector(shiftGroup) {
            const dayBoxes = shiftGroup.querySelectorAll('.day-box');
            const hiddenInput = shiftGroup.querySelector('input[name="days"]');

            dayBoxes.forEach(box => {
                box.addEventListener('click', () => {
                    box.classList.toggle('selected');
                    const selected = Array.from(shiftGroup.querySelectorAll('.day-box.selected'))
                        .map(el => el.getAttribute('data-day'));
                    hiddenInput.value = selected.join(',');
                });
            });
        }

        // Initialize day selector for edit modal
        document.querySelectorAll('#edit_shift .shift-group').forEach(group => initDaySelector(group));
    });
    
    // Toggle visibility of the "Require Media Upload" option per shift-group
    $(document).ready(function() {
        function updateForGroup($group) {
            try {
                var $auto = $group.find('.autoCheckcallToggle').first();
                var $wrapper = $group.find('.requireMediaToggleWrapper').first();
                var $require = $group.find('.requireMediaToggle').first();
                var $from = $group.find('input[name="from_shift"]').first();
                var $to = $group.find('input[name="to_shift"]').first();
                if ($auto.length && $wrapper.length) {
                    if ($auto.is(':checked')) {
                        $wrapper.show();
                    } else {
                        $wrapper.hide();
                    }
                }

                try {
                    var hasDate = ($from.length && $from.val()) || ($to.length && $to.val());
                    if (hasDate) {
                        if ($auto.length && !$auto.is(':checked')) {
                            $auto.prop('checked', true);
                            if ($wrapper.length) $wrapper.show();
                        }
                        if ($require.length && !$require.is(':checked')) {
                            $require.prop('checked', true);
                        }
                    }
                } catch (e) {
                    console && console.error && console.error('Error checking shift date for group', e);
                }
            } catch (e) {
                console && console.error && console.error('Error updating RequireMedia visibility for group', e);
            }
        }

        $('#edit_shift .shift-group').each(function() {
            updateForGroup($(this));
        });

        $('#edit_shift').on('change', '.autoCheckcallToggle', function() {
            var $group = $(this).closest('.shift-group');
            updateForGroup($group);
        });

        $('#edit_shift').on('initShiftGroup', '.shift-group', function() {
            updateForGroup($(this));
        });
    });
    
    // Check call functionality for edit shift modal
    $(document).ready(function() {
        let checkIndex = 0;

        function addCheckCallRow($parentRow) {
            checkIndex++;
            const row = `
                <div class="row checkcall-row mb-3 align-items-center" data-index="${checkIndex}">
                    <div class="col-md-3">
                        <label>Check Call Name</label>
                        <input type="text" name="checkcalls[${checkIndex}][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Scheduled Time</label>
                        <input type="time" name="checkcalls[${checkIndex}][scheduled_time]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removeCheckCallRow">Remove</button>
                    </div>
                </div>
            `;
            $parentRow.append(row);
        }

        $('#edit_shift').on('click', '.addCheckCallRow', function() {
            console.log("Add Check Call clicked in edit modal ✅");
            var $parentRow = $(this).closest('.checkcall-section').find('.checkcall-rows');
            addCheckCallRow($parentRow);
        });

        $('#edit_shift').on('click', '.removeCheckCallRow', function() {
            $(this).closest('.checkcall-row').remove();
        });
    });
    
</script>
