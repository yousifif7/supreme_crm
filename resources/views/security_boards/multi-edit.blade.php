@php
    $staffs = App\Models\User::role('security_staff')->get();
@endphp

<div id="multiEditErrors" class="alert alert-danger d-none"></div>
<div class="modal fade" id="multiEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">Assign Staff to Selected Shifts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="multiEditForm">
                @csrf
                <div class="modal-body">
                    <p>Selected shifts: <span id="selectedShiftsCount">0</span></p>

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

                    <div class="mb-3">
                        <label for="multiAssignStartTime" class="form-label">Change Shift Time (Optional)</label>
                        <span>Start Time</span>
                        <input type="text" name="start_time" id="multiAssignStartTime" class="form-control mb-2"
                            placeholder="00:00">
                        <span>End Time</span>
                        <input type="text" name="end_time" id="multiAssignEndTime" class="form-control"
                            placeholder="00:00">
                    </div>

                    <!-- Hidden inputs for shift_ids -->
                    <div id="multiEditShiftInputs"></div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
            dropdownParent: $('#multiEditModal'),
            matcher: customMatcher,
            width: '100%'
        });
    });
</script>
