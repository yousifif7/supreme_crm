@extends('layouts.app')
@section('title', 'SPL Connect - Site Calendar')
@section('styles')
    <style>
        /* Sidebar prev/next buttons */
        .datepic .fc-prev-button,
        .datepic .fc-next-button {
            font-size: 14px !important;
            padding: 4px 6px !important;
            height: 28px !important;
            width: 34px !important;
            border-radius: 6px !important;
            background-color: #5489C4 !important;
            color: #fff !important;
            border: none !important;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
        }

        .datepic .fc-prev-button:hover,
        .datepic .fc-next-button:hover {
            opacity: 0.85 !important;
            transform: translateY(-1px);
        }

        /* FullCalendar view buttons */
        .fc-toolbar button {
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
            margin-right: 5px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            color: #fff !important;
            transition: background 0.2s, transform 0.1s;
        }

        /* Assign colors */
        .fc-dayGridDay-button {
            background-color: #80BFFF !important;
        }

        .fc-dayGridWeek-button {
            background-color: #5489C4 !important;
        }

        .fc-dayGridMonth-button {
            background-color: #69CF83 !important;
        }

        /* Active button highlight */
        .fc-toolbar button.fc-button-active {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-2px) !important;
        }

        /* Hover effect */
        .fc-toolbar button:hover {
            opacity: 0.85 !important;
        }

        /* Event/shift boxes */
        /* Container for each event (shift) */
        /* Ensure text wraps inside the box */
