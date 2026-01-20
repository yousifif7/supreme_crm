@extends('layouts.app')
@section('title', 'SPL Connect - Scheduling')
@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Flatpickr CSS -->
    <style>
        .gantt-timeline-header,
        .gantt-row-content {
            display: flex;
            flex: 1;
            width: 100%;
            min-width: 100%;
        }

        html {
            font-size: 80%;
        }

        .gantt-container {
            overflow-x: auto;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            width: 100%;
        }

        /* active toggle */
        .btn-gantt-view.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .gantt-header {
            display: flex;
            min-width: 100%;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .gantt-sidebar-header {
            width: 180px;
            min-width: 160px;
            padding: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #212529;
            background-color: #e9ecef;
            border-right: 1px solid #dee2e6;
            text-align: center;
        }

        .gantt-timeline-header {
            display: flex;
            flex: 1;
            font-size: 11px;
        }

        .gantt-body {
            display: flex;
            flex-direction: column;
            min-width: 100%;
        }

        /* increase row height so multi-line bars fit comfortably */
        .gantt-row {
            display: flex;
            min-height: 160px;
            border-bottom: 1px solid #dee2e6;
        }

        .gantt-row:hover {
            background-color: #f8f9fa;
        }

        .gantt-row-sidebar {
            width: 180px;
            min-width: 160px;
            padding: 14px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .gantt-row-content {
            flex: 1;
            position: relative;
            display: flex;
        }

        /* Day column: allow JS to set fixed widths per day so a full week
           can be forced to fit the available container width. Keep
           box-model and overflow handling here but remove a large CSS
           min-width that caused overly wide columns on large screens. */
        .day-column {
            flex: 1;
            min-width: 260px; /* JS will set the concrete width per day */
            border-right: 1px solid #dee2e6;
            position: relative;
            box-sizing: border-box;
            overflow: visible;
        }

        .day-header {
            flex: 1;
            text-align: center;
            padding: 8px 6px;
            font-size: 12px;
            font-weight: 800;
            color: #343a40;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            min-width: 120px;
        }

        /* day-cell grid: two columns on large screens; stack only on very tiny screens */
        .day-cell {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            min-height: 140px;
            position: relative;
            padding: 12px;
            align-items: start;
            box-sizing: border-box;
            overflow: visible;
        }

        /* Ensure bars fill their grid cell and can shrink if needed */
        .day-cell>.gantt-bar {
            min-width: 0;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Bar layout: stacked rows, no ellipsis; text wraps naturally and remains readable */
        .gantt-bar {
            background: #4e73df;
            color: #fff;
            padding: 14px 16px 14px 60px;
            /* leave left space for checkbox */
            border-radius: 10px;
            margin-bottom: 10px;
            width: auto;
            box-sizing: border-box;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
            position: relative;
            overflow: visible;
            transition: transform .12s ease, box-shadow .12s ease;
            display: block;
            white-space: normal;
        }

        /* .bar-content holds each piece on its own line */
        .gantt-bar .bar-content {
            display: block;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.18;
        }

        /* each content piece is block so it displays on separate lines */
        .gantt-bar .service-type,
        .gantt-bar .time-text,
        .gantt-bar .duration-text,
        .gantt-bar .staff-name {
            display: block;
            color: inherit;
            margin: 4px 0;
            font-size: 14px;
            font-weight: 600;
        }

        /* small duration text */
        .gantt-bar small {
            font-size: 12px;
            opacity: 0.95;
            color: inherit;
        }

        /* Hover */
        .gantt-bar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.16);
        }

        /* Checkbox inside bar - hidden unless .selection-mode on .gantt-container */
        .gantt-bar .multi-shift-checkbox {
            position: absolute;
            left: 16px;
            top: 16px;
            z-index: 8;
            width: 18px;
            height: 18px;
            cursor: pointer;
            background: #fff;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: none;
        }

        .selection-mode .gantt-bar .multi-shift-checkbox {
            display: block;
        }

        /* adjust padding when selection active */
        .selection-mode .gantt-bar {
            padding-left: 60px;
        }

        /* note icons */
        .gantt-bar .note-icon,
        .gantt-bar .view-note-icon {
            position: absolute;
            top: 14px;
            right: 14px;
            cursor: pointer;
            font-size: 16px;
            z-index: 9;
        }

        /* selected visual */
        .gantt-bar.selected {
            outline: 4px solid rgba(255, 193, 7, 0.95);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.20);
            transform: translateY(-2px);
        }

        /* Fix contrast for known light backgrounds: force dark text */
        .shift-bg-lighter,
        .shift-bg-primary11,
        .shift-bg-light-yellow,
        .shift-bg-light-blue,
        .shift-bg-orange {
            color: #111 !important;
        }

        .shift-bg-lighter .bar-content,
        .shift-bg-primary11 .bar-content,
        .shift-bg-light-yellow .bar-content,
        .shift-bg-light-blue .bar-content,
        .shift-bg-orange .bar-content {
            color: inherit;
        }

        .shift-bg-lighter small,
        .shift-bg-primary11 small,
        .shift-bg-light-yellow small,
        .shift-bg-light-blue small,
        .shift-bg-orange small {
            color: inherit;
            opacity: 0.95;
        }

        /* fallback when no shifts */
        .gantt-empty {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        /* legend/controls */
        .gantt-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
            padding: 6px;
            background: #f8f9fa;
            border-radius: 6px;
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
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .time-label {
            position: absolute;
            top: -20px;
            font-size: 11px;
            color: #6c757d;
        }

        #ganttChart {
            margin: 0 auto;
            max-width: 100%;
            width: 100%;
        }

        .gantt-wrapper {
            width: 100%;
        }

        /* Toasts - centered overlay */
        #custom-toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2147483647;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            pointer-events: none;
            /* allow clicks to pass through outside toasts */
            width: auto;
            max-width: none;
            padding: 0;
            box-sizing: border-box;
        }

        .custom-toast {
            display: flex;
            align-items: center;
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 12px 20px 12px 16px;
            margin-bottom: 10px;
            border-radius: 6px;
            min-width: 320px;
            max-width: 640px;
            width: auto;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
            opacity: 0;
            transform: translateY(20px);
            transition: all .28s cubic-bezier(.2, .8, .2, 1);
            font-family: Arial, sans-serif;
            pointer-events: auto;
            /* allow interaction with the toast */
            position: relative;
            overflow: visible;
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .custom-toast .close-btn {
            position: absolute;
            top: 6px;
            right: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            color: rgba(0, 0, 0, 0.7);
            padding: 2px 6px;
            z-index: 9999;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .custom-toast .toast-icon {
            font-size: 20px;
            margin-right: 12px;
        }

        .custom-toast .toast-content {
            flex: 1;
        }

        .custom-toast .override-btn {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: bold;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all .2s ease;
        }

        .custom-toast .override-btn:hover {
            background: #c82333;
        }

        /* Example shift classes kept (colors may be project-defined) */
        .shift-bg-dark-blue {
            background-color: #5489C4;
        }

        .shift-bg-lighter {
            background-color: #D6D4CE;
        }

        .shift-bg-dark-green {
            background-color: #69CF83;
            color: #fff;
        }

        .shift-bg-light-yellow {
            background-color: #FAD66B;
        }

        .shift-bg-light-blue {
            background-color: #80BFFF;
        }

        .shift-bg-purple1 {
            background-color: #9F87F5;
            color: #fff;
        }

        .shift-bg-red {
            background-color: #F55B7C;
            color: #fff;
        }

        .shift-bg-primary11 {
            background-color: #FFFF5E;
        }

        .shift-bg-orange {
            background-color: #F5B25F;
        }

        .shift-bg-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .time-marker {
            position: absolute;
            top: 0;
            height: 100%;
            width: 1px;
            background: #e9ecef;
            z-index: 1;
        }

        /* Responsive tweaks (still keep cells relatively wide; horizontal scroll allowed) */
        @media (max-width: 1400px) {
            .day-column {
                min-width: 240px;
            }

            // Create a safe stub so other code can call window.loadAllShiftsData() before
            // the real implementation is ready. Calls will be queued and executed once
            // the real function is assigned below.
            if ( !window.loadAllShiftsData || window.loadAllShiftsData.__isStub !==true) {
                window._pendingLoadAllShiftsCalls=window._pendingLoadAllShiftsCalls || [];

                window.loadAllShiftsData=function() {
                    window._pendingLoadAllShiftsCalls.push(arguments);
                }

                ;
                window.loadAllShiftsData.__isStub=true;
            }

            // Now overwrite with the real function and flush pending calls
            const _real_loadAllShiftsData=loadAllShiftsData;

            window.loadAllShiftsData=function() {
                return _real_loadAllShiftsData.apply(this, arguments);
            }

            ;
            window.loadAllShiftsData.__isStub=false;

            if (window._pendingLoadAllShiftsCalls && window._pendingLoadAllShiftsCalls.length) {
                window._pendingLoadAllShiftsCalls.forEach(function(args) {
                        try {
                            _real_loadAllShiftsData.apply(window, args);
                        }

                        catch (e) {
                            console.debug('flushed loadAllShiftsData call failed', e);
                        }
                    });
                window._pendingLoadAllShiftsCalls=[];
            }

            .gantt-row {
                min-height: 140px;
            }
        }

        @media (max-width: 992px) {
            .day-column {
                min-width: 220px;
            }

            .gantt-row {
                min-height: 140px;
            }

            .gantt-sidebar-header,
            .gantt-row-sidebar {
                min-width: 120px;
            }
        }

        @media (max-width: 768px) {
            .day-column {
                min-width: 200px;
            }

            .gantt-row {
                min-height: 140px;
            }

            .gantt-sidebar-header,
            .gantt-row-sidebar {
                min-width: 100px;
                font-size: 12px;
            }

            .gantt-bar {
                padding-left: 52px;
                font-size: 14px;
            }

            .gantt-bar .multi-shift-checkbox {
                left: 10px;
                top: 10px;
                width: 16px;
                height: 16px;
            }
        }

        /* On very narrow phones stack bars vertically but keep ample height so content is visible */
        @media (max-width: 520px) {
            .day-cell {
                grid-template-columns: 1fr;
                min-height: 160px;
            }

            .day-column {
                min-width: 180px;
            }

            .gantt-bar {
                padding-left: 40px;
            }
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
                    <h2 class="mb-1">Scheduling <i class="fa-solid note-icon fa-pencil "></i></h2>
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
        // Early stub so callers can safely call window.loadAllShiftsData() before the full scheduler script initializes.
        if (!window.loadAllShiftsData || window.loadAllShiftsData.__isStub !== false) {
            window._pendingLoadAllShiftsCalls = window._pendingLoadAllShiftsCalls || [];
            if (!window.loadAllShiftsData || window.loadAllShiftsData.__isStub !== true) {
                window.loadAllShiftsData = function() {
                    window._pendingLoadAllShiftsCalls.push(arguments);
                };
                window.loadAllShiftsData.__isStub = true;
            }
        }
    </script>
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
            // Persist current filters so background refreshes don't reset user's view
            window._ganttCurrentFilters = window._ganttCurrentFilters || {};

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
                var $parentRow = $(this).closest('.checkcall-section').find('.checkcall-rows');
                addCheckCallRow($parentRow);
            });

            $(document).on('click', '.removeCheckCallRow', function() {
                $(this).closest('.checkcall-row').remove();
            });
        });

        let patrolIndex = 0;

        function addPatrolRow($parentRow) {
            patrolIndex++;
            const row = `
                <div class="row patrol-row mb-3 align-items-center" data-index="${patrolIndex}">
                    <div class="col-md-3">
                        <label>Patrol Name</label>
                        <input type="text" name="patrols[${patrolIndex}][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Scheduled Time</label>
                        <input type="time" name="patrols[${patrolIndex}][start_time]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removePatrolRow">Remove</button>
                    </div>
                </div>
            `;
            $parentRow.append(row);
        }

        $(document).ready(function() {
            $(document).on('click', '.addPatrolRow', function() {
                var $parentRow = $(this).closest('.patrol-section').find('.patrol-rows');
                addPatrolRow($parentRow);
            });

            $(document).on('click', '.removePatrolRow', function() {
                $(this).closest('.patrol-row').remove();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Remove manual auto_checkcall_enabled append; rely on native form serialization
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = this;
                let formData = new FormData(form);

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
                                    // Use dedicated override endpoint so server runs override logic
                                    url: baseUrl + '/shifts/store-override',
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
                                        console.error('Override failed:', err);
                                        let msg = 'Failed to override shift. Try again.';
                                        try {
                                            if (err && err.responseJSON) {
                                                if (err.responseJSON.error) msg = err.responseJSON.error;
                                                else if (err.responseJSON.message) msg = err.responseJSON.message;
                                                else if (typeof err.responseJSON === 'string') msg = err.responseJSON;
                                            } else if (err && err.responseText) {
                                                try {
                                                    const parsed = JSON.parse(err.responseText);
                                                    if (parsed.error) msg = parsed.error;
                                                    else if (parsed.message) msg = parsed.message;
                                                } catch (e) {
                                                    msg = err.responseText;
                                                }
                                            }
                                        } catch (e) {
                                            // ignore parsing errors
                                        }
                                        showToast(msg, 'error', 7000);

                                        if (err && err.responseJSON && err.responseJSON.trace) {
                                            console.debug('Override trace:', err.responseJSON.trace);
                                        } else if (err && err.responseText) {
                                            console.debug('Override responseText:', err.responseText);
                                        }
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
        <button class="close-btn" aria-label="Close">&times;</button>
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

            // Auto-hide after 20s if not acted upon
            const autoHideMs = 20000;
            let autoHideTimer = setTimeout(() => closeToast(), autoHideMs);

            // Close button handler
            const closeBtn = toast.querySelector('.close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    clearTimeout(autoHideTimer);
                    closeToast();
                });
            }

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

            // Ensure clicks inside don't propagate to page
            toast.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            let allShiftsData = [];
            // keep a global reference so other helper functions (outside this closure)
            // can inspect the cached shifts. This is used by refreshShiftBar().
            window.allShiftsData = allShiftsData;
            let currentWeekStart = getMonday(new Date());
            let currentWeekEnd = new Date(currentWeekStart);
            currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);

            let ganttView = 'week';
            let initialLoad = true;
            let selectionMode = false;
            const selectedShiftIds = new Set();

            renderCurrentView();

            function debounce(fn, wait) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            }
            window.addEventListener('resize', debounce(() => {
                if (allShiftsData && allShiftsData.length) renderCurrentView();
            }, 150));

            // Small helper to return the desired left padding depending on selection mode
            function getBarLeftPadding() {
                return selectionMode ? '60px' : '16px';
            }

            $('#multiSelectBtn').on('click', function() {
                selectionMode = !selectionMode;
                $('.gantt-container').toggleClass('selection-mode', selectionMode);
                $(this).attr('aria-pressed', selectionMode ? 'true' : 'false');
                $(this).text(selectionMode ? 'Exit Multi-select' : 'Multi-select');

                // Update checkboxes to reflect selection set
                $('.multi-shift-checkbox').each(function() {
                    const id = String($(this).data('id'));
                    $(this).prop('checked', selectedShiftIds.has(id));
                });

                // Adjust padding immediately for all bars so content isn't pushed far to the right
                $('#ganttChart .gantt-bar').css('padding-left', getBarLeftPadding());

                if (selectionMode) {
                    setTimeout(() => {
                        const firstCb = document.querySelector('.multi-shift-checkbox');
                        if (firstCb) firstCb.focus();
                    }, 80);
                }
            });

            $('#todayBtn').on('click', function() {
                const today = new Date();
                if (ganttView === 'day') {
                    // use start/end of day so midnight-normalised shift dates fall inside range
                    currentWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    currentWeekEnd = new Date(currentWeekStart);
                    currentWeekEnd.setHours(23, 59, 59, 999);
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
                    // move one calendar day and normalise to start/end of that day
                    currentWeekStart.setDate(currentWeekStart.getDate() - 1);
                    currentWeekStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(), currentWeekStart.getDate());
                    currentWeekEnd = new Date(currentWeekStart);
                    currentWeekEnd.setHours(23, 59, 59, 999);
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
                    // move one calendar day and normalise to start/end of that day
                    currentWeekStart.setDate(currentWeekStart.getDate() + 1);
                    currentWeekStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(), currentWeekStart.getDate());
                    currentWeekEnd = new Date(currentWeekStart);
                    currentWeekEnd.setHours(23, 59, 59, 999);
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

            $('#viewDayBtn').on('click', function() {
                ganttView = 'day';
                const today = new Date();
                currentWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                currentWeekEnd = new Date(currentWeekStart);
                currentWeekEnd.setHours(23, 59, 59, 999);
                renderCurrentView();
                try {
                    setActiveGanttView('#viewDayBtn');
                } catch (e) {}
            });
            $('#viewWeekBtn').on('click', function() {
                ganttView = 'week';
                currentWeekStart = getMonday(new Date());
                currentWeekEnd = new Date(currentWeekStart);
                currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);
                renderCurrentView();
                try {
                    setActiveGanttView('#viewWeekBtn');
                } catch (e) {}
            });
            $('#viewMonthBtn').on('click', function() {
                ganttView = 'month';
                const today = new Date();
                currentWeekStart = new Date(today.getFullYear(), today.getMonth(), 1);
                currentWeekEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                renderCurrentView();
                try {
                    setActiveGanttView('#viewMonthBtn');
                } catch (e) {}
            });

            $('#ganttSearchBtn').on('click', function() {
                filterGanttChart($('#ganttSearch').val());
            });

            // Auto-search while typing with debounce to avoid excessive filtering/network calls
            try {
                $('#ganttSearch').on('input', debounce(function() {
                    filterGanttChart($(this).val());
                }, 350));
            } catch (err) {
                // Fallback for older browsers: still support Enter key
                $('#ganttSearch').on('keyup', function(e) {
                    if (e.key === 'Enter') filterGanttChart($(this).val());
                });
            }

            function loadAllShiftsData(currentFilters = null) {
                // Use persisted filters when caller doesn't provide any
                const filtersToUse = currentFilters !== null ? currentFilters : (window._ganttCurrentFilters || {});
                $('#ganttChart').html(
                    '<div class="text-center p-5"><div class="spinner-border" role="status"></div><p class="mt-2">Loading shifts...</p></div>'
                );
                $.ajax({
                    url: `${baseUrl}/api/shifts`,
                    method: 'GET',
                    data: {
                        ...filtersToUse
                    },
                    success: function(response) {
                        // normalize payload: some endpoints return { data: [...] } others return array directly
                        const payload = response.data || response.shift_dates || response || [];
                        // TEMP LOG: inspect incoming payload shape to debug client ordering
                        try {
                            console.debug('GANTT PAYLOAD SAMPLE', payload.slice(0, 10).map(p => ({
                                id: p.id,
                                client_id: p.client_id,
                                client_name: p.client_name,
                                start_datetime: p.start_datetime,
                                start_date: p.start_date,
                                start_time: p.start_time
                            })));
                        } catch (e) {
                            console.debug('GANTT payload debug failed', e);
                        }
                        allShiftsData = payload;
                        // keep global copy in sync
                        window.allShiftsData = allShiftsData;
                        renderCurrentView();
                    },
                    error: function(xhr) {
                        $('#ganttChart').html(
                            '<div class="gantt-empty">Error loading data. Please try again.</div>');
                        console.error('Error loading Gantt data:', xhr);
                    }
                });
            }

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
                if (filters.from_shift || filters.to_shift) {
                    startDate = filters.from_shift ? new Date(filters.from_shift) : new Date(Math.min(...
                        shiftsToRender.map(s => new Date(s.start_date))));
                    endDate = filters.to_shift ? new Date(filters.to_shift) : new Date(Math.max(...shiftsToRender
                        .map(s => new Date(s.start_date))));
                } else {
                    if (ganttView === 'day') {
                        // ensure full-day range so shifts with 00:00 timestamps are included
                        startDate = new Date(currentWeekStart);
                        startDate.setHours(0,0,0,0);
                        endDate = new Date(currentWeekStart);
                        endDate.setHours(23,59,59,999);
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
                 filterGanttChart($('#ganttSearch').val())
            }

        function renderGanttChart(data, startDate, endDate) {
    const sites = {};
    
            // Use ISO date strings (YYYY-MM-DD) for comparisons to avoid timezone pitfalls
            const startISO = formatDate(new Date(startDate));
            const endISO = formatDate(new Date(endDate));

            // Filter shifts that fall within the date range
            data.forEach(shift => {
                const shiftDateStr = formatDate(new Date(shift.start_date));

                // Only process shifts within the date range (string compare of ISO dates is safe)
                if (shiftDateStr >= startISO && shiftDateStr <= endISO) {
                    if (!sites[shift.site_id]) sites[shift.site_id] = {
                        id: shift.site_id,
                        name: shift.site_name,
                        client_name: shift.client_name,
                        shifts: []
                    };
                    sites[shift.site_id].shifts.push(shift);
                }
            });

    // If no sites have shifts in the date range, show empty chart
    if (Object.keys(sites).length === 0) {
        $('#ganttChart').html('<div class="alert alert-info text-center">No shifts found in the selected date range.</div>');
        return;
    }

    // After grouping shifts into sites, order by client so clients with the
    // nearest upcoming shifts appear first. Within each client, sites are
    // ordered by their nearest shift and shifts are ordered by start time.
    function parseShiftDateTime(shift) {
        // Prefer backend-provided full datetime if available
        if (shift.start_datetime) {
            let s = String(shift.start_datetime);
            // Backend uses m-d-YTH:i:s (e.g. 10-31-2025T14:00:00). Convert to YYYY-MM-DD for reliable parsing.
            const m = s.match(/^(\d{2})-(\d{2})-(\d{4})T(.*)$/);
            if (m) s = `${m[3]}-${m[1]}-${m[2]}T${m[4]}`;
            const parsed = Date.parse(s);
            if (!isNaN(parsed)) return parsed;
        }

        // Fallback: combine date + time fields
        const datePart = shift.start_date || shift.shift_date || shift.shiftDate || '';
        const timePart = shift.start_time || shift.startTime || shift.start || '00:00';
        if (!datePart) return Infinity;

        let d = String(datePart);
        // Normalize MM-DD-YYYY -> YYYY-MM-DD if necessary
        const m2 = d.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (m2) d = `${m2[3]}-${m2[1]}-${m2[2]}`;

        const dt = new Date(d + ' ' + timePart);
        const t = dt.getTime();
        return isNaN(t) ? Infinity : t;
    }

    // Sort shifts within each site by start datetime (keep this for cell placement)
    Object.values(sites).forEach(site => {
        site.shifts.sort((a, b) => parseShiftDateTime(a) - parseShiftDateTime(b));
    });

    // Group sites by client - but only sites that have shifts in the range
    const clients = {};
    Object.values(sites).forEach(site => {
        // Skip sites with no shifts (shouldn't happen due to earlier filter, but just in case)
        if (site.shifts.length === 0) return;
        
        // Determine a client key - prefer client_id from a shift (allow numeric 0), else fallback to client_name
        const clientIdField = (site.shifts && site.shifts.length) ? (typeof site.shifts[0]
                .client_id !== 'undefined' ? site.shifts[0].client_id : site.shifts[0].clientId
                ) : undefined;
        const clientKey = (typeof clientIdField !== 'undefined' && clientIdField !== null) ?
            String(clientIdField) : (site.client_name || 'unknown_client');
        if (!clients[clientKey]) clients[clientKey] = {
            id: clientKey,
            name: site.client_name || clientKey,
            sites: []
        };
        // attach a reference to client_name for safety
        site.client_name = site.client_name || clients[clientKey].name;
        clients[clientKey].sites.push(site);
    });

    // Compute newest created_at per site and per client, then sort clients by newest created_at (desc)
    function parseCreatedAt(shift) {
        const s = shift.created_at || shift.createdAt || shift.createdAtDate || null;
        if (!s) return -Infinity;
        const parsed = Date.parse(String(s));
        return isNaN(parsed) ? -Infinity : parsed;
    }

    Object.values(clients).forEach(client => {
        client.sites.forEach(site => {
            // compute site._latest as the maximum created_at across its shifts
            let latest = -Infinity;
            site.shifts.forEach(sh => {
                const t = parseCreatedAt(sh);
                if (t > latest) latest = t;
            });
            site._latest = latest;
        });
        // sort sites by newest created_at first
        client.sites.sort((a, b) => b._latest - a._latest);
        // client._latest is the newest time among its sites
        client._latest = client.sites.length ? client.sites[0]._latest : -Infinity;
    });

    // Order clients by their newest created_at (newest first)
    const orderedClients = Object.values(clients).sort((c1, c2) => c2._latest - c1._latest);

    // Flatten ordered sites in client order
    const orderedSites = [];
    orderedClients.forEach(client => client.sites.forEach(site => orderedSites.push(site)));

    // Remove any sites that ended up with no shifts in the current date range
    const filteredOrderedSites = orderedSites.filter(site => {
        return site.shifts && site.shifts.length && site.shifts.some(sh => {
            const d = formatDate(new Date(sh.start_date));
            return d >= startISO && d <= endISO;
        });
    });

    // Debug: log what sites will be rendered (id, name, shift count)
    try {
        console.debug('renderGanttChart: filteredOrderedSites', filteredOrderedSites.map(s => ({ id: s.id, name: s.name, shifts: s.shifts.length })));
    } catch (e) { /* ignore */ }

    // Header
    let headerHtml = `<div class="gantt-header">
        <div class="gantt-sidebar-header">Client Name</div>
        <div class="gantt-sidebar-header">Site Name</div>
        <div class="gantt-timeline-header">`;
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
        headerHtml +=
            `<div class="day-header" data-date="${dateStr}">${dayName}<br>${monthName} ${dayNum}</div>`;
        currentDate.setDate(currentDate.getDate() + 1);
    }
    headerHtml += `</div></div>`;

    // Body
    let bodyHtml = `<div class="gantt-body">`;
    // Use filteredOrderedSites so rows with no shifts are not rendered
    filteredOrderedSites.forEach(site => {
        bodyHtml += `<div class="gantt-row" data-site-id="${site.id}">
            <div class="gantt-row-sidebar"><strong>${site.client_name}</strong></div>
            <div class="gantt-row-sidebar"><strong>${site.name}</strong> <small class="text-muted">${site.shifts.length} shift(s)</small></div>
            <div class="gantt-row-content">`;
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

    $('.gantt-container').toggleClass('selection-mode', selectionMode);

    $('#toggle-subcontractors-all').off('click').on('click', function() {
        const subs = $('.subcontractor-name');
        subs.toggle();
        $(this).text(subs.is(':visible') ? 'Hide Subcontractors' : 'Show Subcontractors');
    });

    // Place shifts only for sites that were rendered (those with shifts in range)
    filteredOrderedSites.forEach(site => {
        const shiftsByDate = {};
        site.shifts.forEach(shift => {
            const dateStr = formatDate(new Date(shift.start_date));
            if (!shiftsByDate[dateStr]) shiftsByDate[dateStr] = [];
            shiftsByDate[dateStr].push(shift);
        });

        Object.entries(shiftsByDate).forEach(([dateStr, shifts]) => {
            const cell = $(`#cell-${site.id}-${dateStr}`);
            if (!cell.length) {
                // Debug: cell missing (row might not have been rendered for this site/date)
                try { console.debug('renderGanttChart: missing cell for', site.id, dateStr); } catch (e) {}
                return;
            }

            shifts.forEach((shift) => {
                // Extract all parenthesised subcontractor tags (e.g. "(SPL)")
                const subcontractorMatches = shift.staff_name ? shift.staff_name.match(/\([^)]*\)/g) : null;
                let subcontractor = '';
                if (subcontractorMatches && subcontractorMatches.length) {
                    // Preserve parentheses and join multiple tags with a space
                    subcontractor = subcontractorMatches.join(' ');
                }
                // Remove all parenthesised groups from the visible staff name
                const staffNameWithoutSub = shift.staff_name ? shift.staff_name.replace(/\s*\([^)]*\)/g, '').trim() : (shift.staff_name || '');

                // bar HTML: stacked rows (service, time, duration, staff)
                const bar = $(`
<div class="gantt-bar shift-${shift.color_class}" data-shift-id="${shift.id}"
     title="${escapeHtml(shift.title || '')} (${escapeHtml(shift.formatted_time || '')}) - ${escapeHtml(shift.staff_name || '')}">
    <input type="checkbox" class="multi-shift-checkbox" data-id="${shift.id}" aria-label="Select shift ${shift.id}">
    <div class="bar-content">
        ${shift.service_type ? `<div class="service-type">${escapeHtml(shift.service_type)}</div>` : ''}
        <div class="time-text">${escapeHtml(shift.formatted_time || '')}</div>
        <div class="staff-name">${escapeHtml(staffNameWithoutSub)}</div>
        ${subcontractor ? `<div class="subcontractor-name" style="display:none; font-weight:bold">${escapeHtml(subcontractor)}</div>` : ''}
    </div>
${shift.note 
    ? `<i class="fa-solid view-note-icon fa-file text-success" data-shift-id="${shift.id}" style="color:green"></i>` 
    : `<i class="fa-solid note-icon  " data-shift-id="${shift.id}" style="color:#ffffff">📝</i>`
}
                `);

                const idStr = String(shift.id);
                if (selectedShiftIds.has(idStr)) bar.addClass('selected');

                cell.append(bar);

                // Ensure bar fills the grid cell and can shrink if needed
                bar.css({
                    'min-width': 0,
                    'width': '100%',
                    'max-width': '100%',
                    'box-sizing': 'border-box',
                    // Override default left padding to remove excessive empty space.
                    // JS keeps this in sync with selectionMode.
                    'padding-left': getBarLeftPadding()
                });

                // checkbox initial state
                const cb = bar.find('.multi-shift-checkbox');
                cb.prop('checked', selectedShiftIds.has(idStr));

                // stop propagation so clicking checkbox doesn't trigger bar navigation
                cb.on('click', function(e) {
                    e.stopPropagation();
                });

                // checkbox change: update selected set and visual
                cb.on('change', function() {
                    const checked = !!$(this).prop('checked');
                    const idLocal = String($(this).data('id'));
                    const theBar = $(this).closest('.gantt-bar');
                    if (checked) {
                        selectedShiftIds.add(idLocal);
                        theBar.addClass('selected');
                    } else {
                        selectedShiftIds.delete(idLocal);
                        theBar.removeClass('selected');
                    }
                });

                // bar click behavior
                bar.on('click', function(e) {
                    const shiftIdLocal = $(this).data('shift-id');
                    if (selectionMode) {
                        const cbLocal = $(this).find(
                            '.multi-shift-checkbox');
                        const newState = !cbLocal.prop('checked');
                        cbLocal.prop('checked', newState).trigger(
                            'change');
                        e.stopPropagation();
                        return;
                    }
                    const target = e.target;
                    if (target && ($(target).closest(
                                '.multi-shift-checkbox').length || $(
                                target).closest('.note-icon').length ||
                            $(target).closest('.view-note-icon').length
                        )) return;
                    if (shiftIdLocal) window.open(
                        `${baseUrl}/shift-dates/${shiftIdLocal}/view`,
                        '_blank');
                });

                // notes: open modals, stop propagation so no navigation
                bar.find('.note-icon').on('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const shiftIdLocal = $(this).data('shift-id');
                    $('#shiftId').val(shiftIdLocal);
                    $('#noteForm')[0].reset();
                    $('#noteType').val('guard');
                    $('#noteText').val('');
                    $('#noteModal').modal('show');
                });

                bar.find('.view-note-icon').on('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const shiftIdLocal = $(this).data('shift-id');
                    $('#shiftId').val(shiftIdLocal);
                    $.get(`/shift-dates/${shiftIdLocal}/note`, function(
                        data) {
                        if (data && data.note) {
                            $('#viewNoteText').text(data.note);
                            $('#viewNoteType').text(data
                                .note_type);
                            // Store both shift-date id and note id to be safe
                            $('#deleteNoteBtn').data('shift-id',
                                shiftIdLocal);
                            if (data.id) $('#deleteNoteBtn')
                                .data('note-id', data.id);
                            $('#viewNoteModal').modal('show');
                        }
                    });
                });
            });
        });
    });

    // After placing bars: sizing assurance and synchronise padding with selection mode
    $('#ganttChart .day-cell > .gantt-bar').each(function() {
        $(this).css({
            'min-width': 0,
            'width': '100%',
            'max-width': '100%',
            'box-sizing': 'border-box',
            'padding-left': getBarLeftPadding()
        });
    });

    // Responsive sizing: bigger baseline so content remains visible
    (function adjustGanttSizing() {
        const ganttChartEl = document.getElementById('ganttChart');
        if (!ganttChartEl) return;

        const dayHeaders = ganttChartEl.querySelectorAll('.day-header');
        const totalDays = dayHeaders.length || 1;

        const sidebarHeaders = ganttChartEl.querySelectorAll('.gantt-sidebar-header');
        let sidebarTotalWidth = 0;
        if (sidebarHeaders && sidebarHeaders.length > 0) {
            sidebarHeaders.forEach(h => {
                sidebarTotalWidth += h.getBoundingClientRect().width;
            });
        } else {
            sidebarTotalWidth = 300;
        }

        const container = document.querySelector('.gantt-container') || ganttChartEl.parentElement;
        const containerWidth = Math.max(container.clientWidth, window.innerWidth - 40);

        let timelineAvailableWidth = containerWidth - sidebarTotalWidth;
        if (timelineAvailableWidth < 400) timelineAvailableWidth = Math.max(containerWidth * 0.6,
            400);

        const minDayWidth = 180;
        const daysToFitOnScreen = (ganttView === 'month') ? Math.min(totalDays, 10) : totalDays;
        let dayWidth = Math.floor(timelineAvailableWidth / Math.max(1, daysToFitOnScreen));
        if (dayWidth < minDayWidth) dayWidth = minDayWidth;

        const dayColumns = ganttChartEl.querySelectorAll('.day-column');
        dayHeaders.forEach(h => {
            h.style.minWidth = dayWidth + 'px';
            h.style.flex = '0 0 ' + dayWidth + 'px';
        });
        dayColumns.forEach(c => {
            c.style.minWidth = dayWidth + 'px';
            c.style.flex = '0 0 ' + dayWidth + 'px';
        });

        const timelineHeader = ganttChartEl.querySelector('.gantt-timeline-header');
        const rowContents = ganttChartEl.querySelectorAll('.gantt-row-content');
        const timelineTotalWidth = dayWidth * totalDays;
        if (timelineHeader) timelineHeader.style.minWidth = timelineTotalWidth + 'px';
        rowContents.forEach(rc => rc.style.minWidth = timelineTotalWidth + 'px');

        const rowSidebars = ganttChartEl.querySelectorAll('.gantt-row-sidebar');
        rowSidebars.forEach(sb => {
            sb.style.width = (sidebarHeaders[0] ? sidebarHeaders[0].getBoundingClientRect()
                .width + 'px' : '160px');
            sb.style.minWidth = (sidebarHeaders[0] ? sidebarHeaders[0]
                .getBoundingClientRect().width + 'px' : '160px');
            sb.style.boxSizing = 'border-box';
        });

        if (initialLoad) {
            const wrapper = document.querySelector('.gantt-container') || ganttChartEl
                .parentElement;
            try {
                wrapper.scrollLeft = 0;
            } catch (err) {}
            initialLoad = false;
        }
    })();
}

          function filterGanttChart(searchTerm) {
    if (!searchTerm) {
        // Show all shift bars and rows
        $('.gantt-bar').show();
        $('.gantt-row').show();
        // Update shift counts in sidebar
        $('.gantt-row').each(function() {
            const siteId = $(this).data('site-id');
            const visibleShifts = $(this).find('.gantt-bar:visible').length;
            $(this).find('.text-muted').text(`${visibleShifts} shift(s)`);
        });
        return;
    }
    
    const term = searchTerm.toLowerCase();
    $('.gantt-row').each(function() {
        const row = $(this);
        const siteText = row.find('.gantt-row-sidebar').text().toLowerCase();
        const shiftBars = row.find('.gantt-bar');
        let anyVisible = false;
        
        // Check if site/client name matches
        const siteMatches = siteText.includes(term);
        
        // Show/hide individual shift bars based on search
        shiftBars.each(function() {
            const bar = $(this);
            const barText = bar.text().toLowerCase();
            const barTitle = bar.attr('title') || '';
            const staffMatch = barText.includes(term) || barTitle.toLowerCase().includes(term);
            
            if (siteMatches || staffMatch) {
                bar.show();
                anyVisible = true;
            } else {
                bar.hide();
            }
        });
        
        // Show/hide entire row based on whether any shift is visible
        if (anyVisible) {
            row.show();
            // Update shift count in sidebar
            const visibleShifts = row.find('.gantt-bar:visible').length;
            row.find('.text-muted').text(`${visibleShifts} shift(s)`);
        } else {
            row.hide();
        }
    });
    
    // Also check if we need to show empty rows (sites with no matching shifts but matching site/client name)
    // This is already handled in the loop above
}

            function formatDate(date) {
                // Use local date to avoid timezone issues
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
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

            const shiftFilterFormEl = document.getElementById('shiftFilterForm');
            if (shiftFilterFormEl) {
                shiftFilterFormEl.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const form = e.target;
                    const formData = new FormData(form);
                    const filters = {};
                    for (const [k, v] of formData.entries())
                        if (v) filters[k] = v;

                    // Persist these filters so background reloads respect the user's selection
                    window._ganttCurrentFilters = filters;

                    const filteredShifts = allShiftsData.filter(shift => {
                        if (filters.staff && parseInt(shift.staff_id) !== parseInt(filters.staff))
                            return false;
                        // Check both client_id and clientId field names
                        if (filters.client_id) {
                            const shiftClientId = shift.client_id ?? shift.clientId;
                            if (parseInt(shiftClientId) !== parseInt(filters.client_id)) 
                                return false;
                        }
                        if (filters.site && parseInt(shift.site_id) !== parseInt(filters.site))
                            return false;
                        if (filters.status && parseInt(shift.status) !== parseInt(filters.status))
                            return false;
                        const shiftStart = new Date(shift.start_date);
                        if (filters.from_shift && shiftStart < new Date(filters.from_shift))
                            return false;
                        if (filters.to_shift && shiftStart > new Date(filters.to_shift))
                            return false;
                        return true;
                    });

                    renderCurrentView(filteredShifts, filters);
                    try {
                        bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
                    } catch (err) {}
                });
            }

            loadAllShiftsData();

            function escapeHtml(str) {
                if (!str && str !== 0) return '';
                return String(str).replace(/[&<>"'`=\/]/g, function(s) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                    '`': '&#x60;',
                        '=': '&#x3D;',
                        '/': '&#x2F;'
                    })[s];
                });
            }
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


    <script type="text/javascript">
        $(document).ready(function() {

            $('.staff-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            $('.client-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            $('.site-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            
            $('.select2_site').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#add_shift'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })

            $('.select2_edit_modal').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#multiEditModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })


            $('.select2_client').select2({
                    placeholder: "--choose--",
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#add_shift'), // make sure this matches your modal ID
                    minimumResultsForSearch: 0 // force search bar for single select
                })
                // Ensure Select2 selection and clear propagate a native change event
                .on('select2:select', function(e) {
                    $(this).trigger('change');
                })
                .on('select2:clear', function(e) {
                    $(this).val('');
                    $(this).trigger('change');
                });

            // Extra safeguard: directly handle Select2 select by calling AJAX
            $('.select2_client').on('select2:select', function(e) {
                var $target = $(this);
                var clientId = $target.val();
                if (!clientId) return;

                var $shiftGroup = $target.closest('.shift-group');
                if (!$shiftGroup.length) $shiftGroup = $target.parents('.shift-group').first();
                var $siteSelect = $shiftGroup.length ? $shiftGroup.find('#siteSelect') : $target.closest(
                    'form').find('#siteSelect');
                if (!$siteSelect || !$siteSelect.length) $siteSelect = $('#siteSelect');
                $siteSelect.html('<option value="">--choose--</option>');

                $.ajax({
                    url: `${baseUrl}/api/client/${clientId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        try {
                            $shiftGroup.find('.siteRate').val(data.client.office_rate || '');
                        } catch (err) {}
                        if (data.sites && data.sites.length > 0) {
                            $.each(data.sites, function(index, site) {
                                $siteSelect.append('<option value="' + site.id + '">' +
                                    site.site_name + '</option>');
                            });
                        } else {
                            $siteSelect.append('<option value="">No sites found</option>');
                        }
                        try {
                            if ($siteSelect.hasClass('select2')) $siteSelect.trigger(
                                'change.select2');
                            else $siteSelect.trigger('change');
                        } catch (err) {}
                    },
                    error: function(xhr, status, error) {
                        console.error('Fetch error:', error);
                    }
                });
            });
        });

        // Handle client -> site population for both native selects and Select2-enhanced selects
        $(document).on("change select2:select", "#clientSelect, .select2_client", function(e) {
            // Prefer the event target when it's the original <select>, otherwise use this
            var $target = $(e.target && e.target.nodeName === 'SELECT' ? e.target : this);
            const clientId = $target.val();
            console.debug('clientSelect handler fired, clientId=', clientId, 'event=', e.type);

            // Determine the shift-group context (may be multiple groups on the page)
            var $shiftGroup = $target.closest('.shift-group');
            if (!$shiftGroup.length) $shiftGroup = $target.parents('.shift-group').first();

            // Find the site select within the same group, falling back to global
            var $siteSelect = $shiftGroup.length ? $shiftGroup.find('#siteSelect') : $target.closest('form').find(
                '#siteSelect');
            if (!$siteSelect || !$siteSelect.length) $siteSelect = $('#siteSelect');

            // Reset options
            $siteSelect.html('<option value="">--choose--</option>');

            if (!clientId) {
                try {
                    $shiftGroup.find('.siteRate').val('');
                } catch (err) {}
                try {
                    $siteSelect.trigger('change');
                } catch (err) {}
                return;
            }

            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.debug('getClient response', data);
                    try {
                        $shiftGroup.find('.siteRate').val(data.client.office_rate || '');
                    } catch (err) {}
                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function(index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site
                                .site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }

                    // Notify enhancers (Select2) to refresh their UI if needed
                    try {
                        if ($siteSelect.hasClass('select2')) {
                            // trigger Select2-specific change
                            $siteSelect.trigger('change.select2');
                        } else {
                            $siteSelect.trigger('change');
                        }
                    } catch (err) {
                        /* ignore */ }
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

                        // store both note ID and shift-date ID on the Delete button
                        if (data.id) $('#deleteNoteBtn').data('note-id', data.id);
                        $('#deleteNoteBtn').data('shift-id', shiftId);

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
                    // Update the specific shift bar in-place so the UI reflects the new note
                    console.debug('Note save success for shiftId=', shiftId, res);
                    if (typeof refreshShiftBar === 'function') refreshShiftBar(shiftId, res.note);
                    // Force reload of shifts so the Gantt chart re-renders with authoritative data
                    if (window.loadAllShiftsData && typeof window.loadAllShiftsData === 'function') {
                        try {
                            window.loadAllShiftsData();
                        } catch (e) {
                            console.debug('window.loadAllShiftsData failed', e);
                        }
                    } else {
                        console.debug('window.loadAllShiftsData not available yet');
                    }
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
            // prefer shift-date id, fall back to stored note-id if that's being used
            const shiftId = $(this).data('shift-id') || $(this).data('note-id') || $('#shiftId').val();
            $('#confirmDeleteBtn').data('shift-id', shiftId);
            $('#viewNoteModal').modal('hide');
            $('#confirmDeleteModal').modal('show');
        });

        $(document).on('click', '#confirmDeleteBtn', function() {
            const shiftId = $(this).data('shift-id') || $(this).data('note-id') || $('#shiftId').val();
            if (!shiftId) return;

            $.ajax({
                url: `/shift-dates/${shiftId}/note`, // route expects shift_date id
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
                    // update any view-note-icons for this shift-date id
                    // Update only the affected bar so we don't reload the whole chart
                    console.debug('Note delete success for shiftId=', shiftId);
                    if (typeof refreshShiftBar === 'function') refreshShiftBar(shiftId, null);
                    // Force reload to ensure UI updates (safe, avoids full page refresh)
                    if (window.loadAllShiftsData && typeof window.loadAllShiftsData === 'function') {
                        try {
                            window.loadAllShiftsData();
                        } catch (e) {
                            console.debug('window.loadAllShiftsData failed', e);
                        }
                    } else {
                        console.debug('window.loadAllShiftsData not available yet');
                    }
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

        /**
         * Refresh a single shift bar in the Gantt chart.
         * - If `noteData` is provided, use it to update the icon immediately.
         * - Otherwise, try to find the shift in `allShiftsData` (loaded at page load),
         *   or re-fetch the shifts API as a fallback to get fresh data for that shift.
         */
        function refreshShiftBar(shiftId, noteData = undefined) {
            try {
                const idStr = String(shiftId);

                // If noteData provided, update the icon directly
                if (noteData !== undefined) {
                    // Ensure both possible icons are normalized
                    const viewIcon = $(`.view-note-icon[data-shift-id="${idStr}"]`);
                    const noteIcon = $(`.note-icon[data-shift-id="${idStr}"]`);

                    if (noteData) {
                        // Ensure a view-note-icon exists
                        if (noteIcon.length) {
                            noteIcon.removeClass('note-icon').addClass('view-note-icon').css('color', '#0d6efd');
                            noteIcon.html('📄');
                        }

                    } else {
                        // No note -> show inactive note-icon
                        if (viewIcon.length) {
                            viewIcon.removeClass('view-note-icon').addClass('note-icon').css('color', '#555');
                            viewIcon.html('📝');
                        }
                        if (noteIcon.length) {
                            noteIcon.css('color', '#555');
                        }
                    }
                    return;
                }

                // Try to find shift in allShiftsData (client-side cache)
                if (window.allShiftsData && Array.isArray(window.allShiftsData)) {
                    const found = window.allShiftsData.find(s => String(s.id) === idStr || String(s.shift_id) === idStr);
                    if (found) {
                        // If the Gantt bar exists, update its icon accordingly
                        const hasNote = !!(found.note || found.note_text || (found.note && found.note.note));
                        // Primary selector: bar-level icons
                        const viewIcon = $(`.view-note-icon[data-shift-id="${idStr}"]`);
                        const noteIcon = $(`.note-icon[data-shift-id="${idStr}"]`);

                        if (hasNote) {
                            if (noteIcon.length) noteIcon.removeClass('note-icon').addClass('view-note-icon').css('color',
                                '#0d6efd').html('📝');
                            if (viewIcon.length) viewIcon.css('color', '#0d6efd').html('📝');
                        } else {
                            if (viewIcon.length) viewIcon.removeClass('view-note-icon').addClass('note-icon').css('color',
                                '#555').html('📝');
                            if (noteIcon.length) noteIcon.css('color', '#555').html('📝');
                        }

                        // If no direct icon present (bar was re-rendered), attempt to find the bar element and inject/update the icon
                        const bar = $(`.gantt-bar[data-shift-id="${idStr}"]`);
                        if (bar.length && bar.find('.view-note-icon, .note-icon').length === 0) {
                            // create appropriate icon span
                            const iconClass = hasNote ? 'view-note-icon' : 'note-icon';
                            const color = hasNote ? '#0d6efd' : '#555';
                            const $icon = $(
                                `<span class="${iconClass}" data-shift-id="${idStr}" style="color:${color}">📝</span>`);
                            // append to bar
                            bar.append($icon);

                            // re-bind click handlers to the newly-created icon(s)
                            $icon.on('click', function(e) {
                                e.stopPropagation();
                                e.preventDefault();
                                const sid = $(this).data('shift-id');
                                if ($(this).hasClass('note-icon')) {
                                    $('#shiftId').val(sid);
                                    $('#noteForm')[0].reset();
                                    $('#noteType').val('guard');
                                    $('#noteText').val('');
                                    $('#noteModal').modal('show');
                                } else {
                                    $('#shiftId').val(sid);
                                    $.get(`/shift-dates/${sid}/note`, function(data) {
                                        if (data && data.note) {
                                            $('#viewNoteText').text(data.note);
                                            $('#viewNoteType').text(data.note_type);
                                            if (data.id) $('#deleteNoteBtn').data('note-id', data.id);
                                            $('#deleteNoteBtn').data('shift-id', sid);
                                            $('#viewNoteModal').modal('show');
                                        }
                                    });
                                }
                            });
                        }

                        // If the bar exists but icon still missing, attempt to fully re-render the day cell
                        if (bar.length && bar.find('.view-note-icon, .note-icon').length === 0) {
                            try {
                                const siteId = found.site_id || found.siteId || found.site_id;
                                const dateStr = found.start_date || found.shift_date || found.shift_date;
                                if (siteId && dateStr) {
                                    rerenderDayCell(siteId, dateStr);
                                }
                            } catch (err) {
                                console.error('rerenderDayCell fallback failed', err);
                            }
                        }

                        return;
                    }
                }

                // Fallback: fetch fresh data for all shifts and re-render the affected bar
                $.get(`${baseUrl}/api/shifts`, function(resp) {
                    // resp expected to be { data: [...] } in some implementations
                    const payload = resp.data || resp.shift_dates || resp;
                    let found = null;
                    if (Array.isArray(payload)) found = payload.find(s => String(s.id) === idStr || String(s
                        .shift_id) === idStr);
                    if (!found && resp.shift_dates && Array.isArray(resp.shift_dates)) found = resp.shift_dates
                        .find(s => String(s.id) === idStr || String(s.shift_id) === idStr);

                    if (!found) return;

                    // Update local cache if present
                    if (window.allShiftsData && Array.isArray(window.allShiftsData)) {
                        const idx = window.allShiftsData.findIndex(s => String(s.id) === idStr || String(s
                            .shift_id) === idStr);
                        if (idx !== -1) window.allShiftsData[idx] = found;
                    }

                    const hasNote = !!(found.note || (found.note && found.note.note));
                    const viewIcon = $(`.view-note-icon[data-shift-id="${idStr}"]`);
                    const noteIcon = $(`.note-icon[data-shift-id="${idStr}"]`);

                    if (hasNote) {
                        if (noteIcon.length) noteIcon.removeClass('note-icon').addClass('view-note-icon').css(
                            'color', '#0d6efd').html('📝');
                        if (viewIcon.length) viewIcon.css('color', '#0d6efd').html('📝');
                    } else {
                        if (viewIcon.length) viewIcon.removeClass('view-note-icon').addClass('note-icon').css(
                            'color', '#555').html('📝');
                        if (noteIcon.length) noteIcon.css('color', '#555').html('📝');
                    }
                });
            } catch (err) {
                console.error('refreshShiftBar error', err);
            }
        }

        // Helper: create the DOM element for a single shift bar from a shift object
        function createBarElement(shift) {
            if (!shift) return null;
            // lightweight local escaper to avoid depending on page-scoped escapeHtml
            function safeEscape(str) {
                if (str === null || str === undefined) return '';
                return String(str).replace(/[&<>"'`=\/]/g, function(s) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                    '`': '&#x60;',
                        '=': '&#x3D;',
                        '/': '&#x2F;'
                    })[s];
                });
            }
            const idStr = String(shift.id || shift.shift_id || shift.shiftId);
            const subcontractorMatch = shift.staff_name ? shift.staff_name.match(/\(([^)]+)\)/) : null;
            const subcontractor = subcontractorMatch ? subcontractorMatch[0] : '';
            const staffNameWithoutSub = subcontractorMatch ? shift.staff_name.replace(subcontractor, '').trim() : (shift
                .staff_name || '');
            const hasNote = !!(shift.note || (shift.note && shift.note.note));

            const $bar = $(`
<div class="gantt-bar shift-${shift.color_class || ''}" data-shift-id="${idStr}" title="${(shift.title || '')} - ${shift.staff_name || ''}">
    <input type="checkbox" class="multi-shift-checkbox" data-id="${idStr}" aria-label="Select shift ${idStr}">
    <div class="bar-content">
        ${shift.service_type ? `<div class="service-type">${safeEscape(shift.service_type)}</div>` : ''}
        <div class="time-text">${safeEscape(shift.formatted_time || shift.start_time || '')}</div>
        <div class="duration-text">${safeEscape(shift.duration || '')}</div>
        <div class="staff-name">${safeEscape(staffNameWithoutSub)}</div>
        ${subcontractor ? `<div class="subcontractor-name" style="display:none; font-weight:bold">${escapeHtml(subcontractor)}</div>` : ''}
    </div>
    ${hasNote ? `<span class="view-note-icon" data-shift-id="${idStr}" style="color:#0d6efd">📝</span>` : `<span class="note-icon" data-shift-id="${idStr}" style="color:#555">📝</span>`}
</div>
            `);

            // checkbox behavior
            $bar.find('.multi-shift-checkbox').on('click', function(e) {
                e.stopPropagation();
            });

            // icon handlers
            $bar.find('.note-icon').on('click', function(e) {
                e.stopPropagation();
                const sid = $(this).data('shift-id');
                $('#shiftId').val(sid);
                $('#noteForm')[0].reset();
                $('#noteType').val('guard');
                $('#noteText').val('');
                $('#noteModal').modal('show');
            });
            $bar.find('.view-note-icon').on('click', function(e) {
                e.stopPropagation();
                const sid = $(this).data('shift-id');
                $('#shiftId').val(sid);
                $.get(`/shift-dates/${sid}/note`, function(data) {
                    if (data && data.note) {
                        $('#viewNoteText').text(data.note);
                        $('#viewNoteType').text(data.note_type);
                        if (data.id) $('#deleteNoteBtn').data('note-id', data.id);
                        $('#deleteNoteBtn').data('shift-id', sid);
                        $('#viewNoteModal').modal('show');
                    }
                });
            });

            return $bar;
        }

        // Re-render a single day cell (siteId, dateStr) using cached window.allShiftsData.
        function rerenderDayCell(siteId, dateStr) {
            try {
                console.debug('rerenderDayCell', siteId, dateStr);
                const cell = $(`#cell-${siteId}-${dateStr}`);
                if (!cell.length) return;

                // find shifts for this site/date from cache
                const shiftsForCell = (window.allShiftsData || []).filter(s => String(s.site_id || s.siteId) === String(
                    siteId) && (s.start_date === dateStr || s.shift_date === dateStr || s.shift_date === dateStr ||
                    s.shift_date === dateStr));

                cell.empty();
                shiftsForCell.forEach(shift => {
                    const $bar = createBarElement(shift);
                    if ($bar) cell.append($bar);
                });

                // Ensure padding/size matches selectionMode
                cell.find('.gantt-bar').css('padding-left', getBarLeftPadding());
            } catch (err) {
                console.error('rerenderDayCell error', err);
            }
        }
    </script>


@endsection
