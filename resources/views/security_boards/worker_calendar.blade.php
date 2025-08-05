@extends('layouts.app')
@section('title', 'CRM - Worker Calendar')

@section('styles')

@endsection
@section('contents')
    <!-- Page Wrapper -->
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
                                        <a href="{{ url('worker_calendar') }}" class="btn btn-dark w-100">Back to
                                            List</a>
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
                            checkpointSection.setAttribute('id', `checkpoint-section${newShiftGroupIndex}`);
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
    <!-- Inline Scripts after libraries load -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

              const colorMap = {
                'bg-dark-blue': '#5489C4',
                'bg-lighter': '#D6D4CE',
                'bg-dark-green': '#69CF83',
                'bg-light-yellow': '#FAD66B',
                'bg-light-blue': '#80BFFF',
                'bg-purple1': '#9F87F5',
                'bg-red': '#F55B7C',
                'bg-primary11': '#FFFF5E',
                'bg-orange': '#F5B25F',
                'bg-secondary': '#6c757d'
            };


            fetch(`${baseUrl}/api/shifts-with-staff`)
                .then(response => response.json())
                .then(data => {
                    const highlightDates = data.highlightDates;

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridWeek',
                        initialDate: new Date().toISOString().split('T')[0],
                        timeZone: 'local',
                        eventDisplay: 'block',
                        displayEventEnd: true,

                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,dayGridWeek,dayGridDay'
                        },

                        events: data.events,

                        // 🔸 Highlight days with shifts
                        dayCellClassNames: function(arg) {
                            const dateStr = arg.date.toLocaleDateString('en-CA'); // YYYY-MM-DD
                            return highlightDates.includes(dateStr) ? ['highlight-day'] : [];
                        },

                        // 🔸 Render events with images, icons, time, and urgency
                        eventContent: function(info) {
                            const event = info.event;
                            const props = event.extendedProps;
                                                        const bgClass = event.classNames?.[0] || 'bg-secondary';


                            const startTime = event.start?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';
                            const endTime = event.end?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';

                            const container = document.createElement('div');

                            // Compose class string
                           container.className = `_schedule-box-container ${bgClass}`;
                    container.style.marginBottom = '5px';

                    container.style.backgroundColor = colorMap[bgClass] || colorMap['bg-secondary'];

                            // Apply background color from colorMap based on className
                            const bgColor = colorMap[bgClass] || colorMap['bg-secondary'];


                            container.innerHTML = `
                        <div class="d-flex align-items-center " style="background: ${bgColor} !important;">
                            <div class="position-relative" >
                                ${props.urgent ? '<span class="urgent-indicator bg-danger"></span>' : ''}
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-size:10px"><strong style="color: black;">${event.title}</strong></div>
                                <div class="text-muted" style="font-size: 0.75rem; color: black !important;">${props.location || ''}</div>
                                <div class="text-muted" style="font-size: 0.75rem; color: black !important">${startTime} - ${endTime}</div>
                            </div>
                        </div>
                    `;

                            return {
                                domNodes: [container]
                            };
                        },

                        eventClick: function(info) {
                            // console.log('Event clicked:', info.event.extendedProps);
                            // create a button with data-toggle="ajax-modal" in body and click it
                            const button = document.createElement('button');
                            button.setAttribute('data-toggle', 'ajax-modal');
                            button.setAttribute('data-title', 'Rota Detail');
                            button.setAttribute('data-size', 'modal-xl');
                            button.setAttribute('data-width', '80%');
                            button.setAttribute('data-href', `shifts/${info.event.extendedProps.sd_id}`);
                            button.style.display = 'none';
                            document.body.appendChild(button);
                            button.click();
                        },

                        eventDidMount: function(info) {
                            info.el.style.overflow = 'visible';
                        }
                    });

                    calendar.render();

                    $('#calendarSearch').on('input', function() {
                        const searchText = $(this).val().toLowerCase();

                        calendar.batchRendering(() => {
                            calendar.getEvents().forEach(event => {
                                const matches = event.title.toLowerCase().includes(searchText) ||
                                                (event.extendedProps.location && event.extendedProps.location.toLowerCase().includes(searchText));

                                if (matches) {
                                    event.setProp('display', 'auto'); // show event
                                } else {
                                    event.setProp('display', 'none'); // hide event
                                }
                            });
                        });
                    });
                    
                    // 🔸 Sidebar mini calendar
                    const sidebarEl = document.querySelector('.datepic');
                    if (sidebarEl) {
                        const sidebarCal = document.createElement('div');
                        sidebarEl.appendChild(sidebarCal);

                        new FullCalendar.Calendar(sidebarCal, {
                            initialView: 'dayGridMonth',
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
        $(document).on("change","#clientSelect",function() {
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
                success: function (data) {
                    $this.parents('.shift-group').find('.siteRate').val(data.client.office_rate || '');

                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function (index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site.site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

        $(document).on("change","#StaffSelect",function() {
            var $this = $(this);
            const staffId = $(this).val();

            if (!staffId) return;

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    $this.parents('.shift-group').find('.staffRate').val(data.employee.guard_rate || '');
                },
                error: function (xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });
    </script>
@endsection
