@extends('layouts.app')
@section('title', 'CRM - Scheduling')
@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    <!-- Flatpickr CSS -->
    <style>
        .gantt-timeline-header,
        .gantt-row-content {
            display: flex;
            flex: 1;
            min-width: 100%;
        }

        .gantt-timeline-header,
        .gantt-row-content {
            display: flex;
            flex: 1;
            width: 100%;
            /* take full width */
        }

        html {
            font-size: 80%;
        }

        .gantt-container {
            overflow-x: auto;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .btn-gantt-view.active {
            background-color: #0d6efd;
            /* Bootstrap primary color */
            color: white;
            border-color: #0d6efd;
        }

        .gantt-header {
            display: flex;
            min-width: 100%;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .gantt-sidebar-header {
            width: 140px;
            min-width: 100px;
            padding: 10px;
            font-size: 16px;
            /* slightly larger */
            font-weight: 800;
            /* heavier than bold */
            color: #212529;
            /* darker for contrast */
            background-color: #e9ecef;
            border-right: 1px solid #dee2e6;
            text-align: center;
        }

        .gantt-timeline-header {
            display: flex;
            flex: 1;
            font-size: 10px;
        }

        .gantt-body {
            display: flex;
            flex-direction: column;
            min-width: 100%;
        }

        .gantt-row {
            display: flex;
            min-height: 60px;
            border-bottom: 1px solid #dee2e6;
        }

        .gantt-row:hover {
            background-color: #f8f9fa;
        }

        .gantt-row-sidebar {
            width: 140px;
            min-height: 100px;
            min-width: 100px;
            padding: 12px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            display: flex;
            font-style: bold;
            flex-direction: column;
            justify-content: center;
            font-size: 13px;
        }

        .gantt-row-content {
            flex: 1;
            position: relative;
            display: flex;
        }

        .day-column {
            width: 100%;
            min-width: 100px;
            border-right: 1px solid #dee2e6;
            position: relative;
        }


        .day-header,
        .day-column {
            flex: 1;
            /* each day takes equal space */
            min-width: 0;
            /* allow shrink/stretch */
            width: auto;
            /* reset the fixed 100px */
            box-sizing: border-box;
        }


        .day-header {
            text-align: center;
            padding: 8px 5px;
            font-size: 11px;
            font-weight: 800;
            color: #343a40;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .day-cell {
            flex: 1;
            /* make the cell fill */
            min-height: 80px;
            position: relative;
            padding: 5px 10px;
        }

        .gantt-bar {
            background: #4e73df;
            color: #fff;
            padding: 2px;
            border-radius: 4px;
            margin-bottom: 5px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .gantt-bar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.25);
        }

        .gantt-empty {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .gantt-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 20px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .gantt-legend-item {
            display: flex;
            align-items: center;
            font-size: 11px;
        }

        .gantt-legend-color {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            border-radius: 3px;
        }

        .view-toggle {
            margin-bottom: 15px;
        }

        .gantt-controls {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        /* Color coding for shifts */
        .shift-bg-dark-blue {
            background-color: #5489C4;
        }

        .shift-bg-lighter {
            background-color: #D6D4CE;
            color: #333;
        }

        .shift-bg-dark-green {
            background-color: #69CF83;
        }

        .shift-bg-light-yellow {
            background-color: #FAD66B;
            color: #333;
        }

        .shift-bg-light-blue {
            background-color: #80BFFF;
        }

        .shift-bg-purple1 {
            background-color: #9F87F5;
        }

        .shift-bg-red {
            background-color: #F55B7C;
        }

        .shift-bg-primary11 {
            background-color: #FFFF5E;
            color: #333;
        }

        .shift-bg-orange {
            background-color: #F5B25F;
        }

        .shift-bg-secondary {
            background-color: #6c757d;
        }

        .time-marker {
            position: absolute;
            top: 0;
            height: 100%;
            width: 1px;
            background-color: #e9ecef;
            z-index: 1;
        }

        .time-label {
            position: absolute;
            top: -20px;
            font-size: 10px;
            color: #6c757d;
        }

        #ganttChart {
            margin: 0 auto;
            max-width: 1600px;
            /* keep it readable on huge monitors */
            width: 100%;
            /* stretch to fill wrapper */
        }

        .gantt-wrapper {
            width: 100%;
        }

        .day-header,
        .day-column {
            min-width: 145px;
            text-align: center;
            box-sizing: border-box;
        }

        #custom-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2147483647;
            /* Max possible z-index to be above everything */
        }

        .custom-toast {
            display: flex;
            align-items: center;
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 5px;
            min-width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .custom-toast .toast-icon {
            font-size: 20px;
            margin-right: 12px;
        }

        .custom-toast .toast-content {
            flex: 1;
        }

        .custom-toast .toast-content p {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #856404;
        }

        .custom-toast .override-btn {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: bold;
            background-color: #dc3545;
            /* red for admin action */
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .custom-toast .override-btn:hover {
            background-color: #c82333;
        }
    </style>
@endsection
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper security_board">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Scheduling</h2>
                </div>
            </div>

            @include('security_boards.shiftfilter')

            @include('security_boards.shifts.shift_filter_options')


            <div class="row">
                <!-- Sidebar -->

                <!-- /Sidebar -->

                <!-- Gantt Chart -->
                <div class="col-xxl-12 col-xl-12 theiaStickySidebar">
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="gantt-controls mb-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <input type="text" id="ganttSearch" class="form-control"
                                                placeholder="Search sites or shifts...">
                                            <button class="btn btn-outline-secondary" type="button" id="ganttSearchBtn">
                                                <i class="ti ti-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary" id="prevWeekBtn">
                                                <i class="ti ti-chevron-left"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" id="todayBtn">Today</button>
                                            <button class="btn btn-outline-secondary" id="nextWeekBtn">
                                                <i class="ti ti-chevron-right"></i>
                                            </button>

                                            <!-- New buttons for view modes -->
                                            <button class="btn btn-outline-secondary" id="viewDayBtn">Day</button>
                                            <button class="btn btn-outline-secondary" id="viewWeekBtn">Week</button>
                                            <button class="btn btn-outline-secondary" id="viewMonthBtn">Month</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="gantt-container gantt-wrapper d-flex justify-content-center">
                                <div id="ganttChart">
                                    <div class="text-center p-5">
                                        <div class="spinner-border" role="status"></div>
                                        <p class="mt-2">Loading shifts...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Gantt Chart -->

                <div class="col-xxl-12 col-xl-12">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class=" pb-2 mb-4">
                                        <h6 class="mb-3">Current Week</h6>
                                        <div id="currentWeekDisplay" class="fw-bold"></div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h6 class="mb-3">Shift Status</h6>
                                    <div class="gantt-legend">
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-dark-blue"></div>
                                            <span>Pending</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-lighter"></div>
                                            <span>Dispatched</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-dark-green"></div>
                                            <span>Accepted</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-light-yellow"></div>
                                            <span>Started</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-light-blue"></div>
                                            <span>Ended</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-purple"></div>
                                            <span>Rejected</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-red"></div>
                                            <span>Cancelled</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-dark-yellow"></div>
                                            <span>Pre-Start</span>
                                        </div>
                                        <div class="gantt-legend-item">
                                            <div class="gantt-legend-color shift-bg-orange"></div>
                                            <span>Await-Finish</span>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Event Colors Legend -->

                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Rota -->
            <!-- Add shift -->
            @include('security_boards.shiftmodal');
            @include('security_boards.multi-edit');

        </div>

        <!-- Add Shift Success -->
        <div class="modal fade" id="success_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center p-3">
                            <span class="avatar avatar-lg avatar-rounded bg-success mb-3">
                                <i class="ti ti-check fs-24"></i>
                            </span>
                            <h5 class="mb-2" id="success_message"></h5>
                            <p>Choose which to perform!</p>
                            <div class="row g-2">
                                <div class="col-12">
                                    <a href="{{ url('scheduling') }}" class="btn btn-dark w-100">Back to List</a>
                                </div>
                                <div class="col-12">
                                    <a id="latest_shift_link" href="#" class="btn btn-success w-100">Go to Latest
                                        Shift</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="noteForm">
                        @csrf
                        <input type="hidden" id="shiftId" name="shift_id" value="">

                        <div class="mb-3">
                            <label for="noteType" class="form-label">Note For</label>
                            <select class="form-select" id="noteType" name="note_type" required>
                                <option value="guard">Guard</option>
                                <option value="control">Control Room</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="noteText" class="form-label">Note</label>
                            <textarea class="form-control" id="noteText" name="note" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNoteBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shift Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Note For:</strong> <span id="viewNoteType"></span></p>
                    <p><strong>Note:</strong></p>
                    <p id="viewNoteText" class="border rounded p-2 bg-light"></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="deleteNoteBtn">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this note?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- /Page Wrapper -->
@endsection
@section('scripts')

    <script>
        window.isSuperAdmin = @json(auth()->check() && auth()->user()->getRoleNames()->contains('superadmin'));
    </script>

    <script>
        let container = document.getElementById('custom-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'custom-toast-container';
            // Append to body as the last child
            document.body.appendChild(container);
        }

        document.addEventListener('DOMContentLoaded', function() {
            function initDaySelector(shiftGroup) {
                const dayBoxes = shiftGroup.querySelectorAll('.day-box');
                const hiddenInput = shiftGroup.querySelector('input[name="days[]"]');

                dayBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        box.classList.toggle('selected');
                        const selected = Array.from(shiftGroup.querySelectorAll(
                                '.day-box.selected'))
                            .map(el => el.getAttribute('data-day'));

                        hiddenInput.value = selected.join(',');
                    });
                });
            }

            function bindEvents() {
                // Add Shift Button
                document.querySelectorAll('.addShiftGroup').forEach(btn => {
                    btn.onclick = function() {
                        const wrapper = document.querySelector('.shift-wrapper');
                        const lastGroup = wrapper.querySelector('.shift-group:last-of-type');
                        const clone = lastGroup.cloneNode(true);

                        // Reset values in clone
                        clone.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        });

                        // Reset day selection
                        clone.querySelectorAll('.day-box').forEach(box => box.classList.remove(
                            'selected'));
                        clone.querySelector('input[name="days[]"]').value = '';

                        // Update data-shift-group attribute
                        const allShiftGroups = wrapper.querySelectorAll('.shift-group');
                        const newShiftGroupIndex = allShiftGroups.length;
                        const checkpointBtn = clone.querySelector('.addCheckpointRow');
                        if (checkpointBtn) {
                            checkpointBtn.setAttribute('data-shift-group', newShiftGroupIndex);
                        }
                        const checkpointSection = clone.querySelector('.checkpoint-section');
                        if (checkpointSection) {
                            checkpointSection.setAttribute('id',
                                `checkpoint-section${newShiftGroupIndex}`);
                        }

                        // Clear checkpoint rows
                        const checkpointRows = clone.querySelector('.checkpoint-rows');
                        if (checkpointRows) {
                            checkpointRows.innerHTML = '';
                        }

                        wrapper.appendChild(clone);

                        // Re-init new shift group logic
                        initDaySelector(clone);
                        bindEvents();
                    };
                });

                // Remove Shift Button
                document.querySelectorAll('.remove-shift').forEach(btn => {
                    btn.onclick = function() {
                        const shiftGroups = document.querySelectorAll('.shift-wrapper .shift-group');
                        if (shiftGroups.length > 1) {
                            btn.closest('.shift-group').remove();
                        } else {
                            toast_danger('You must have at least one shift.');
                        }
                    };
                });
            }

            // Initialize for first shift-group
            document.querySelectorAll('.shift-group').forEach(group => initDaySelector(group));

            // Initial binding
            bindEvents();
        });

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

        $(document).ready(function() {
            $(document).on('click', '.addCheckCallRow', function() {
                console.log("Add Check Call clicked ✅");
                var $parentRow = $(this).closest('.checkcall-section').find('.checkcall-rows');
                addCheckCallRow($parentRow);
            });

            $(document).on('click', '.removeCheckCallRow', function() {
                $(this).closest('.checkcall-row').remove();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            let autoCheckcallEnabled = true; // default ON

            // Toggle listener
            $('#autoCheckcallToggle').change(function() {
                autoCheckcallEnabled = $(this).is(':checked');
                const message = autoCheckcallEnabled ? 'Auto checkcalls enabled' :
                    'Auto checkcalls disabled';
                toast_success(message);
            });

            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = this;
                let formData = new FormData(form);

                // Add the auto checkcall state to formData
                formData.append('auto_checkcall_enabled', autoCheckcallEnabled ? 1 : 0);

                let submitButton = $('#saveshift');
                submitButton.prop('disabled', true).html('Saving...');

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        showToast(response.message ?? 'Shift created successfully!', 'success',
                            5000);
                        location.reload();
                    },
                    error: function(xhr) {
                        console.log("Status:", xhr.status);
                        console.log("Response:", xhr.responseText);

                        // Check for override opportunity
                        if (xhr.status === 422 && xhr.responseJSON?.override_message) {
                            let overrideMessage = xhr.responseJSON.override_message;

                            // Show override toast with confirmation button
                            showRestrictionToast(overrideMessage, () => {
                                // Admin confirmed override here
                                formData.append('override', 1);

                                $.ajax({
                                    url: $(form).attr('action'),
                                    method: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    headers: {
                                        'X-CSRF-TOKEN': $(
                                            'input[name="_token"]').val()
                                    },
                                    success: function(resp) {
                                        closeBsModal('#add_shift');
                                        showToast(resp.message ??
                                            'Shift created successfully!',
                                            'success', 5000);
                                        location.reload();
                                    },
                                    error: function(err) {
                                        showToast(
                                            'Failed to override shift. Try again.',
                                            'error', 7000);
                                    },
                                    complete: function() {
                                        submitButton.prop('disabled', false)
                                            .html('Save');
                                    }
                                });
                            });


                        } else if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            let errors = xhr.responseJSON.errors;
                            let responseIndex = xhr.responseJSON.index ?? 0;
                            $.each(errors, function(key, value) {
                                if ($('#error_' + key).length)
                                    $('#error_' + key).text(value[0]);

                                if ($('.error_' + key).length)
                                    $('.error_' + key).eq(responseIndex).text(value[0]);
                            });
                        } else if (xhr.responseJSON?.error) {
                            showToast(xhr.responseJSON.error, 'error', 5000);
                        } else {
                            showToast('An unexpected error occurred. Please try again.',
                                'error', 5000);
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });

        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function showRestrictionToast(message, onOverride) {
            let container = document.getElementById('custom-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'custom-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'custom-toast';

            toast.innerHTML = `
        <div class="toast-icon">⚠</div>
        <div class="toast-content">
            <p>${message}</p>
            <div class="toast-actions">
                <button class="override-btn">Override Restriction</button>
            </div>
        </div>
    `;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 50);

            // Step 1: Override clicked
            toast.querySelector('.override-btn').addEventListener('click', function() {
                // Replace actions with confirmation buttons
                const actions = toast.querySelector('.toast-actions');
                actions.innerHTML = `
            <button class="confirm-btn">Yes, Override</button>
            <button class="cancel-btn">Cancel</button>
        `;

                // Step 2: Confirm override
                actions.querySelector('.confirm-btn').addEventListener('click', function() {
                    if (typeof onOverride === 'function') {
                        onOverride();
                    }
                    closeToast();
                });

                // Step 2: Cancel override
                actions.querySelector('.cancel-btn').addEventListener('click', function() {
                    closeToast();
                });
            });

            function closeToast() {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) container.removeChild(toast);
                }, 300);
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            let allShiftsData = []; // Store all shifts data
            let currentWeekStart = getMonday(new Date()); // Start with current week (Monday)
            let currentWeekEnd = new Date(currentWeekStart);
            currentWeekEnd.setDate(currentWeekEnd.getDate() + 6); // Sunday of current week

            let ganttView = 'week'; // default view
            setActiveGanttView('#viewWeekBtn'); // highlight week button
            // Initial render
            renderCurrentView();

            // Navigation buttons
            $('#todayBtn').on('click', function() {
                const today = new Date();
                if (ganttView === 'day') {
                    currentWeekStart = new Date(today);
                    currentWeekEnd = new Date(today);
                } else if (ganttView === 'week') {
                    currentWeekStart = getMonday(today);
                    currentWeekEnd = new Date(currentWeekStart);
                    currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);
                } else if (ganttView === 'month') {
                    currentWeekStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    currentWeekEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                }
                renderCurrentView();
            });

            $('#prevWeekBtn').on('click', function() {
                if (ganttView === 'day') {
                    currentWeekStart.setDate(currentWeekStart.getDate() - 1);
                    currentWeekEnd = new Date(currentWeekStart);
                } else if (ganttView === 'week') {
                    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
                    currentWeekEnd.setDate(currentWeekEnd.getDate() - 7);
                } else if (ganttView === 'month') {
                    currentWeekStart.setMonth(currentWeekStart.getMonth() - 1);
                    currentWeekEnd = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth() +
                        1, 0);
                }
                renderCurrentView();
            });

            $('#nextWeekBtn').on('click', function() {
                if (ganttView === 'day') {
                    currentWeekStart.setDate(currentWeekStart.getDate() + 1);
                    currentWeekEnd = new Date(currentWeekStart);
                } else if (ganttView === 'week') {
                    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
                    currentWeekEnd.setDate(currentWeekEnd.getDate() + 7);
                } else if (ganttView === 'month') {
                    currentWeekStart.setMonth(currentWeekStart.getMonth() + 1);
                    currentWeekEnd = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth() +
                        1, 0);
                }
                renderCurrentView();
            });

            // View mode buttons
            $('#viewDayBtn').on('click', function() {
                ganttView = 'day';
                currentWeekStart = new Date();
                currentWeekEnd = new Date(currentWeekStart);
                renderCurrentView();
                setActiveGanttView('#viewDayBtn');
            });

            $('#viewWeekBtn').on('click', function() {
                ganttView = 'week';
                currentWeekStart = getMonday(new Date());
                currentWeekEnd = new Date(currentWeekStart);
                currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);
                renderCurrentView();
                setActiveGanttView('#viewWeekBtn');
            });

            $('#viewMonthBtn').on('click', function() {
                ganttView = 'month';
                const today = new Date();
                currentWeekStart = new Date(today.getFullYear(), today.getMonth(), 1);
                currentWeekEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                renderCurrentView();
                setActiveGanttView('#viewMonthBtn');
            })
            // Search
            $('#ganttSearchBtn').on('click', function() {
                filterGanttChart($('#ganttSearch').val());
            });
            $('#ganttSearch').on('keyup', function(e) {
                if (e.key === 'Enter') filterGanttChart($(this).val());
            });
            // Load shifts
            function loadAllShiftsData(currentFilters = {}) {
                $('#ganttChart').html(
                    '<div class="text-center p-5"><div class="spinner-border" role="status"></div><p class="mt-2">Loading shifts...</p></div>'
                );

                $.ajax({
                    url: `${baseUrl}/api/shifts`,
                    method: 'GET',
                    data: {
                        ...currentFilters
                    },
                    success: function(response) {
                        allShiftsData = response.data;
                        renderCurrentView();
                    },
                    error: function(xhr) {
                        $('#ganttChart').html(
                            '<div class="gantt-empty">Error loading data. Please try again.</div>');
                        console.error('Error loading Gantt data:', xhr);
                    }
                });
            }

            // Render Gantt based on view
            function renderCurrentView(filteredData = null, filters = {}) {
                if (!allShiftsData || allShiftsData.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found.</div>');
                    return;
                }

                const shiftsToRender = filteredData || allShiftsData;

                if (shiftsToRender.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found for this selection.</div>');
                    return;
                }

                let startDate, endDate;

                // 👇 if filter form passed in with date range, respect it
                if (filters.from_shift || filters.to_shift) {
                    startDate = filters.from_shift ? new Date(filters.from_shift) : new Date(Math.min(...
                        shiftsToRender.map(s => new Date(s.start_date))));
                    endDate = filters.to_shift ? new Date(filters.to_shift) : new Date(Math.max(...shiftsToRender
                        .map(s => new Date(s.start_date))));
                } else {
                    // otherwise fall back to ganttView selection
                    if (ganttView === 'day') {
                        startDate = new Date(currentWeekStart);
                        endDate = new Date(currentWeekStart);
                    } else if (ganttView === 'week') {
                        startDate = new Date(currentWeekStart);
                        endDate = new Date(currentWeekStart);
                        endDate.setDate(endDate.getDate() + 6);
                    } else if (ganttView === 'month') {
                        startDate = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(), 1);
                        endDate = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth() + 1, 0);
                        currentWeekStart = new Date(startDate);
                        currentWeekEnd = new Date(endDate);
                    }
                }

                renderGanttChart(shiftsToRender, startDate, endDate);
                updateWeekDisplay();
            }

            function renderGanttChart(data, startDate, endDate) {
                const sites = {};
                data.forEach(shift => {
                    if (!sites[shift.site_id]) {
                        sites[shift.site_id] = {
                            id: shift.site_id,
                            name: shift.site_name,
                            client_name: shift.client_name,
                            shifts: []
                        };
                    }
                    sites[shift.site_id].shifts.push(shift);
                });

                // Header
                let headerHtml = `
            <div class="gantt-header">
                <div class="gantt-sidebar-header">Client Name</div>
                <div class="gantt-sidebar-header">Site Name</div>
                <div class="gantt-timeline-header">
        `;
                const currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    const dateStr = formatDate(currentDate);
                    const dayName = currentDate.toLocaleDateString('en-US', {
                        weekday: 'short'
                    });
                    const dayNum = currentDate.getDate();
                    const monthName = currentDate.toLocaleDateString('en-US', {
                        month: 'short'
                    });
                    headerHtml += `<div class="day-header">${dayName}<br>${monthName} ${dayNum}</div>`;
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                headerHtml += `</div></div>`;

                // Body
                let bodyHtml = `<div class="gantt-body">`;
                Object.values(sites).forEach(site => {
                    bodyHtml += `
                <div class="gantt-row" data-site-id="${site.id}">
                    <div class="gantt-row-sidebar"><strong>${site.client_name}</strong></div>
                    <div class="gantt-row-sidebar"><strong>${site.name}</strong> <small class="text-muted">${site.shifts.length} shift(s)</small></div>
                    <div class="gantt-row-content">
            `;
                    const dayDate = new Date(startDate);
                    while (dayDate <= endDate) {
                        const dateStr = formatDate(dayDate);
                        bodyHtml += `<div class="day-column" data-date="${dateStr}">
                                <div class="day-cell" id="cell-${site.id}-${dateStr}"></div>
                             </div>`;
                        dayDate.setDate(dayDate.getDate() + 1);
                    }
                    bodyHtml += `</div></div>`;
                });
                bodyHtml += `</div>`;

                $('#ganttChart').html(headerHtml + bodyHtml);

                // Place shifts
                // 1️⃣ Button click toggles all subcontractor names
                $('#toggle-subcontractors-all').on('click', function() {
                    const subs = $('.subcontractor-name'); // all subcontractor spans
                    subs.toggle();
                    $(this).text(subs.is(':visible') ? 'Hide Subcontractors' : 'Show Subcontractors');
                });

                // 2️⃣ Generate Gantt bars for each site and date
                Object.values(sites).forEach(site => {
                    const shiftsByDate = {};
                    site.shifts.forEach(shift => {
                        const dateStr = formatDate(new Date(shift.start_date));
                        if (!shiftsByDate[dateStr]) shiftsByDate[dateStr] = [];
                        shiftsByDate[dateStr].push(shift);
                    });

                    Object.entries(shiftsByDate).forEach(([dateStr, shifts]) => {
                        const cell = $(`#cell-${site.id}-${dateStr}`);
                        if (cell.length) {
                            shifts.forEach((shift, index) => {
                                // Extract subcontractor name from brackets
                                const subcontractorMatch = shift.staff_name.match(
                                    /\(([^)]+)\)/);
                                const subcontractor = subcontractorMatch ?
                                    subcontractorMatch[0] : '';
                                const staffNameWithoutSub = subcontractorMatch ?
                                    shift.staff_name.replace(subcontractor, '').trim() :
                                    shift.staff_name;

                                const bar = $(`
                        <div class="gantt-bar shift-${shift.color_class}" 
                             data-shift-id="${shift.id}"
                             style="position: relative; top: ${index*5}px; z-index:${100-index};" 
                             title="${shift.title} (${shift.formatted_time}) - ${shift.staff_name}">
                            ${shift.service_type ?? ''}<br>
                            ${shift.formatted_time}<small><small>${shift.duration}</small></small><br>
                            <span class="staff-name">${staffNameWithoutSub}</span>
                            ${subcontractor ? `<span class="subcontractor-name" style="display:none; font-weight:bold;">${subcontractor}</span>` : ''}

                            ${shift.note 
                                ? `<span class="view-note-icon" data-shift-id="${shift.id}" 
                                                                    style="position:absolute; top:2px; right:2px; cursor:pointer; font-size:14px; color:#0d6efd;">📝</span>` 
                                : `<span class="note-icon" data-shift-id="${shift.id}" 
                                                                    style="position:absolute; top:2px; right:2px; cursor:pointer; font-size:14px; color:#555;">📝</span>`}
                        </div>
                    `);

                                const checkbox = $(
                                    `<input type="checkbox" class="multi-shift-checkbox" data-id="${shift.id}" style="display:none; margin-right:5px;">`
                                );
                                cell.append(checkbox);
                                cell.append(bar);

                                // Clicking bar opens shift details
                                bar.on('click', function() {
                                    if (!selectionMode) {
                                        const shiftId = $(this).data('shift-id');
                                        window.open(
                                            `${baseUrl}/shift-dates/${shiftId}/view`,
                                            '_blank');
                                    }
                                });

                                // Clicking note icon opens modal (prevent bar click)
                                bar.find('.note-icon').on('click', function(e) {
                                    e.stopPropagation();
                                    const shiftId = $(this).data('shift-id');
                                    $('#shiftId').val(shiftId);
                                    $('#noteForm')[0].reset();
                                    $('#noteType').val('guard');
                                    $('#noteText').val('');
                                    $('#noteModal').modal('show');
                                });

                                // View note icon
                                bar.find('.view-note-icon').on('click', function(e) {
                                    e.stopPropagation();
                                    const shiftId = $(this).data('shift-id');
                                    $('#shiftId').val(shiftId);

                                    $.get(`/shift-dates/${shiftId}/note`, function(
                                        data) {
                                        if (data && data.note) {
                                            $('#viewNoteText').text(data
                                                .note);
                                            $('#viewNoteType').text(data
                                                .note_type);
                                            $('#deleteNoteBtn').data(
                                                'shift-id', shiftId);
                                            $('#viewNoteModal').modal(
                                                'show');
                                        }
                                    });
                                });
                            });
                        }
                    });
                });
                const todayStr = formatDate(new Date());
                const todayCell = document.querySelector(`[data-date='${todayStr}']`);

                if (todayCell) {
                    todayCell.scrollIntoView({
                        behavior: "smooth",
                        inline: "center", // keep today in the center
                        block: "nearest"
                    });
                }
                // const totalDays = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                // const minWidthPerDay = ganttView === 'day' ? 400 : ganttView === 'week' ? 150 : 60;
                // $('#ganttChart .gantt-timeline-header, #ganttChart .gantt-row-content')
                //     .css('min-width', `${totalDays * minWidthPerDay}px`);
            }

            function filterGanttChart(searchTerm) {
                if (!searchTerm) {
                    $('.gantt-row').show();
                    return;
                }
                const term = searchTerm.toLowerCase();
                $('.gantt-row').each(function() {
                    const siteText = $(this).find('.gantt-row-sidebar').text().toLowerCase();
                    const shiftText = $(this).find('.gantt-bar').text().toLowerCase();
                    if (siteText.includes(term) || shiftText.includes(term)) $(this).show();
                    else $(this).hide();
                });
            }

            function formatDate(date) {
                return date.toISOString().split('T')[0];
            }

            function getMonday(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(d.setDate(diff));
            }

            function updateWeekDisplay() {
                const options = {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                };
                let startStr = currentWeekStart.toLocaleDateString('en-US', options);
                let endStr = currentWeekEnd.toLocaleDateString('en-US', options);

                if (ganttView === 'day') $('#currentWeekDisplay').text(startStr);
                else if (ganttView === 'week') $('#currentWeekDisplay').text(`${startStr} - ${endStr}`);
                else if (ganttView === 'month') {
                    const monthName = currentWeekStart.toLocaleDateString('en-US', {
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#currentWeekDisplay').text(monthName);
                }
            }

            // Filter form
            document.getElementById('shiftFilterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const filters = {};
                for (const [key, value] of formData.entries())
                    if (value) filters[key] = value;

                const filteredShifts = allShiftsData.filter(shift => {
                    if (filters.staff && parseInt(shift.staff_id) !== parseInt(filters.staff))
                        return false;
                    if (filters.client_id && parseInt(shift.client_id) !== parseInt(filters
                            .client_id)) return false;
                    if (filters.site && parseInt(shift.site_id) !== parseInt(filters.site))
                        return false;
                    if (filters.status && parseInt(shift.status) !== parseInt(filters.status))
                        return false;

                    const shiftStart = new Date(shift.start_date);
                    if (filters.from_shift && shiftStart < new Date(filters.from_shift))
                        return false;
                    if (filters.to_shift && shiftStart > new Date(filters.to_shift)) return false;
                    return true;
                });

                renderCurrentView(filteredShifts, filters); // 👈 pass filters here
                bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
            });


            // Initial data load
            loadAllShiftsData();
        });

        let selectionMode = false; // global
        let selectedShiftIds = [];

        // Toggle selection mode
        $('#enableSelectBtn').on('click', function() {
            selectionMode = !selectionMode;
            $(this).text(selectionMode ? 'Cancel Select' : 'Multi Select');
            $('#editSelectedBtn').prop('hidden', !selectionMode);

            // Show/hide checkboxes
            $('.multi-shift-checkbox').each(function() {
                $(this).css('display', selectionMode ? 'inline-block' : 'none');
                if (!selectionMode) this.checked = false;
            });

            // Reset selected IDs
            if (!selectionMode) {
                selectedShiftIds = [];
                $('#selectedShiftsCount').text(0);
            }
        });

        // Track checkbox changes
        $(document).on('change', '.multi-shift-checkbox', function() {
            const shiftId = $(this).data('id');
            if (this.checked) selectedShiftIds.push(shiftId);
            else selectedShiftIds = selectedShiftIds.filter(id => id != shiftId);

            $('#selectedShiftsCount').text(selectedShiftIds.length);
        });

        // Open multi-edit modal
        $('#editSelectedBtn').on('click', function() {
            if (selectedShiftIds.length === 0) {
                alert('Please select at least one shift.');
                return;
            }

            $.ajax({
                url: `${baseUrl}/shifts/multi-edit`, // your multi-edit route
                method: 'POST',
                data: {
                    shift_ids: selectedShiftIds,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    let html = '';
                    data.shifts.forEach(shift => {
                        html += `
            <div class="mb-2">
                <strong>${shift.title}</strong><br>
                Start: <input type="time" class="edit-start-time" data-id="${shift.id}" value="${shift.start_time}">
                End: <input type="time" class="edit-end-time" data-id="${shift.id}" value="${shift.end_time}">
            </div>
        `;
                    });
                    $('#multiEditContent').html(html);
                    $('#multiEditModal').modal('show');
                },
                error: function() {
                    toast_danger('Failed to load selected shifts.');
                }
            });
        });

        // Save changes from multi-edit modal
        $(document).off('submit', '#multiEditForm').on('submit', '#multiEditForm', function(e) {
            e.preventDefault();

            if (!selectedShiftIds.length) {
                toast_danger('No shifts selected.');
                return;
            }

            // Clear previous hidden shift inputs
            $('#multiEditShiftInputs').empty();

            // Add hidden inputs for each selected shift
            selectedShiftIds.forEach(id => {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'shift_ids[]',
                    value: id
                }).appendTo('#multiEditShiftInputs');

                const startTime = $('#multiAssignStartTime').val();
                const endTime = $('#multiAssignEndTime').val();
                const bookOn = $('#multiAssignBookOn').val();
                const bookOff = $('#multiAssignBookOff').val();
                const shiftDate = $('.multiAssignDateInput').val();

                if (startTime) $('<input>').attr({
                    type: 'hidden',
                    name: `start_times[${id}]`,
                    value: startTime
                }).appendTo('#multiEditShiftInputs');
                if (endTime) $('<input>').attr({
                    type: 'hidden',
                    name: `end_times[${id}]`,
                    value: endTime
                }).appendTo('#multiEditShiftInputs');
                if (bookOn) $('<input>').attr({
                    type: 'hidden',
                    name: `book_on[${id}]`,
                    value: bookOn
                }).appendTo('#multiEditShiftInputs');
                if (bookOff) $('<input>').attr({
                    type: 'hidden',
                    name: `book_off[${id}]`,
                    value: bookOff
                }).appendTo('#multiEditShiftInputs');
                if (shiftDate) $('<input>').attr({
                    type: 'hidden',
                    name: `shift_dates[${id}]`,
                    value: shiftDate
                }).appendTo('#multiEditShiftInputs');
            });

            const submitData = (override = false) => {
                $.ajax({
                    url: override ? `${baseUrl}/shifts/multi-assign-override` :
                        `${baseUrl}/shifts/multi-assign`,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.updated.length) {
                            showToast(override ? 'Shifts updated successfully (override)!' :
                                'Shifts updated successfully!', 'success', 5000);

                            $('#multiEditModal').modal('hide');
                            selectedShiftIds = [];
                            $('#selectedShiftsCount').text(0);
                            selectionMode = false;
                            $('#enableSelectBtn').text('Select');
                            $('#editSelectedBtn').prop('disabled', true);
                            $('#multiEditForm')[0].reset();
                            $('.selec2_assign_modal').val(null).trigger('change');
                            location.reload();
                        }

                        if (res.errors && Object.keys(res.errors).length) {
                            const messages = [];
                            for (const [shiftId, errs] of Object.entries(res.errors)) {
                                messages.push(
                                    `Shift ${shiftId}: ${Object.values(errs).flat().join(', ')}`
                                );
                            }
                            showToast(messages.join('<br>'), 'error', 5000);
                        }
                    },
                    error: function(xhr) {
                        // Collect error messages recursively
                        const collectMessages = (obj) => {
                            let msgs = [];
                            Object.values(obj).forEach(val => {
                                if (Array.isArray(val)) msgs.push(...val);
                                else if (typeof val === 'object') msgs.push(...
                                    collectMessages(val));
                            });
                            return msgs;
                        };

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const messages = collectMessages(xhr.responseJSON.errors);

                            if (messages.length) {
                                if (window.isSuperAdmin) {
                                    // Show restriction toast with override option
                                    showRestrictionToast(messages[0], () => {
                                        submitData(true); // retry with override
                                    });
                                } else {
                                    messages.forEach(msg => showToast(msg, 'error', 5000));
                                }
                            } else {
                                showToast('Validation failed, but no message returned.', 'error',
                                    5000);
                            }
                        } else if (xhr.responseJSON?.error) {
                            showToast(xhr.responseJSON.error, 'error', 5000);
                        } else {
                            showToast('An unexpected error occurred.', 'error', 5000);
                        }
                    }
                });
            };

            // Initial submit
            submitData();
        });


        function customMatcher(params, data) {
            if ($.trim(params.term) === '') return data;
            let term = params.term.toLowerCase();
            let first = $(data.element).data('first') || '';
            let last = $(data.element).data('last') || '';
            let full = (first + ' ' + last).trim();
            if (first.includes(term) || last.includes(term) || full.includes(term)) return data;
            return null;
        }
    </script>

    <script>
        document.querySelectorAll('.numeric-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts[1];
                }
            });
        });
    </script>

    </script>

    <script type="text/javascript">
        $(document).on("change", "#clientSelect", function() {
            var $this = $(this);
            const clientId = $(this).val();
            if (!clientId) return;

            var $siteSelect = $('#siteSelect');
            $siteSelect.html('<option value="">--choose--</option>');

            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $this.parents('.shift-group').find('.siteRate').val(data.client.office_rate || '');
                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function(index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site
                                .site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

        $(document).on("change", "#StaffSelect", function() {
            var $this = $(this);
            const staffId = $(this).val();
            if (!staffId) return;

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $this.parents('.shift-group').find('.staffRate').val(data.employee.guard_rate ||
                        '');
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

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

        function setActiveGanttView(buttonId) {
            // Remove active from all
            $('#viewDayBtn, #viewWeekBtn, #viewMonthBtn').removeClass('active');

            // Add active to the clicked one
            $(buttonId).addClass('active');
        }
        let currentShiftId = null;

        // Handle both add-note (📝) and view-note (👁️) icons
        // Only global delegated click handler
        $(document).on('click', '.note-icon, .view-note-icon', function(e) {
            e.stopPropagation(); // Prevent the bar click from firing

            const shiftId = $(this).data('shift-id');
            if (!shiftId) return;

            if ($(this).hasClass('note-icon')) {
                // Add note modal
                $('#shiftId').val(shiftId);
                $('#noteForm')[0].reset();
                $('#noteType').val('guard');
                $('#noteText').val('');
                $('#noteModal').modal('show');
            } else if ($(this).hasClass('view-note-icon')) {
                // View note modal
                $('#shiftId').val(shiftId);
                $.get(`/shift-dates/${shiftId}/note`, function(data) {
                    if (data && data.note) {
                        $('#viewNoteText').text(data.note);
                        $('#viewNoteType').text(data.note_type);

                        // store note ID on the Delete button
                        $('#deleteNoteBtn').data('note-id', data.id);

                        $('#viewNoteModal').modal('show');
                    }
                });
            }
        });

        // 2️⃣ Save or update note
        $(document).on('click', '#saveNoteBtn', function(e) {
            e.preventDefault();
            const shiftId = $('#shiftId').val();
            if (!shiftId) return;

            $.ajax({
                url: `/shift-dates/${shiftId}/note`,
                type: 'POST',
                data: $('#noteForm').serialize(), // form data includes note_type, note, shift_id, CSRF
                success: function(res) {
                    $('#noteModal').modal('hide');
                    showToast(
                        'Success on saving note!', // message
                        'success', // type
                        5000 // duration in ms
                    );
                    // Mark the icon as "has note"
                    $(`.note-icon[data-shift-id="${shiftId}"]`).css('color', '#0d6efd');

                    console.log("Saved note:", res);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    showToast(
                        xhr.responseText, // message
                        'error', // type
                        5000 // duration in ms
                    );
                }
            });
        });

        // Click delete button (from view modal)
        $(document).on('click', '#deleteNoteBtn', function() {
            const noteId = $(this).data('note-id');
            $('#confirmDeleteBtn').data('note-id', noteId);
            $('#viewNoteModal').modal('hide');
            $('#confirmDeleteModal').modal('show');
        });

        $(document).on('click', '#confirmDeleteBtn', function() {
            const noteId = $(this).data('note-id');
            // if (!noteId) return;

            $.ajax({
                url: `/shift-dates/${noteId}/note`, // route should accept note ID
                type: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    $('#confirmDeleteModal').modal('hide');
                    showToast(
                        'Note deleted!', // message
                        'success', // type
                        5000 // duration in ms
                    );
                    $(`.view-note-icon[data-note-id="${noteId}"]`)
                        .removeClass('view-note-icon')
                        .addClass('note-icon')
                        .html('📝')
                        .css('color', '#555');
                },
                error: function(xhr) {
                    $('#confirmDeleteModal').modal('hide');
                    showToast(
                        'Error deleting note!', // message
                        'error', // type
                        5000 // duration in ms
                    );
                    console.error(xhr.responseText);
                }
            });
        });
    </script>


@endsection
