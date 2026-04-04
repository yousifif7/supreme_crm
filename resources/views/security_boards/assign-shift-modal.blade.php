{{-- staffs are passed from the controller; no query needed here --}}

<div id="assignShiftErrors" class="alert alert-danger d-none"></div>
<div class="modal fade" id="assignShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">Assign Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignShiftForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="assign_shift_modal_shift_id" name="shift_id">
                    <div class="mb-3">
                        <label for="staff_id" class="form-label">Select Staff</label>
                        <select name="staff_id" id="staff_id" class="form-select selec2_assign_modal" required>
                            <option value="">-- Choose Staff --</option>
                            @php
                                $sortedStaffs = $staffs->sortBy(function ($s) {
                                    return strtolower(trim($s->first_name . ' ' . ($s->last_name ?? '')));
                                });
                            @endphp
                            @foreach ($sortedStaffs as $staff)
                                <option value="{{ $staff->id }}" data-first="{{ strtolower($staff->first_name) }}"
                                    data-last="{{ strtolower($staff->last_name) }}">
                                    {{ $staff->first_name }} {{ $staff->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assign_subcontractor_id" class="form-label">Select Subcontractor</label>
                        <select name="subcontractor_id" id="assign_subcontractor_id"
                            class="form-select SubcontractorSelectAssign">
                            <option value="">-- Choose Subcontractor --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Custom matcher for Select2
    function customMatcher(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }

        let term = params.term.toLowerCase();
        let first = $(data.element).data('first') || '';
        let last = $(data.element).data('last') || '';
        let full = (first + ' ' + last).trim();

        if (first.includes(term) || last.includes(term) || full.includes(term)) {
            return data;
        }

        return null;
    }

    // Initialize Select2 with matcher (guarded)
    $(document).ready(function() {
        if ($.fn && $.fn.select2) {
            try {
                $('.selec2_assign_modal').select2({
                    dropdownParent: $('#assignShiftModal'),
                    matcher: customMatcher,
                    width: '100%'
                });

                // init subcontractor select with Select2
                if (!$('#assign_subcontractor_id').hasClass('select2-hidden-accessible')) {
                    $('#assign_subcontractor_id').select2({
                        dropdownParent: $('#assignShiftModal'),
                        width: '100%',
                        minimumResultsForSearch: 0
                    });
                }
            } catch (e) {
                console.warn('Select2 init failed in assign-shift-modal:', e);
            }
        } else {
            console.warn('Select2 library not loaded; assign-shift modal will use native selects.');
        }
    });
</script>
<script>
    // When staff changes in assign modal, fetch subcontractors for that employee
    $(document).ready(function() {
        function populateAssignSubcontractors(staffId, preserveValue) {
            var $sub = $('#assign_subcontractor_id');
            $sub.prop('disabled', true);
            $sub.html('<option value="">Loading...</option>');

            if (!staffId) {
                $sub.html('<option value="">-- Choose Subcontractor --</option>').prop('disabled', false)
                    .trigger('change');
                return;
            }

            $.ajax({
                url: `${baseUrl}/subcontractors/for-employee/${staffId}`,
                method: 'GET',
                dataType: 'json'
            }).done(function(res) {
                $sub.empty().append('<option value="">-- Choose Subcontractor --</option>');
                if (res && res.data && res.data.length) {
                    res.data.forEach(function(s) {
                        var label = s.company_name || (s.first_name ? (s.first_name + ' ' + (s
                            .last_name || '')) : ('Subcontractor ' + s.id));
                        $sub.append(`<option value="${s.id}">${label}</option>`);
                    });
                }
                // restore preserved value if provided
                if (typeof preserveValue !== 'undefined' && preserveValue) {
                    try {
                        $sub.val(preserveValue);
                    } catch (e) {}
                }
                $sub.prop('disabled', false);
                if ($sub.hasClass('select2-hidden-accessible')) $sub.trigger('change.select2');
            }).fail(function() {
                $sub.empty().append('<option value="">-- Choose Subcontractor --</option>').prop(
                    'disabled', false);
            });
        }

        // wire change to helper
        $('#assignShiftModal').on('change', '#staff_id', function() {
            var staffId = $(this).val();
            populateAssignSubcontractors(staffId);
        });

        // when modal shown, pre-populate based on current staff value (preserve existing selection)
        $('#assignShiftModal').on('shown.bs.modal', function() {
            var staffId = $('#assignShiftModal #staff_id').val();
            var preserve = $('#assign_subcontractor_id').val();
            if (staffId) populateAssignSubcontractors(staffId, preserve);
        });
    });
</script>
<script>
    // $(document).off('submit', '#assignShiftForm').on('submit', '#assignShiftForm', function(e) {
    //     e.preventDefault();

    //     $.ajax({
    //         url: `${baseUrl}/assign-shift`,
    //         type: 'POST',
    //         data: $(this).serialize(),
    //         success: function(response) {
    //             showToast(response.success, 'success', 5000);
    //             location.reload();
    //         },
    //         error: function(xhr) {
    //             $('#assignShiftErrors').addClass('d-none').empty(); // clear old errors

    //             if (xhr.status === 422 && xhr.responseJSON?.errors) {
    //                 let messages = Object.values(xhr.responseJSON.errors).flat();
    //                 const restrictionMsg = messages[0]; // first error

    //                 if (window.isSuperAdmin) {
    //                     showRestrictionToast(restrictionMsg, () => {
    //                         // Clear errors before override
    //                         $('#assignShiftErrors').addClass('d-none').empty();

    //                         // Send override request
    //                         $.ajax({
    //                             url: `${baseUrl}/assign-shift-override`,
    //                             type: 'POST',
    //                             data: $('#assignShiftForm').serialize(),
    //                             success: function(res) {
    //                                 showToast(res.success, 'success', 5000);
    //                                 location.reload();
    //                             },
    //                             error: function(err) {
    //                                 showToast("Override failed. Try again.", "error", 5000);
    //                             }
    //                         });
    //                     });
    //                 } else {
    //                     showToast(restrictionMsg, 'error', 5000);
    //                 }

    //                 // Optional fallback in error div
    //                 messages.forEach(msg => $('#assignShiftErrors').append(`<div>${msg}</div>`));
    //                 $('#assignShiftErrors').removeClass('d-none');
    //             } else if (xhr.responseJSON?.error) {
    //                 showToast(xhr.responseJSON.error, 'error', 5000);
    //             } else {
    //                 showToast('An unexpected error occurred while assigning the shift.', 'error', 5000);
    //             }
    //         }
    //     });
    // });
</script>

<script></script>
