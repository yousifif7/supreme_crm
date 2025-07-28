<!-- Filter Modal -->

@php
    $shifts = App\Models\Shift::with(['staff', 'site'])->get();
    // $staffIds = $shifts->pluck('staff_id')->unique();
    // $siteIds = $shifts->pluck('site_id')->unique();

    $staffs = App\Models\Employee::all();
    $sites = App\Models\Site::all();
@endphp

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="shiftFilterForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Shifts</h5>
                    <button type="button" class="add_btn btn btn-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Staff -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Staff</label>
                            <select class="form-select" name="staff">
                                <option value="">--choose--</option>
                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->fore_name }} {{$staff->sur_name  }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Site -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Site</label>
                            <select class="form-select" name="site">
                                <option value="">--choose--</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Start Time -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time">
                        </div>

                        <!-- End Time -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time">
                        </div>

                        <!-- Created At -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="from_shift">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="add_btn btn btn-white">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        dropdownParent: $('#filterModal'),
        placeholder: 'Select an option',
        allowClear: true
    });

    // Form Submit Handling
    const form = document.getElementById('shiftFilterForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch("{{ route('shifts.filter') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data.events)) {
                // Clear existing events from the FullCalendar instance
                calendar.getEvents().forEach(event => event.remove());

                // Add new events from the filtered result
                data.events.forEach(event => {
                    calendar.addEvent({
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        end: event.end,
                        className: event.className,
                        extendedProps: {
                            location: event.location,
                            urgent: event.urgent,
                            sd_id: event.sd_id
                        }
                    });
                });

                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
            } else {
                console.error("Invalid response format: ", data);
            }
        })
        .catch(error => console.error('Filtering error:', error));
    });
});

</script>
