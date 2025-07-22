<!-- Filter Modal -->
<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="shiftFilterForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Shifts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="guard">Guard</label>
                        <input type="text" name="guard" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="site">Site</label>
                        <input type="text" name="site" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="security">Security Status</label>
                        <select name="security" class="form-control">
                            <option value="">-- All --</option>
                            <option value="on_time">On-time</option>
                            <option value="late">Late</option>
                            <option value="missed">Missed</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    document.getElementById('applyFilters').addEventListener('click', function() {
        const guard = document.getElementById('filterGuard').value.trim();
        const site = document.getElementById('filterSite').value.trim();
        const security = document.getElementById('filterSecurity').value;

        const filters = {
            guard: guard || null,
            site: site || null,
            security: security || null,
        };

        console.log('Filters applied:', filters);

        // This is where you’d trigger your AJAX/filtering logic (next step if needed)

        document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('shiftFilterForm');

        form.addEventListener('submit', function (e) {
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
                .then(response => response.text())
                .then(html => {
                    document.getElementById('shiftTableContainer').innerHTML = html;
                    const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
                    modal.hide();
                })
                .catch(error => console.error('Filtering error:', error));
        });
    });
</script>
