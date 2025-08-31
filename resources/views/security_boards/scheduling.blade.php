@extends('layouts.app')
@section('title', 'CRM - Scheduling')
@section('styles')
    <!-- Flatpickr CSS -->
    <style>
        .gantt-container {
            overflow-x: auto;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .gantt-header {
            display: flex;
            min-width: 100%;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .gantt-sidebar-header {
            width: 115px;
            min-width: 100px;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            background-color: #e9ecef;
            border-right: 1px solid #dee2e6;
            text-align: center;
        }

        .gantt-timeline-header {
            display: flex;
            flex: 1;
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
            width: 115px;
            min-height: 100px;
            min-width: 100px;
            padding: 12px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            display: flex;
            font-style: bold;
            flex-direction: column;
            justify-content: center;
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

        .day-header {
            text-align: center;
            padding: 8px 5px;
            width: 100px;
            font-size: 12px;
            font-weight: bold;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .day-cell {
            height: 100%;
            position: relative;
        }

        .gantt-bar {
            position: absolute;
            border-radius: 4px;
            padding: 5px;
            color: white;
            font-size: 11px;
            overflow: hidden;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
            top: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .gantt-bar:hover {
            opacity: 0.9;
            transform: scale(1.02);
            z-index: 20;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
            display: inline-block;
            grid-template-columns: repeat(7, 1fr);
            /* 7 days */
        }

        .day-header,
        .day-column {
            min-width: 145px;
            text-align: center;
            box-sizing: border-box;
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
                    <h2 class="mb-1">Scheduling - Gantt View</h2>
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
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="gantt-container">
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
            @include('security_boards.edit');
        </div>

        <!-- Add Shift Success -->
        <div class="modal fade" id="success_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center p-3">
                            <span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
                                    class="ti ti-check fs-24"></i></span>
                            <h5 class="mb-2" id="success_message"></h5>
                            <div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="{{ url('scheduling') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>
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
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveshift'); // Add an ID to your submit button

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        $('#success_message').html('Shift Added Successfully')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        console.log("Status:", xhr.status);
                        console.log("Response:", xhr.responseText); // Helpful for debugging

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            let errors = xhr.responseJSON.errors;
                            let responseIndex = xhr.responseJSON.index;
                            $.each(errors, function(key, value) {
                                if ($('#error_' + key).length)
                                    $('#error_' + key).text(value[0]);

                                if ($('.error_' + key).length)
                                    $('.error_' + key).eq(responseIndex).text(value[0]);
                            });
                        } else if (xhr.responseJSON?.error) {
                            toast_danger(xhr.responseJSON.error); //
                        } else {
                            toast_danger('An unexpected error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let allShiftsData = []; // Store all shifts data
            let currentWeekStart = getMonday(new Date()); // Start with current week (Monday)
            let currentWeekEnd = new Date(currentWeekStart);
            currentWeekEnd.setDate(currentWeekEnd.getDate() + 6); // Sunday of current week

            // Display current week
            updateWeekDisplay();

            // Navigation buttons
            $('#todayBtn').on('click', function() {
                currentWeekStart = getMonday(new Date());
                currentWeekEnd = new Date(currentWeekStart);
                currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);
                updateWeekDisplay();
                renderCurrentWeek();
            });

            $('#prevWeekBtn').on('click', function() {
                currentWeekStart.setDate(currentWeekStart.getDate() - 7);
                currentWeekEnd.setDate(currentWeekEnd.getDate() - 7);
                updateWeekDisplay();
                renderCurrentWeek();
            });

            $('#nextWeekBtn').on('click', function() {
                currentWeekStart.setDate(currentWeekStart.getDate() + 7);
                currentWeekEnd.setDate(currentWeekEnd.getDate() + 7);
                updateWeekDisplay();
                renderCurrentWeek();
            });

            // Load all shifts data
            loadAllShiftsData();

            // Handle search
            $('#ganttSearchBtn').on('click', function() {
                filterGanttChart($('#ganttSearch').val());
            });

            $('#ganttSearch').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    filterGanttChart($(this).val());
                }
            });

            function loadAllShiftsData(currentFilters = {}) {
                // Show loading state
                $('#ganttChart').html(
                    '<div class="text-center p-5"><div class="spinner-border" role="status"></div><p class="mt-2">Loading shifts...</p></div>'
                );

                // Fetch all shifts data from API
                $.ajax({
                    url: `${baseUrl}/api/shifts`,
                    method: 'GET',
                    data: {
                        ...currentFilters
                        // No date filters - get all shifts
                    },
                    success: function(response) {
                        allShiftsData = response.data;
                        renderCurrentWeek();
                    },
                    error: function(xhr) {
                        $('#ganttChart').html(
                            '<div class="gantt-empty">Error loading data. Please try again.</div>');
                        console.error('Error loading Gantt data:', xhr);
                    }
                });
            }

            function renderCurrentWeek() {
                if (!allShiftsData || allShiftsData.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found.</div>');
                    return;
                }

                // Filter shifts for the current week
                const weekShifts = allShiftsData.filter(shift => {
                    const shiftDate = new Date(shift.start_date);
                    return shiftDate >= currentWeekStart && shiftDate <= currentWeekEnd;
                });

                if (weekShifts.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found for this week.</div>');
                    return;
                }

                renderGanttChart(weekShifts, currentWeekStart, currentWeekEnd);
            }

            function renderGanttChart(data, startDate, endDate) {
                // Group shifts by site
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

                // Generate header
                let headerHtml = `
                    <div class="gantt-header">
                    <div class="gantt-sidebar-header">Client Name</div>
                        <div class="gantt-sidebar-header">Site Name</div>
                        <div class="gantt-timeline-header">
                `;

                // Generate day columns for each day in the week
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

                    // Move to next day
                    currentDate.setDate(currentDate.getDate() + 1);
                }

                headerHtml += `</div></div>`;

                // Generate body with sites and shifts
                let bodyHtml = `<div class="gantt-body">`;

                Object.values(sites).forEach(site => {
                    bodyHtml += `
                        <div class="gantt-row" data-site-id="${site.id}">
                        <div class="gantt-row-sidebar">
                                <strong>${site.client_name}</strong>
                            </div>
                            <div class="gantt-row-sidebar">
                                <strong>${site.name}</strong>
                                <small class="text-muted">${site.shifts.length} shift(s)</small>
                            </div>
                            <div class="gantt-row-content">
                    `;

                    // Generate day columns for this site row
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

                // Combine and render
                $('#ganttChart').html(headerHtml + bodyHtml);

                // Now place the shifts in the correct day cells
                Object.values(sites).forEach(site => {
                    site.shifts.forEach(shift => {
                        const shiftDate = new Date(shift.start_date);
                        const dateStr = formatDate(shiftDate);
                        const cell = $(`#cell-${site.id}-${dateStr}`);

                        if (cell.length) {
                            // Calculate position based on time
                            const startTime = new Date(`${shift.start_date}T${shift.start_time}`);
                            const endTime = new Date(`${shift.end_date}T${shift.end_time}`);

                            // Calculate position as percentage of day
                            const dayStart = new Date(shiftDate);
                            dayStart.setHours(0, 0, 0, 0);

                            const dayEnd = new Date(shiftDate);
                            dayEnd.setHours(23, 59, 59, 999);

                            const dayDuration = dayEnd - dayStart;
                            const shiftStartOffset = startTime - dayStart;
                            const shiftDuration = endTime - startTime;

                            const left = 1;
                            const width = 100;

                            // Create shift bar
                            const bar = $(`
                                <div class="gantt-bar shift-${shift.color_class}" 
                                    style="left: ${left}%; width: ${width}%;"
                                    data-shift-id="${shift.id}"
                                    title="${shift.title} (${shift.formatted_time}) - ${shift.staff_name}">
                                    ${shift.title}<br>${shift.formatted_time}<br>${shift.duration}<br>${shift.staff_name}
                                </div>
                            `);

                            cell.append(bar);

                            // Add click handler to bar
                            bar.on('click', function() {
                                const shiftId = $(this).data('shift-id');
                                window.open(`${baseUrl}/shift-dates/${shiftId}/view`,
                                    '_blank');
                            });
                        }
                    });
                });
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

                    if (siteText.includes(term) || shiftText.includes(term)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            function formatDate(date) {
                return date.toISOString().split('T')[0];
            }

            function getMonday(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is Sunday
                return new Date(d.setDate(diff));
            }

            function updateWeekDisplay() {
                const options = {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                };
                const startStr = currentWeekStart.toLocaleDateString('en-US', options);
                const endStr = currentWeekEnd.toLocaleDateString('en-US', options);
                $('#currentWeekDisplay').text(`${startStr} - ${endStr}`);
            }

            // UPDATED FILTER HANDLER

            document.getElementById('shiftFilterForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;
                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value) {
                        params.append(key, value);
                    }
                }

                const filteredUrl = `${baseUrl}/api/shifts?${params.toString()}`;

                calendar.removeAllEvents();
                calendar.setOption('events', filteredUrl);
                calendar.refetchEvents();

                const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
                modal.hide(); // Hide modal after applying filters
            });

        });
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
    </script>

@endsection