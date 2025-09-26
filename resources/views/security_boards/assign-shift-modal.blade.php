@php
    $staffs = App\Models\User::role('security_staff')->get();
@endphp

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
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}" data-first="{{ strtolower($staff->first_name) }}"
                                    data-last="{{ strtolower($staff->last_name) }}">
                                    {{ $staff->first_name }} {{ $staff->last_name }}
                                </option>
                            @endforeach
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

    // Initialize Select2 with matcher
    $(document).ready(function() {
        $('.selec2_assign_modal').select2({
            dropdownParent: $('#assignShiftModal'),
            matcher: customMatcher,
            width: '100%'
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
