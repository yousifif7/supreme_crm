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
                                <option value="{{ $staff->id }}">{{ $staff->fore_name }}
                                    {{ $staff->sur_name }}
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
                    $('#success_message').html('Shift assigned successfully!');
                    $('#assignShiftBtn').remove();
                    closeBsModal('#assignShiftModal');
                    closeBsModal('#globalModal');
                    $('#success_modal').modal('show');
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON) {
                        // Show specific validation error
                        if (xhr.responseJSON.error) {
                            toast_danger(xhr.responseJSON.error);
                        } else if (xhr.responseJSON.errors) {
                            // Multiple field errors
                            let messages = Object.values(xhr.responseJSON.errors).flat().join('\n');
                            toast_danger(messages);
                        }
                    } else {
                        toast_danger('An unexpected error occurred while assigning the shift.');
                    }
                }
            });
        });
</script>
