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
                                <option value="{{ $staff->id }}">{{ $staff->first_name }}
                                    {{ $staff->last_name }}
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
    $(document).off('submit', '#assignShiftForm').on('submit', '#assignShiftForm', function(e) {
        e.preventDefault();

        $.ajax({
            url: `${baseUrl}/assign-shift`,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                // On success, do nothing because normal redirect will handle it
                location.reload();
            },
            error: function(xhr) {
                $('#assignShiftErrors').addClass('d-none').empty();

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    let messages = Object.values(xhr.responseJSON.errors).flat();
                    messages.forEach(msg => $('#assignShiftErrors').append(`<div>${msg}</div>`));
                    $('#assignShiftErrors').removeClass('d-none');
                } else if (xhr.responseJSON?.error) {
                    $('#assignShiftErrors').html(xhr.responseJSON.error).removeClass('d-none');
                } else {
                    $('#assignShiftErrors').html(
                            'An unexpected error occurred while assigning the shift.')
                        .removeClass('d-none');
                }
            }
        });
    });
</script>

<script>

</script>