._schedule-box-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 13px;
    min-height: 50px;
    width: 95%;
    margin: 2px auto; /* spacing between events */
    color: #000;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: pointer;
    line-height: 1.3;
    word-break: break-word;
    white-space: normal;
    padding: 8px 10px;
}

        .fc .fc-daygrid-event-harness,
        .fc .fc-daygrid-event {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Optional: remove any hover effect applied by FullCalendar */
        .fc .fc-daygrid-event:hover {
            background: transparent !important;
            box-shadow: none !important;
        }

        ._schedule-box-container:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        /* Increase spacing in calendar header for clarity */
        .fc .fc-col-header-cell {
            padding: 6px 4px !important;
            font-size: 0.85rem;
        }

        /* Stack events neatly */
.fc-daygrid-day-events {
    display: flex !important;
    flex-direction: column;
    gap: 6px !important; /* stack events neatly */
}   

        /* Hide urgent red lines */
        .urgent-indicator {
            display: none !important;
        }

        .fc-daygrid-day-frame {
            padding: 6px 4px !important;
            min-height: 90px;
            /* taller day cells */
        }
    </style>
@endsection
@section('contents')<!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper site_calendar">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Scheduling</h2>

                </div>

            </div>


            @include('security_boards.shiftfilter')

            <div class="row" style="padding-right: 0px !important; padding-left: 0px !important;">

                <!-- Calendar Sidebar -->
                <div class="col-xxl-3 col-xl-3" style="padding-right: 0px !important; padding-left: 0px !important;">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="border-bottom pb-2 mb-4">
                                <div class="datepic"></div>
                            </div>

                            <!-- Event -->
                            @include('security_boards.event_colors')
                            <!-- /Event -->



                        </div>
                    </div>

                </div>
                <!-- /Calendar Sidebar -->

                <div class="col-xxl-9 col-xl-9 theiaStickySidebar">
                    <div class="card border-0">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Calendar View -->

            <!-- Add Rota -->

            <!-- Add shift -->
            @include('security_boards.shiftmodal');
            <!-- /Breadcrumb -->

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

                            </p>
                            <div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="{{ url('site_calendar') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assign Shift Modal -->
        @include('security_boards.assign-shift-modal')
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
                                const checkboxName = el.getAttribute('name') || '';
                                const defaultCheckedNames = [
                                    'restrict_start_time[]',
                                    'enforce_picture_check[]',
                                    'restrict_location_check[]',
                                    'auto_checkcall_enabled[]',
                                    'auto_patrol_enabled[]',
                                    'require_media_upload[]'
                                ];
                                el.checked = defaultCheckedNames.includes(checkboxName);
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

        function addCheckpointRow($parentRow, groupIndex = 0) {
            checkIndex++;

            const checkpointRow = `
                <div class="row checkpoint-row mb-3 align-items-center" data-index="${checkIndex}">
                    <div class="col-md-3"><label>Checkpoint Name</label>
                        <input type="text" name="checkpoints[${groupIndex}][${checkIndex}][checkpoint_name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Time</label>
                        <input type="time" name="checkpoints[${groupIndex}][${checkIndex}][checkpoint_time]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removeCheckpointRow">Remove</button>
                    </div>
                </div>
            `;

            $parentRow.append(checkpointRow);
            // $('#checkpoint-rows').append(checkpointRow);
        }

        $(document).on('click', '.addCheckpointRow', function() {
            var groupIndex = $(this).data('shift-group');
            var $parentRow = $(this).parents(`#checkpoint-section${groupIndex}`).find('.checkpoint-rows');
            addCheckpointRow($parentRow, groupIndex);
        });
        $(document).on('click', '.removeCheckpointRow', function() {
            $(this).closest('.checkpoint-row').remove();
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();

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
                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const colorMap = {
                'bg-dark-blue': '#5489C4',
                'bg-lighter': '#D6D4CE',
                'bg-dark-green': '#69CF83',
                'bg-light-yellow': '#FAD66B',
                'bg-light-blue': '#80BFFF',
                'bg-purple': '#9F87F5',
                'bg-red': '#F55B7C',
                'bg-primary11': '#FFFF5E',
                'bg-orange': '#F5B25F',
                'bg-secondary': '#6c757d'
            };

            fetch(`${baseUrl}/api/shifts-by-site`)
                .then(response => response.json())
                .then(data => {
                    const highlightDates = data.highlightDates;

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: new Date().toISOString().split('T')[0],
                        timeZone: 'local',
                        firstDay: 1,
                        eventDisplay: 'block',
                        displayEventEnd: true,

                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,dayGridWeek,dayGridDay'
                        },

                        events: data.events,

                        dayCellClassNames: function(arg) {
                            const dateStr = arg.date.toLocaleDateString('en-CA');
                            return highlightDates.includes(dateStr) ? ['highlight-day'] : [];
                        },

eventContent: function(info) {
    const event = info.event;
    const props = event.extendedProps;

    const startTime = props.startTime || '';
    const endTime = props.endTime || '';

    const bgClass = event.classNames?.[0] || 'bg-secondary';
    const bgColor = colorMap[bgClass] || colorMap['bg-secondary'];

    // Determine text color for readability
    let textColor = '#212529'; // default dark
    // Simple check for darker backgrounds (you can extend this)
    const darkBackgrounds = ['#3a87ad', '#5489C4', '#4e73df', '#2a4d69']; 
    if (darkBackgrounds.includes(bgColor)) {
        textColor = '#fff';
    }

    const container = document.createElement('div');
    container.className = '_schedule-box-container';
    container.style.backgroundColor = bgColor;
    container.style.color = textColor; // set readable font
    container.style.margin = '2px 0';
    container.style.padding = '8px 10px';

    container.innerHTML = `
        <div style="word-wrap: break-word; font-weight: 600;">${event.title}</div>
        <div style="font-size:0.85rem; color:${textColor};">
            ${startTime} - ${endTime} (${props.duration})
        </div>
    `;

    return {
        domNodes: [container]
    };
},
    
                        eventClick: function(info) {
                            // create a button with data-toggle="ajax-modal" in body and click it
                            const button = document.createElement('button');
                            const shiftId = info.event.extendedProps.sd_id;

                            if (!shiftId) {
                                console.error('Shift ID missing for this event:', info.event);
                                return;
                            }

                            window.open(`/shift-dates/${shiftId}/view`, '_blank');
                        },

                        eventDidMount: function(info) {
                            info.el.style.backgroundColor = 'transparent';
                            info.el.style.border = 'none';
                            info.el.style.overflow = 'visible';
                        }
                    });

                    calendar.render();

                    $('#calendarSearch').on('input', function() {
                        const searchText = $(this).val().toLowerCase();

                        calendar.batchRendering(() => {
                            calendar.getEvents().forEach(event => {
                                const matches = event.title.toLowerCase().includes(
                                        searchText) ||
                                    (event.extendedProps.location && event.extendedProps
                                        .location.toLowerCase().includes(searchText));

                                if (matches) {
                                    event.setProp('display', 'auto'); // show event
                                } else {
                                    event.setProp('display', 'none'); // hide event
                                }
                            });
                        });
                    });

                    // Sidebar Mini Calendar
                    const sidebarEl = document.querySelector('.datepic');
                    if (sidebarEl) {
                        const sidebarCal = document.createElement('div');
                        sidebarEl.appendChild(sidebarCal);

                        new FullCalendar.Calendar(sidebarCal, {
                            initialView: 'dayGridMonth',
                            firstDay: 1,
                            headerToolbar: {
                                left: 'prev',
                                center: 'title',
                                right: 'next'
                            },
                            selectable: true,
                            dateClick: function(info) {
                                calendar.gotoDate(info.dateStr);
                            },
                            height: 'auto',
                            initialDate: new Date().toISOString().split('T')[0],
                            timeZone: 'local'
                        }).render();
                    }
                });
        });
    </script>

    <script>
        document.querySelectorAll('.numeric-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');

                // Optional: Only allow one decimal point
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts[1];
                }
            });
        });
    </script>
    <script type="text/javascript">
        $(document).on("change", "#clientSelect", function() {
            var $this = $(this);
            const clientId = $(this).val();

            if (!clientId) return;

            var $siteSelect = $('#siteSelect');
            // Clear current options
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
