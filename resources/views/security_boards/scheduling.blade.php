@extends('layouts.app')
@section('title', 'SPL Connect - Scheduling')
@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Flatpickr CSS -->
    <style>
        .gantt-header,
        .gantt-row {
            display: flex;
            width: 100%;
        }

        .gantt-row-content,
        .gantt-timeline-header {
            flex-shrink: 0;
        }

        /* .gantt-timeline-header,
                                .gantt-row-content {
                                    display: grid;
                                    grid-template-columns: repeat(7, 1fr);
                                    flex: 1;
                                } */

        /* .gantt-timeline-header,
                                                    .gantt-row-content {
                                                        display: flex;
                                                        flex: 1;
                                                        width: 100%;
                                                        min-width: 100%;
                                                    } */

        html {
            /* Was 80% which made the wording unreadable when the chart was zoomed.
               90% keeps the chart compact while staying legible. */
            font-size: 90%;
        }

        .gantt-container {
            overflow-x: scroll;
            overflow-y: auto;
            margin-top: 20px;
            /* border: 1px solid #dee2e6; */
            border-radius: 6px;
            width: 100%;
            -webkit-overflow-scrolling: touch;
            /* Match top scrollbar colors/thickness for Firefox */
            scrollbar-color: #d32f2f #fff0f0;
            scrollbar-width: thin;
            /* Reserve gutter so layout doesn't shift when scrollbars appear */
            scrollbar-gutter: stable both-edges;
        }
        /* Top horizontal scrollbar placed above the Gantt chart.
           This provides an independent visual horizontal scroller with
           a bold red thumb. Width is synced to the Gantt timeline by JS. */
        .gantt-top-scroll-wrapper {
            overflow-x: scroll;
            overflow-y: hidden;
            height: 18px;
            margin-bottom: 10px;
            border-radius: 6px;
            -webkit-overflow-scrolling: touch;
            scrollbar-color: #d32f2f #fff0f0;
            scrollbar-width: thin;
            scrollbar-gutter: stable;
        }

        .gantt-top-scroll-inner {
            /* hide the decorative red bar while keeping the inner spacer for scroll width */
            height: 2px;
            background: transparent;
            border-radius: 6px;
            font-weight: 700;
            pointer-events: none;
        }

        /* WebKit scrollbar styling for the top scroller */
        .gantt-top-scroll-wrapper::-webkit-scrollbar {
            height: 14px;
        }
        .gantt-top-scroll-wrapper::-webkit-scrollbar-track {
            background: #fff0f0;
        }
        .gantt-top-scroll-wrapper::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #d32f2f, #b71c1c);
            border-radius: 10px;
            border: 3px solid #fff0f0;
        }

        /* Bottom (native) Gantt scrollbar: match top scroller appearance */
        .gantt-container::-webkit-scrollbar {
            height: 14px;
        }
        .gantt-container::-webkit-scrollbar-track {
            background: #fff0f0;
        }
        .gantt-container::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #d32f2f, #b71c1c);
            border-radius: 10px;
            border: 3px solid #fff0f0;
        }
        .gantt-container::-webkit-scrollbar-thumb:hover {
            filter: brightness(0.95);
        }

        /* active toggle */
        .btn-gantt-view.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .gantt-header {
            display: flex;
            width: 100%;

        }

        .gantt-sidebar-header {
            width: 100px;
            flex-shrink: 0;
            padding: 4px;
            font-size: 10px;
            font-weight: 800;
            color: #212529;
            background-color: #e9ecef;
            /* border-right: 1px solid #dee2e6; */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .gantt-timeline-header {
            display: flex;
            flex: 1 1 auto;
            width: 100%;
        }

        .gantt-body {
            display: flex;
            flex-direction: column;
            min-width: 100%;
        }

        /* row height reduced to make bars more compact; rows grow to fit their
           shifts but stay short when a site has only one/no shift, so the chart
           no longer leaves large empty bands after shifts. */
        .gantt-row {
            display: flex;
            min-height: 64px;
            /* border-bottom: 1px solid #dee2e6; */
            position: relative;
        }

        /* Ensure the row divider is always visible above inner content by
                                                           drawing it with a pseudo-element. This prevents wide day-columns
                                                           or inner elements from visually covering the separator. */
        .gantt-row::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: #dee2e6;
            z-index: 1000;
            /* high enough to appear above row contents but below modals */
            pointer-events: none;
        }



        .gantt-row-sidebar {
            width: 100px;
            flex-shrink: 0;
            padding: 4px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        .gantt-row-content {
            flex: 1 1 auto;
            position: relative;
            display: flex;
            /* width: 100%; */
        }

        /* Day column: allow JS to set fixed widths per day so a full week
                                                           can be forced to fit the available container width. Keep
                                                           box-model and overflow handling here but remove a large CSS
                                                           min-width that caused overly wide columns on large screens. */
        .day-column {

            border-right: 1px solid #dee2e6;
            box-sizing: border-box;
            flex: 0 0 auto;
            flex-shrink: 0
        }

        .day-header {
            width: 100%;
            text-align: center;
            padding: 6px;
            font-size: 10px;
            font-weight: 800;
            box-sizing: border-box;
            flex: 0 0 auto;
            background-color: #f8f9fa;
            /* border-bottom: 2px solid #dee2e6; */
        }

        /* day-cell: grid of bars; min-height kept small so empty/sparse days don't
           create tall empty rows. Bars set their own height as content requires.
           No per-cell box border: the day columns already have a right divider and
           rows a bottom divider, so a cell border would draw an uneven box around
           cells that have more shifts than their neighbours (looked confusing). */
        .day-cell {
            display: grid;
            gap: 5px;
            min-height: 60px;
            padding: 6px;
            align-content: start;
        }

        /* Bars grow to fill available row width; at most 4-5 per row in a 760px column */
        /* .day-cell>.gantt-bar {
                                                        flex: 1 1 140px;
                                                        min-width: 130px;
                                                        max-width: 100%;
                                                        box-sizing: border-box;
                                                        align-self: start;
                                                    } */

        /* Bar layout: readable and compact */
        .gantt-bar {

            min-height: 40px;
            padding: 4px 6px;
            width: 100%;
            /* min-width: 100px; */
            border-radius: 5px;
            box-sizing: border-box;
            cursor: pointer;
            display: flex;
            color: #fff;
            gap: 4px;
            font-size: 11px;
        }

        #ganttChart.day-view .day-cell {
            grid-template-columns: repeat(auto-fill, 160px);
            justify-content: flex-start;

        }

        #ganttChart.day-view .day-column {
            width: 100%;
        }

        #ganttChart.day-view .gantt-bar {
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }

        /* .bar-content holds each piece on its own line */
        .gantt-bar .bar-content {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            overflow: hidden;
            text-overflow: ellipsis;
            /* line-height: 1.15; */
            gap: 2px;
        }

        /* each content piece is block so it displays on separate lines */
        .gantt-bar .service-type,
        .gantt-bar .duration-text {
            display: block;
            color: inherit;
            margin: 0;
            font-size: 10px;
            font-weight: 500;
        }

        .gantt-bar .staff-name {
            display: block;
            color: inherit;
            margin: 0;
            font-size: 11px;
            font-weight: 700;
        }

        .gantt-bar .subcontractor-name {
            display: block;
            color: inherit;
            margin: 0;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            opacity: 0.96;
        }

        /* time-text with icons inline */
        .gantt-bar .time-text {
            display: flex;
            align-items: center;
            gap: 4px;
            justify-content: space-between;
            color: inherit;
            margin: 0;
            font-size: 10px;
            font-weight: 600;
            flex-wrap: wrap;
        }

        .gantt-bar .bar-actions {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* subcontractor inline when appended to staff-name should be normal weight */
        .gantt-bar .staff-name .subcontractor-inline {
            font-weight: 400;
            font-size: 9px;
            color: inherit;
            margin-left: 4px;
        }

        /* small duration text */
        .gantt-bar small {
            font-size: 8px;
            opacity: 0.9;
            color: inherit;
        }

        /* Hover */
        .gantt-bar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Checkbox - in-flow flex item, hidden by default so it takes no space */
        .gantt-bar .multi-shift-checkbox {
            flex: 0 0 14px;
            width: 14px;
            height: 14px;
            margin-top: 2px;
            cursor: pointer;
            background: #fff;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: none;
            flex-shrink: 0;
        }

        .selection-mode .gantt-bar .multi-shift-checkbox {
            display: block;
        }

        /* note icon: dimmed pencil = no note yet */
        .gantt-bar .note-icon,
        .gantt-bar .view-note-icon {
            cursor: pointer;
            font-size: 11px;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            border-radius: 4px;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease,
                color 0.15s ease;
            flex-shrink: 0;
        }

        .gantt-bar .note-icon {
            color: #1f1f1f;
            background: rgba(255, 255, 255, 0.22);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        }

        /* view-note icon: amber highlight = note exists */
        .gantt-bar .view-note-icon {
            color: #fff;
            background: #c62828;
            box-shadow: 0 0 0 1px rgba(123, 18, 18, 0.4);
        }

        .gantt-bar .note-icon:hover,
        .gantt-bar .view-note-icon:hover {
            transform: scale(1.08);
        }

        /* Notes thread (inside view-note modal) */
        .notes-thread {
            max-height: 360px;
            overflow-y: auto;
        }

        .notes-thread .note-card {
            border: 1px solid #e3e6ea;
            border-left: 4px solid #c62828;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
            background: #fff;
        }

        .notes-thread .note-card .note-meta {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .notes-thread .note-card .note-body {
            white-space: pre-wrap;
            word-break: break-word;
            color: #212529;
        }

        .notes-thread .note-card .note-type-badge {
            text-transform: capitalize;
        }

        .notes-thread .note-card .note-actions {
            margin-top: 8px;
            display: flex;
            gap: 6px;
        }

        .notes-thread .notes-empty {
            color: #6c757d;
            font-style: italic;
        }

        /* edit icon (pencil) - inline with time row */
        .gantt-bar .edit-shift-icon {
            cursor: pointer;
            font-size: 8px;
            opacity: 0.9;
            /* display: inline-flex;
                            align-items: center;
                            flex-shrink: 0;
                            opacity: 0.8;
                            color: inherit;
                            vertical-align: middle; */
        }

        /*
                        .gantt-bar .edit-shift-icon svg {
                            width: 14px;
                            height: 14px;
                        } */

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
            width: 100%;
            /* min-width: 100%; */
            display: block;
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

        @media (min-width:1600px) {

            .gantt-bar .service-type,
            .gantt-bar .duration-text {
                font-size: 13px
            }

            .gantt-bar .staff-name,
            .gantt-bar .subcontractor-name {
                font-size: 14px
            }

            .gantt-bar .time-text {
                font-size: 12px
            }

            .gantt-bar .note-icon,
            .gantt-bar .view-note-icon {
                font-size: 14px;
                width: 22px;
                height: 22px
            }
            .gantt-bar .edit-shift-icon {
                font-size: 10px
            }

            #scheduling .status-summary div {
                font-size: 16px
            }

            .gantt-sidebar-header {
                font-size: 13px
            }

            .gantt-row-sidebar {
                font-size: 13px
            }

            #currentWeekDisplay {
                font-size: 14px
            }

            .gantt-legend-item {
                font-size: 14px
            }

            .gantt-legend-color {
                width: 18px;
                height: 18px;
            }

            .day-header {
                font-size: 13px
            }
        }

        @media (min-width:3000px) {

            .gantt-bar .service-type,
            .gantt-bar .duration-text {
                font-size: 16px
            }

            .gantt-bar .staff-name,
            .gantt-bar .subcontractor-name {
                font-size: 17px
            }

            .gantt-bar .time-text {
                font-size: 13px
            }

            .gantt-bar .note-icon,
            .gantt-bar .view-note-icon {
                font-size: 16px;
                width: 24px;
                height: 24px
            }
            .gantt-bar .edit-shift-icon {
                font-size: 13px
            }

            #scheduling .status-summary div {
                font-size: 16px
            }

            .gantt-sidebar-header {
                font-size: 15px
            }

            .gantt-row-sidebar {
                font-size: 13px
            }

            #currentWeekDisplay {
                font-size: 16px
            }

            .gantt-legend-item {
                font-size: 16px
            }

            .gantt-legend-color {
                width: 20px;
                height: 20px;
            }

            .day-header {
                font-size: 16px
            }
        }

        @media (max-width: 992px) {
            .gantt-row {
                min-height: 70px;
            }

            .gantt-sidebar-header,
            .gantt-row-sidebar {
                min-width: 120px;
            }
        }

        @media (max-width: 768px) {
            .day-cell {
                padding: 5px;
            }

            .gantt-row {
                min-height: 70px;
            }

            .gantt-sidebar-header,
            .gantt-row-sidebar {
                min-width: 100px;
                font-size: 12px;
            }

            .gantt-bar {
                padding: 4px 6px;
                font-size: 10px;
                height: auto;
            }

            .gantt-bar .multi-shift-checkbox {
                width: 12px;
                height: 12px;
                margin-top: 3px;
            }
        }

        /* On very narrow phones: looser min-width for bars so more fit per row */
        @media (max-width: 520px) {
            .day-cell {
                padding: 4px;
            }

            .day-cell>.gantt-bar {
                min-width: 120px;
            }

            .gantt-bar {
                padding: 4px 5px;
                font-size: 9px;
                height: auto;
            }
        }

        @media (max-width: 1200px) {

            /* .day-cell {
                                            grid-template-columns: repeat(3, 1fr);
                                        } */

        }

        @media (max-width: 768px) {

            /* .day-cell {
                                            grid-template-columns: repeat(2, 1fr);
                                        } */

        }

        @media (max-width: 480px) {

            /* .day-cell {
                                            grid-template-columns: repeat(1, 1fr);
                                        } */

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

                            <!-- Top synchronized scrollbar (click/drag to scroll timeline) -->
                            <div id="ganttTopScroll" class="gantt-top-scroll-wrapper" aria-hidden="false" title="Scroll timeline">
                                <div id="ganttTopScrollInner" class="gantt-top-scroll-inner"></div>
                            </div>

                            <div class="gantt-container gantt-wrapper">
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
            @include('security_boards.edit')

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
        <div class="modal-dialog modal-dialog-centered modal-lg">
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shift Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="viewNoteShiftId" value="">

                    {{-- Thread of notes (newest first) --}}
                    <div id="notesThread" class="notes-thread mb-3"></div>

                    {{-- Add-new-note form (hidden until "Add Note" is clicked) --}}
                    <div id="addNoteInline" class="border rounded p-3 bg-light" style="display:none;">
                        <h6 class="mb-2" id="addNoteInlineTitle">Add a new note</h6>
                        <input type="hidden" id="inlineNoteId" value="">
                        <div class="mb-2">
                            <label for="inlineNoteType" class="form-label">Note For</label>
                            <select id="inlineNoteType" class="form-select">
                                <option value="guard">Guard</option>
                                <option value="control">Control Room</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="inlineNoteText" class="form-label">Note</label>
                            <textarea id="inlineNoteText" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" id="inlineNoteSaveBtn">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="inlineNoteCancelBtn">Cancel</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-success" id="addNewNoteBtn">
                        <i class="fa-solid fa-plus"></i> Add Note
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        // Lightweight jQuery -> Bootstrap 5 modal polyfill.
        // If code calls `$(...).modal('show')` (Bootstrap 4 style), this will delegate
        // to the Bootstrap 5 `bootstrap.Modal` API when available, preserving
        // existing callsites without changing other code.
        (function() {
            if (window.jQuery && (typeof window.jQuery.fn.modal !== 'function')) {
                window.jQuery.fn.modal = function(action) {
                    try {
                        const show = action === 'show';
                        const hide = action === 'hide';
                        this.each(function() {
                            const el = this;
                            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                                if (show) {
                                    try {
                                        new window.bootstrap.Modal(el).show();
                                    } catch (e) {}
                                } else if (hide) {
                                    try {
                                        const inst = window.bootstrap.Modal.getInstance(el);
                                        if (inst) inst.hide();
                                    } catch (e) {}
                                }
                            }
                        });
                    } catch (e) {}
                    return this;
                };
            }
        })();
    </script>
    <script>
        window.isSuperAdmin = @json(auth()->check() && auth()->user()->getRoleNames()->contains('superadmin'));
    </script>
    <script>
        // Prepopulate subcontractor id->name map from server-provided list (if available)
        window._subcontractorMap = window._subcontractorMap || {};
        @if (isset($subcontractors) && $subcontractors)
            window._subcontractorMap = @json(
                $subcontractors->mapWithKeys(function ($u) {
                    return [$u->id => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))];
                }));
        @endif
    </script>

    <script>
        /**
         * Layout helper: sizes every day column to an equal, responsive width that
         * fills the available timeline area. Smaller screens show ~5 days, wider
         * screens show the whole range — without dumping leftover space onto columns
         * (which previously left large empty gaps after shifts).
         */
        function adjustGanttDayCellColumns() {
            if (document.querySelector('#ganttChart').classList.contains('day-view')) {
                return;
            }

            const ganttChartEl = document.getElementById('ganttChart');
            if (!ganttChartEl) return;

            const container = document.querySelector('.gantt-container');

            // Group all day columns by date (each site row contributes one column per date)
            const dateGroups = {};
            const dayCols = ganttChartEl.querySelectorAll(".day-column");

            dayCols.forEach(dc => {
                const date = dc.dataset.date;
                if (!date) return;
                if (!dateGroups[date]) dateGroups[date] = [];
                dateGroups[date].push(dc);
            });

            const dates = Object.keys(dateGroups);
            const totalDays = dates.length;
            if (totalDays === 0) return;

            // Measure the actual sidebar width (Client + Site columns) so the timeline
            // calculation stays accurate across screen sizes / zoom levels.
            const sidebarHeaders = ganttChartEl.querySelectorAll('.gantt-sidebar-header');
            let sidebarWidth = 0;
            sidebarHeaders.forEach(h => { sidebarWidth += h.getBoundingClientRect().width; });
            if (!sidebarWidth) sidebarWidth = 200;

            const containerWidth = container ? container.clientWidth : ganttChartEl.clientWidth;
            const availableWidth = Math.max(containerWidth - sidebarWidth - 4, 320);

            // Responsive target: how many day columns we try to fit on screen at once.
            // Smaller screens show ~5 days; larger screens show the full week (or month
            // run) without leaving big empty gaps after the shifts.
            const minDayWidth = 150; // a single shift bar fits comfortably at this width
            let visibleDays = Math.floor(availableWidth / minDayWidth);
            if (visibleDays < 1) visibleDays = 1;
            // Cap so we never try to spread fewer days than we actually have, and on
            // narrower screens keep it to a sensible ~5 so wording stays readable.
            visibleDays = Math.min(visibleDays, totalDays);

            // Equal-width columns that exactly fill the available width — no leftover
            // space gets dumped onto individual columns, which was the source of the
            // large gaps after shifts.
            let dayWidth = Math.floor(availableWidth / visibleDays);
            if (dayWidth < minDayWidth) dayWidth = minDayWidth;

            // How many shift bars fit side-by-side in one day column.
            const barMinWidth = 140;
            const gap = 5;
            const cellPadding = 12; // 6px left + 6px right
            let gridColumns = Math.floor((dayWidth - cellPadding + gap) / (barMinWidth + gap));
            if (gridColumns < 1) gridColumns = 1;
            if (gridColumns > 4) gridColumns = 4;

            const totalWidth = dayWidth * totalDays;

            // Apply equal width to every day column + header, and set the inner grid.
            dates.forEach(date => {
                dateGroups[date].forEach(dc => {
                    dc.style.width = dayWidth + "px";
                    dc.style.minWidth = dayWidth + "px";
                    dc.style.flex = "0 0 " + dayWidth + "px";

                    const cell = dc.querySelector(".day-cell");
                    if (cell) cell.style.gridTemplateColumns = `repeat(${gridColumns}, 1fr)`;
                });

                const headerEl = ganttChartEl.querySelector(`.day-header[data-date="${date}"]`);
                if (headerEl) {
                    headerEl.style.width = dayWidth + "px";
                    headerEl.style.minWidth = dayWidth + "px";
                    headerEl.style.flex = "0 0 " + dayWidth + "px";
                }
            });

            // Size the timeline header + row content to the total so columns line up.
            const header = ganttChartEl.querySelector(".gantt-timeline-header");
            const rows = ganttChartEl.querySelectorAll(".gantt-row-content");

            if (header) {
                header.style.width = totalWidth + "px";
                header.style.minWidth = totalWidth + "px";
                header.style.flex = "0 0 " + totalWidth + "px";
                header.style.display = "flex";
            }

            rows.forEach(r => {
                r.style.width = totalWidth + "px";
                r.style.minWidth = totalWidth + "px";
                r.style.flex = "0 0 " + totalWidth + "px";
                r.style.display = "flex";
            });

            ganttChartEl.style.width = "100%";
        }

        /**
         * Alternative layout function that also groups by date
         */


        /**
         * Fit week to screen width (equal distribution)
         */

        /**
         * Simple per-cell grid adjustment (kept for compatibility)
         */
        function adjustDayCells() {
            document.querySelectorAll('.day-cell').forEach(cell => {
                const shifts = cell.querySelectorAll('.gantt-bar').length;
                let columns = Math.min(shifts, 4);
                if (columns === 0) columns = 1;
                cell.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
            });
        }

        // Initialize on DOM ready
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(() => {

                adjustGanttDayCellColumns();
            }, 100);

            setTimeout(() => {

                adjustGanttDayCellColumns();
            }, 500);
        });

        // Debounced resize handler
        let resizeTimer;
        window.addEventListener("resize", () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {

                adjustGanttDayCellColumns();
            }, 150);
        });

        // MutationObserver for dynamic content changes.
        // Guard: skip expensive layout recalc while a bulk render is in progress
        // (renderGanttChart sets window._ganttRendering = true for the duration).
        (function() {
            const ganttEl = document.getElementById('ganttChart');
            if (ganttEl && window.MutationObserver) {
                const mo = new MutationObserver(() => {
                    if (window._ganttRendering) return;
                    adjustGanttDayCellColumns();
                    // Clean up any collapse buttons
                    try {
                        $('.gantt-cell-more-btn').remove();
                    } catch (e) {}
                });
                mo.observe(ganttEl, {
                    childList: true,
                    subtree: true
                });
            }
        })();
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


            // bind specifically to the Multi-Edit modal's controls to avoid
            // collisions with other modals that reuse the same IDs
            const modal = document.getElementById('multiEditModal');
            if (!modal) return;

            const staffSelect = modal.querySelector('#staff_id');
            const subSelect = modal.querySelector('#subcontractor');

            // global helper so delegated listeners work even if elements are initialised later
            window.fetchSubcontractorsForModal = window.fetchSubcontractorsForModal || function(modalEl, userId) {
                try {
                    console.log('multi-edit: fetchSubcontractorsForModal', userId);
                    if (!modalEl) return;
                    const sub = modalEl.querySelector('#subcontractor');
                    if (!sub) return;
                    // reset
                    sub.innerHTML = '<option value="">--choose--</option>';
                    if (!userId) return;

                    fetch(`/subcontractors/for-employee/${userId}`)
                        .then(function(res) {
                            return res.json();
                        })
                        .then(function(json) {
                            const rows = (json && json.data) ? json.data : [];
                            rows.forEach(function(r) {
                                const opt = document.createElement('option');
                                opt.value = r.id ?? r.user_id ?? '';
                                const name = (r.first_name ?? r.company_name ?? '') + (r.last_name ?
                                    ' ' + r.last_name : '');
                                opt.textContent = name || (r.email ?? '');
                                sub.appendChild(opt);
                            });

                            if (window.jQuery && jQuery(sub).data('select2')) {
                                jQuery(sub).trigger('change');
                            }
                        })
                        .catch(function(err) {
                            console.error('Failed to load subcontractors for employee', err);
                        });
                } catch (e) {
                    console.error('fetchSubcontractorsForModal error', e);
                }
            };

            // Delegated native change listener (catches dynamic elements)
            document.addEventListener('change', function(e) {
                const el = e.target;
                if (!el) return;
                if (el.id === 'staff_id') {
                    const m = el.closest('#multiEditModal');
                    if (m) window.fetchSubcontractorsForModal(m, el.value);
                }
            });

            // Delegated Select2 listener via jQuery
            try {
                if (window.jQuery) {
                    jQuery(document).on('select2:select', '#multiEditModal #staff_id', function() {
                        const m = jQuery(this).closest('#multiEditModal')[0];
                        window.fetchSubcontractorsForModal(m, jQuery(this).val());
                    });
                }
            } catch (e) {}

            // Persist current filters so background refreshes don't reset user's view
            window._ganttCurrentFilters = window._ganttCurrentFilters || {};

            // Initialize subcontractor-toggle button from persisted state
            try {
                var _initSubs = false;
                try { _initSubs = JSON.parse(localStorage.getItem('gantt_subs_visible') || 'false'); } catch (e) {}
                var $initBtn = $('#toggle-subcontractors-all');
                if ($initBtn && $initBtn.length) {
                    $initBtn.data('subs-visible', !!_initSubs);
                    $initBtn.text(_initSubs ? 'Hide Subcontractors' : 'Show Subcontractors');
                }
            } catch (e) {}

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
                                        console.error('Override failed:',
                                            err);
                                        let msg =
                                            'Failed to override shift. Try again.';
                                        try {
                                            if (err && err.responseJSON) {
                                                if (err.responseJSON.error)
                                                    msg = err.responseJSON
                                                    .error;
                                                else if (err.responseJSON
                                                    .message) msg = err
                                                    .responseJSON.message;
                                                else if (typeof err
                                                    .responseJSON ===
                                                    'string') msg = err
                                                    .responseJSON;
                                            } else if (err && err
                                                .responseText) {
                                                try {
                                                    const parsed = JSON
                                                        .parse(err
                                                            .responseText);
                                                    if (parsed.error) msg =
                                                        parsed.error;
                                                    else if (parsed.message)
                                                        msg = parsed
                                                        .message;
                                                } catch (e) {
                                                    msg = err.responseText;
                                                }
                                            }
                                        } catch (e) {
                                            // ignore parsing errors
                                        }
                                        showToast(msg, 'error', 7000);

                                        if (err && err.responseJSON && err
                                            .responseJSON.trace) {
                                            console.debug('Override trace:',
                                                err.responseJSON.trace);
                                        } else if (err && err
                                            .responseText) {
                                            console.debug(
                                                'Override responseText:',
                                                err.responseText);
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

                            // If ban restriction present, show in toast (non-overridable)
                            if (errors.ban_forbidden) {
                                showToast(errors.ban_forbidden[0], 'error', 7000);
                            }

                            $.each(errors, function(key, value) {
                                if (key === 'ban_forbidden') return; // already handled
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

            const persistedFilterKeys = ['staff', 'client_id', 'site', 'subcontractor', 'status', 'from_shift', 'to_shift'];

            function collectActiveFiltersFromForm(formEl) {
                const filters = {};
                if (!formEl) return filters;

                const formData = new FormData(formEl);
                for (const [key, value] of formData.entries()) {
                    if (value !== null && String(value).trim() !== '') {
                        filters[key] = value;
                    }
                }

                return filters;
            }

            function applyFiltersToShifts(sourceShifts, filters = {}) {
                const shifts = Array.isArray(sourceShifts) ? sourceShifts : [];

                return shifts.filter(shift => {
                    const shiftStaffId = shift.staff_id || shift.staffId || shift.staff || null;
                    const shiftClientId = shift.client_id || shift.clientId || shift.client || null;
                    const shiftSiteId = shift.site_id || shift.siteId || shift.site || null;
                    // Subcontractor filter matches by the resolved subcontractor USER id
                    // (the dropdown lists subcontractor users). Fall back to the raw
                    // stored subcontractor_id when the user id wasn't resolved.
                    const shiftSubcontractorId = shift.subcontractor_user_id || shift.subcontractor_id ||
                        null;
                    const shiftStatus = (typeof shift.status !== 'undefined') ? shift.status : (shift
                        .state || null);
                    const shiftStartRaw = shift.start_date || shift.shift_date || shift.startDate || shift
                        .start || null;

                    if (filters.staff && parseInt(shiftStaffId, 10) !== parseInt(filters.staff, 10))
                        return false;

                    if (filters.client_id) {
                        if (shiftClientId === null || parseInt(shiftClientId, 10) !== parseInt(filters
                                .client_id, 10))
                            return false;
                    }

                    if (filters.site) {
                        if (shiftSiteId === null || parseInt(shiftSiteId, 10) !== parseInt(filters.site,
                                10))
                            return false;
                    }

                    if (filters.subcontractor) {
                        if (shiftSubcontractorId === null || parseInt(shiftSubcontractorId, 10) !== parseInt(
                                filters.subcontractor, 10))
                            return false;
                    }

                    if (filters.status && (shiftStatus === null || parseInt(shiftStatus, 10) !== parseInt(
                            filters.status, 10)))
                        return false;

                    const shiftStart = shiftStartRaw ? new Date(shiftStartRaw) : null;
                    if (filters.from_shift && shiftStart && shiftStart < new Date(filters.from_shift))
                        return false;
                    if (filters.to_shift && shiftStart && shiftStart > new Date(filters.to_shift))
                        return false;

                    return true;
                });
            }

            function parseIsoDate(dateStr) {
                if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(String(dateStr))) return null;
                const parts = String(dateStr).split('-');
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                const parsed = new Date(year, month, day);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            }

            function normalizeToStartOfDay(dateObj) {
                const d = dateObj instanceof Date ? dateObj : new Date(dateObj);
                return new Date(d.getFullYear(), d.getMonth(), d.getDate());
            }

            function applyGanttViewState(viewName, anchorDate = new Date()) {
                const safeView = ['day', 'week', 'month'].includes(viewName) ? viewName : 'week';
                const baseDate = normalizeToStartOfDay(anchorDate);

                ganttView = safeView;

                if (safeView === 'day') {
                    currentWeekStart = new Date(baseDate);
                    currentWeekEnd = new Date(baseDate);
                    currentWeekEnd.setHours(23, 59, 59, 999);
                } else if (safeView === 'week') {
                    currentWeekStart = getMonday(baseDate);
                    currentWeekEnd = new Date(currentWeekStart);
                    currentWeekEnd.setDate(currentWeekEnd.getDate() + 6);
                } else {
                    currentWeekStart = new Date(baseDate.getFullYear(), baseDate.getMonth(), 1);
                    currentWeekEnd = new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 0);
                }

                try {
                    const activeButton = safeView === 'day' ? '#viewDayBtn' : (safeView === 'week' ?
                        '#viewWeekBtn' : '#viewMonthBtn');
                    setActiveGanttView(activeButton);
                } catch (err) {}

                const chart = document.querySelector('#ganttChart');

                if (chart) {
                    chart.classList.remove('day-view', 'week-view', 'month-view');
                    chart.classList.add(`${safeView}-view`);
                }
            }

            function persistCurrentGanttViewState() {
                syncSchedulingStateToUrl(window._ganttCurrentFilters || {}, {
                    ganttSearch: $('#ganttSearch').val(),
                    ganttView: ganttView,
                    ganttDate: formatDate(currentWeekStart)
                });
            }

            function syncSchedulingStateToUrl(filters = {}, extra = {}) {
                const url = new URL(window.location.href);
                const params = url.searchParams;

                persistedFilterKeys.forEach(key => {
                    const value = filters[key];
                    if (value !== undefined && value !== null && String(value).trim() !== '') {
                        params.set(key, value);
                    } else {
                        params.delete(key);
                    }
                });

                if (extra.ganttSearch && String(extra.ganttSearch).trim() !== '') {
                    params.set('ganttSearch', String(extra.ganttSearch).trim());
                } else {
                    params.delete('ganttSearch');
                }

                const requestedView = extra.ganttView || ganttView;
                if (requestedView && ['day', 'week', 'month'].includes(String(requestedView))) {
                    params.set('ganttView', String(requestedView));
                } else {
                    params.delete('ganttView');
                }

                const requestedDate = extra.ganttDate || formatDate(currentWeekStart);
                if (requestedDate && /^\d{4}-\d{2}-\d{2}$/.test(String(requestedDate))) {
                    params.set('ganttDate', String(requestedDate));
                } else {
                    params.delete('ganttDate');
                }

                const nextUrl =
                    `${url.pathname}${params.toString() ? `?${params.toString()}` : ''}${url.hash || ''}`;
                window.history.replaceState({}, '', nextUrl);
            }

            function restoreSchedulingStateFromUrl(formEl) {
                const params = new URLSearchParams(window.location.search);
                const restoredFilters = {};

                persistedFilterKeys.forEach(key => {
                    const value = params.get(key);
                    if (value !== null && value !== '') {
                        restoredFilters[key] = value;
                        if (formEl) {
                            const field = formEl.querySelector(`[name="${key}"]`);
                            if (field) field.value = value;
                        }
                    }
                });

                const restoredSearch = params.get('ganttSearch') || '';
                const searchInput = document.getElementById('ganttSearch');
                if (searchInput) searchInput.value = restoredSearch;

                const restoredView = params.get('ganttView');
                const restoredDateRaw = params.get('ganttDate');
                const restoredDate = parseIsoDate(restoredDateRaw) || new Date();
                if (restoredView && ['day', 'week', 'month'].includes(restoredView)) {
                    applyGanttViewState(restoredView, restoredDate);
                }

                if (window.jQuery && formEl) {
                    window.jQuery(formEl).find('select').trigger('change');
                }

                return {
                    filters: restoredFilters,
                    search: restoredSearch
                };
            }

            window.addEventListener('resize', debounce(() => {
                if (allShiftsData && allShiftsData.length) renderCurrentView();
            }, 150));

            // Checkbox spacing is now handled by flex layout; no padding override needed.
            function getBarLeftPadding() {
                return '';
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

                // Show/hide bulk unassign button based on selection mode
                $('#bulkUnassignBtn').toggle(selectionMode);
                $('#bulkUnassignBtn').toggleClass('d-none', !selectionMode);
                $('#bulkUnassignBtn').css('display', selectionMode ? 'inline-block' : 'none');

                // Adjust padding immediately for all bars so content isn't pushed far to the right
                $('#ganttChart .gantt-bar').css('padding-left', getBarLeftPadding());

                if (selectionMode) {
                    setTimeout(() => {
                        const firstCb = document.querySelector('.multi-shift-checkbox');
                        if (firstCb) firstCb.focus();
                    }, 80);
                }
            });

        // Bulk unassign selected shifts
        $('#bulkUnassignBtn').on('click', function() {
            if (selectedShiftIds.size === 0) {
                showToast('Please select at least one shift to unassign', 'error', 3000);
                return;
            }

            if (!confirm(`Are you sure you want to unassign ${selectedShiftIds.size} selected shift(s)?`)) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="ti ti-loader"></i> Unassigning...');

            $.ajax({
                url: `${baseUrl}/shifts/bulkUnassign`,
                method: 'POST',
                data: {
                    shift_ids: Array.from(selectedShiftIds),
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast(response.message || `${selectedShiftIds.size} shifts unassigned successfully`, 'success', 5000);
                    selectedShiftIds.clear();
                    $('.multi-shift-checkbox').prop('checked', false);
                    $btn.hide();
                    if (window.loadAllShiftsData) {
                        window.loadAllShiftsData();
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'Error unassigning shifts', 'error', 5000);
                    $btn.prop('disabled', false).html('<i class="ti ti-user-off"></i> Unassign Selected');
                }
            });
        });

            $('#todayBtn').on('click', function() {
                const today = new Date();
                applyGanttViewState(ganttView, today);
                renderCurrentView();
                persistCurrentGanttViewState();
            });

            $('#prevWeekBtn').on('click', function() {
                if (ganttView === 'day') {
                    // move one calendar day and normalise to start/end of that day
                    currentWeekStart.setDate(currentWeekStart.getDate() - 1);
                    currentWeekStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(),
                        currentWeekStart.getDate());
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
                persistCurrentGanttViewState();
            });

            $('#nextWeekBtn').on('click', function() {
                if (ganttView === 'day') {
                    // move one calendar day and normalise to start/end of that day
                    currentWeekStart.setDate(currentWeekStart.getDate() + 1);
                    currentWeekStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(),
                        currentWeekStart.getDate());
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
                persistCurrentGanttViewState();
            });

            $('#viewDayBtn').on('click', function() {
                applyGanttViewState('day', new Date());
                renderCurrentView();
                persistCurrentGanttViewState();
            });
            $('#viewWeekBtn').on('click', function() {
                applyGanttViewState('week', new Date());
                renderCurrentView();
                persistCurrentGanttViewState();
            });
            $('#viewMonthBtn').on('click', function() {
                applyGanttViewState('month', new Date());
                renderCurrentView();
                persistCurrentGanttViewState();
            });

            $('#ganttSearchBtn').on('click', function() {
                const searchTerm = $('#ganttSearch').val();
                filterGanttChart(searchTerm);
                syncSchedulingStateToUrl(window._ganttCurrentFilters || {}, {
                    ganttSearch: searchTerm
                });
            });

            // Auto-search while typing with debounce to avoid excessive filtering/network calls
            try {
                $('#ganttSearch').on('input', debounce(function() {
                    const searchTerm = $(this).val();
                    filterGanttChart(searchTerm);
                    syncSchedulingStateToUrl(window._ganttCurrentFilters || {}, {
                        ganttSearch: searchTerm
                    });
                }, 350));
            } catch (err) {
                // Fallback for older browsers: still support Enter key
                $('#ganttSearch').on('keyup', function(e) {
                    if (e.key === 'Enter') {
                        const searchTerm = $(this).val();
                        filterGanttChart(searchTerm);
                        syncSchedulingStateToUrl(window._ganttCurrentFilters || {}, {
                            ganttSearch: searchTerm
                        });
                    }
                });
            }

            // ── Delegated Gantt bar event handlers ────────────────────────────────
            // Set up ONCE here instead of binding per-bar inside renderGanttChart().
            // This eliminates 5+ listener attachments × N bars on every render.
            (function setupGanttDelegation() {
                const $chart = $('#ganttChart');

                // Bar click: open shift view or toggle multi-select
                $chart.on('click', '.gantt-bar', function(e) {
                    if ($(e.target).closest('.multi-shift-checkbox, .note-icon, .view-note-icon, .edit-shift-icon').length) return;
                    const shiftIdLocal = $(this).data('shift-id');
                    if (selectionMode) {
                        const cbLocal = $(this).find('.multi-shift-checkbox');
                        cbLocal.prop('checked', !cbLocal.prop('checked')).trigger('change');
                        e.stopPropagation();
                        return;
                    }
                    if (shiftIdLocal) {
                        const from = encodeURIComponent(window.location.pathname + window.location.search);
                        window.open(`${baseUrl}/shift-dates/${shiftIdLocal}/view?from=${from}`, '_blank');
                    }
                });

                // Checkbox click: prevent bubbling to bar click
                $chart.on('click', '.multi-shift-checkbox', function(e) { e.stopPropagation(); });

                // Checkbox change: update selection Set and visual state
                $chart.on('change', '.multi-shift-checkbox', function() {
                    const idLocal = String($(this).data('id'));
                    const theBar  = $(this).closest('.gantt-bar');
                    if (this.checked) { selectedShiftIds.add(idLocal); theBar.addClass('selected'); }
                    else              { selectedShiftIds.delete(idLocal); theBar.removeClass('selected'); }
                });

                // Edit-shift icon: open and populate edit modal
                $chart.on('click', '.edit-shift-icon', function(e) {
                    e.stopPropagation();
                    const sid = $(this).data('shift-id');
                    $('#shift_id').val(sid);
                    try { $('#edit_shift-form')[0].reset(); } catch (err) {}
                    const editUrls = [
                        `${baseUrl}/editshift/${sid}`,
                        `${baseUrl}/shift-dates/${sid}/edit`,
                        `${baseUrl}/shifts/${sid}`
                    ];
                    try {
                        if ($('#edit_shift .modal-spinner').length === 0) {
                            $('#edit_shift .modal-content').append(
                                '<div class="modal-spinner" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.8);z-index:1051;"><div class="text-center"><div class="spinner-border" role="status"></div><div class="mt-2">Loading...</div></div></div>'
                            );
                        }
                        $('#edit_shift').modal('show');
                    } catch (e2) {}
                    const populate = function(data) {
                        try {
                            if (data.shift_date)    $('#shift_date').val(data.shift_date);
                            if (data.start_time)    $('#start_shift').val(data.start_time);
                            if (data.end_time)      $('#end_shift').val(data.end_time);
                            if (data.guard_rate)    $('#guard_rate').val(data.guard_rate);
                            if (data.book_on)       $('#book_on').val(data.book_on);
                            if (data.book_off)      $('#book_off').val(data.book_off);
                            if (typeof data.status_id !== 'undefined') $('#status_id').val(data.status_id);
                            if (typeof data.staff_id  !== 'undefined') $('#staff_id').val(data.staff_id).trigger('change');
                            if (typeof data.subcontractor_id !== 'undefined') $('#subcontractor').val(data.subcontractor_id).trigger('change');
                        } catch (err) { console.debug(err); }
                        try { $('#edit_shift .modal-spinner').remove(); } catch (e2) {}
                        $('#edit_shift').modal('show');
                    };
                    (function tryNext(i) {
                        if (i >= editUrls.length) {
                            try { $('#edit_shift .modal-spinner').remove(); } catch (e2) {}
                            $('#edit_shift').modal('show');
                            return;
                        }
                        $.get(editUrls[i]).done(function(resp) {
                            if (resp && typeof resp === 'object') populate(resp);
                            else if (typeof resp === 'string' && resp.indexOf('<form') !== -1) {
                                try { $('#edit_shift').replaceWith(resp); } catch (e2) {}
                                try { $('#edit_shift .modal-spinner').remove(); } catch (e2) {}
                                $('#edit_shift').modal('show');
                            } else {
                                try { populate(JSON.parse(resp)); } catch (e2) {
                                    try { $('#edit_shift .modal-spinner').remove(); } catch (er) {}
                                    $('#edit_shift').modal('show');
                                }
                            }
                        }).fail(function() { tryNext(i + 1); });
                    })(0);
                });

                // Note icon: open add-note modal
                $chart.on('click', '.note-icon', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const shiftIdLocal = $(this).data('shift-id');
                    $('#shiftId').val(shiftIdLocal);
                    $('#noteForm')[0].reset();
                    $('#noteType').val('guard');
                    $('#noteText').val('');
                    $('#noteModal').modal('show');
                });

                // View-note icon: open the full notes thread for this shift
                $chart.on('click', '.view-note-icon', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const shiftIdLocal = $(this).data('shift-id');
                    if (typeof window.openNotesThread === 'function') {
                        window.openNotesThread(shiftIdLocal);
                    }
                });
            })();

            // Returns true when the currently-visible view window falls outside
            // the date range that was fetched from the server on the last load.
            function _viewOutsideLoadedRange() {
                if (!window._loadedFrom || !window._loadedTo) return true;
                const vs = new Date(currentWeekStart); vs.setHours(0, 0, 0, 0);
                const ve = new Date(currentWeekEnd);   ve.setHours(23, 59, 59, 0);
                return vs < window._loadedFrom || ve > window._loadedTo;
            }

            function loadAllShiftsData(currentFilters = null) {
                // Use persisted filters when caller doesn't provide any
                const filtersToUse = currentFilters !== null ? currentFilters : (window._ganttCurrentFilters || {});

                // Build a date-scoped request so the server only returns the rows the
                // current view actually needs.  Fetch the visible window ± 4-week buffer
                // so the user can navigate several weeks without triggering a re-fetch.
                // Explicit from_shift / to_shift filters from the user always take precedence.
                const requestData = Object.assign({}, filtersToUse);
                if (!requestData.from_shift && !requestData.to_shift) {
                    const buf = 28; // 4-week buffer
                    const dFrom = new Date(currentWeekStart);
                    dFrom.setDate(dFrom.getDate() - buf);
                    const dTo = new Date(currentWeekEnd);
                    dTo.setDate(dTo.getDate() + buf);
                    requestData.from_shift = formatDate(dFrom);
                    requestData.to_shift   = formatDate(dTo);
                }

                // Track the loaded range so _viewOutsideLoadedRange() can check it.
                window._loadedFrom = requestData.from_shift ? new Date(requestData.from_shift) : null;
                window._loadedTo   = requestData.to_shift   ? new Date(requestData.to_shift)   : null;

                $('#ganttChart').html(
                    '<div class="text-center p-5"><div class="spinner-border" role="status"></div><p class="mt-2">Loading shifts...</p></div>'
                );
                $.ajax({
                    url: `${baseUrl}/api/shifts`,
                    method: 'GET',
                    data: requestData,
                    // Never serve a cached/304 response for the shift list. Browsers and
                    // proxies were occasionally returning a stale (sometimes empty) copy,
                    // which made shifts appear only "sometimes" after a refresh. cache:false
                    // appends a unique _ param and the no-cache headers force a fresh fetch.
                    cache: false,
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache'
                    },
                    success: function(response) {
                        // normalize payload: some endpoints return { data: [...] } others return array directly
                        const payload = response.data || response.shift_dates || response || [];

                        // Normalize payload so frontend filtering can rely on stable top-level keys.
                        allShiftsData = (payload || []).map(s => {
                            const out = Object.assign({}, s);

                            // client id: prefer top-level, then embedded shift.client_id, then shift.site.client_id
                            out.client_id = out.client_id || out.clientId || (out.shift && out
                                    .shift.client_id) || (out.shift && out.shift.client && out
                                    .shift.client.id) || (out.shift && out.shift.site && out
                                    .shift.site.client_id) || (out.client && out.client.id) ||
                                out.client || null;

                            // site id/name: prefer top-level, then embedded shift.site_id / shift.site
                            out.site_id = out.site_id || out.siteId || (out.shift && out.shift
                                .site_id) || (out.shift && out.shift.site && out.shift.site
                                .id) || (out.site && out.site.id) || out.site || null;
                            out.site_name = out.site_name || out.siteName || (out.shift && out
                                .shift.site && (out.shift.site.site_name || out.shift.site
                                    .name)) || out.site_name || out.siteName || null;

                            // start date: prefer several possible names
                            out.start_date = out.start_date || out.shift_date || out
                                .shiftStart || out.startDate || (out.shift && out.shift
                                    .shift_date) || (out.shift && out.shift.start_date) || null;

                            // client display name
                            out.client_name = out.client_name || out.clientName || (out.shift &&
                                out.shift.client_name) || out.client_name || null;

                            return out;
                        });
                        // keep global copy in sync
                        window.allShiftsData = allShiftsData;
                        const activeFilters = window._ganttCurrentFilters || {};
                        const filteredShifts = applyFiltersToShifts(allShiftsData, activeFilters);
                        renderCurrentView(filteredShifts, activeFilters);
                    },
                    error: function(xhr) {
                        $('#ganttChart').html(
                            '<div class="gantt-empty">Error loading data. Please try again.</div>');
                        console.error('Error loading Gantt data:', xhr);
                    }
                });
            }

            window.loadAllShiftsData = function(currentFilters = null) {
                return loadAllShiftsData(currentFilters);
            };
            window.loadAllShiftsData.__isStub = false;

            function renderCurrentView(filteredData = null, filters = null) {
                // If the view window has moved outside the loaded date range and the
                // caller hasn't supplied pre-filtered data, re-fetch for the new window.
                // The fetch success callback will call renderCurrentView again with data.
                if (filteredData === null && _viewOutsideLoadedRange()) {
                    loadAllShiftsData(window._ganttCurrentFilters || {});
                    return;
                }

                if (!allShiftsData || allShiftsData.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found.</div>');
                    return;
                }

                const activeFilters = (filters !== null && typeof filters === 'object') ? filters : (window
                    ._ganttCurrentFilters || {});
                const shiftsToRender = filteredData || applyFiltersToShifts(allShiftsData, activeFilters);

                if (shiftsToRender.length === 0) {
                    $('#ganttChart').html('<div class="gantt-empty">No shifts found for this selection.</div>');
                    return;
                }

                let startDate, endDate;
                if (activeFilters.from_shift || activeFilters.to_shift) {
                    startDate = activeFilters.from_shift ? new Date(activeFilters.from_shift) : new Date(Math.min(
                        ...
                        shiftsToRender.map(s => new Date(s.start_date))));
                    endDate = activeFilters.to_shift ? new Date(activeFilters.to_shift) : new Date(Math.max(...
                        shiftsToRender
                        .map(s => new Date(s.start_date))));
                } else {
                    if (ganttView === 'day') {
                        // ensure full-day range so shifts with 00:00 timestamps are included
                        startDate = new Date(currentWeekStart);
                        startDate.setHours(0, 0, 0, 0);
                        endDate = new Date(currentWeekStart);
                        endDate.setHours(23, 59, 59, 999);
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

            window.removeDeletedShiftsFromGantt = function(ids) {
                const idsToRemove = Array.isArray(ids) ? ids.map(id => String(id)) : [];
                if (!idsToRemove.length || !Array.isArray(allShiftsData)) return false;

                const nextShifts = allShiftsData.filter(shift => {
                    const shiftId = shift && (shift.id || shift.shift_id || shift.shiftId);
                    return !idsToRemove.includes(String(shiftId));
                });

                if (nextShifts.length === allShiftsData.length) return false;

                allShiftsData = nextShifts;
                window.allShiftsData = allShiftsData;

                const activeFilters = window._ganttCurrentFilters || {};
                const filteredShifts = applyFiltersToShifts(allShiftsData, activeFilters);
                renderCurrentView(filteredShifts, activeFilters);
                return true;
            };

            // Persist & apply subcontractor visibility across renders/tab switches/zoom
            function applySubcontractorVisibility(forceState) {
                try {
                    var stored = localStorage.getItem('gantt_subs_visible');
                    var show = typeof forceState !== 'undefined' ? !!forceState : (stored ? JSON.parse(stored) : false);
                    var $btn = $('#toggle-subcontractors-all');
                    if ($btn && $btn.length) {
                        $btn.data('subs-visible', show);
                        $btn.text(show ? 'Hide Subcontractors' : 'Show Subcontractors');
                    }

                    $('.gantt-bar').each(function() {
                        var $bar = $(this);
                        var orig = $bar.attr('data-orig-staff') || '';
                        var sub = $bar.attr('data-sub-name') || '';
                        if (!sub) {
                            var sid = $bar.attr('data-sub-id');
                            if (sid && window._subcontractorMap && window._subcontractorMap[sid]) sub = window._subcontractorMap[sid];
                        }

                        if (!sub) {
                            // nothing to show for this bar
                            try { $bar.find('.staff-name').first().html(escapeHtml(orig || '')); } catch (e) {}
                            $bar.find('.subcontractor-name').hide();
                            return;
                        }

                        var cleanSub = String(sub).replace(/^\(|\)$/g, '').trim();
                        var $staff = $bar.find('.staff-name').first();

                        if (show) {
                            // Only show subcontractor when a staff is actually assigned (orig != 'Not Assigned')
                            var origNorm = (orig || '').toString().trim();
                            if (!origNorm || /^not\s*assigned$/i.test(origNorm)) {
                                try { $staff.html(escapeHtml(orig || '')); } catch (e) {}
                                $bar.find('.subcontractor-name').hide();
                                return;
                            }

                            try {
                                $staff.html(escapeHtml(orig || '') + ' <span class="subcontractor-inline">(' + escapeHtml(cleanSub) + ')</span>');
                            } catch (e) {
                                // fall back to safe text
                                $staff.text((orig || '') + ' (' + cleanSub + ')');
                            }
                            $bar.find('.subcontractor-name').hide();
                        } else {
                            try { $staff.html(escapeHtml(orig || '')); } catch (e) { $staff.text(orig || ''); }
                            $bar.find('.subcontractor-name').hide();
                        }
                    });
                } catch (e) {
                    console.debug('applySubcontractorVisibility failed', e);
                }
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
                    $('#ganttChart').html(
                        '<div class="alert alert-info text-center">No shifts found in the selected date range.</div>'
                    );
                    return;
                }

                // After grouping shifts into sites, order by client so clients with the
                // nearest upcoming shifts appear first. Within each client, sites are
                // ordered by their nearest shift and shifts are ordered by start time.
                function parseShiftDateTime(shift) {
                    const datePart = shift.start_date || shift.shift_date || shift.shiftDate || '';
                    const timePart = shift.start_time || shift.startTime || shift.start || '00:00';
                    if (!datePart) return Infinity;
                    let d = String(datePart);
                    // Normalize MM-DD-YYYY -> YYYY-MM-DD if necessary
                    const m = d.match(/^(\d{2})-(\d{2})-(\d{4})$/);
                    if (m) d = `${m[3]}-${m[1]}-${m[2]}`;
                    const dt = new Date(d + 'T' + String(timePart).slice(0, 5));
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

                // Pause the MutationObserver's layout recalc while we populate cells.
                window._ganttRendering = true;
                $('#ganttChart').html(headerHtml + bodyHtml);

                $('.gantt-container').toggleClass('selection-mode', selectionMode);

                $('#toggle-subcontractors-all').off('click').on('click', function() {
                    const $btn = $(this);
                    const currentlyVisible = $btn.data('subs-visible') === true;
                    const newState = !currentlyVisible;
                    try { localStorage.setItem('gantt_subs_visible', JSON.stringify(newState)); } catch (e) {}
                    applySubcontractorVisibility(newState);
                });

                // Place shifts into day cells using HTML string concatenation + innerHTML.
                // This avoids jQuery DOM parsing and per-bar event listener attachment
                // (events are handled by the delegated handlers set up above).
                filteredOrderedSites.forEach(site => {
                    const shiftsByDate = {};
                    site.shifts.forEach(shift => {
                        const dateStr = formatDate(new Date(shift.start_date));
                        if (!shiftsByDate[dateStr]) shiftsByDate[dateStr] = [];
                        shiftsByDate[dateStr].push(shift);
                    });

                    Object.entries(shiftsByDate).forEach(([dateStr, shifts]) => {
                        const cellEl = document.getElementById(`cell-${site.id}-${dateStr}`);
                        if (!cellEl) return;

                        let cellHtml = '';
                        shifts.forEach((shift) => {
                            const displayStaff = shift.staff_name_clean || shift.staff_name || 'Not Assigned';
                            const subcontractorId   = shift.subcontractor_id || null;
                            const subcontractorName = shift.subcontractor_name ||
                                (subcontractorId && window._subcontractorMap && window._subcontractorMap[subcontractorId]
                                    ? window._subcontractorMap[subcontractorId] : '');
                            const idStr     = String(shift.id);
                            const isSelected = selectedShiftIds.has(idStr);

                            cellHtml += `<div class="gantt-bar shift-${shift.color_class || ''}${isSelected ? ' selected' : ''}" data-shift-id="${idStr}" data-orig-staff="${escapeHtml(displayStaff)}"${subcontractorName ? ` data-sub-name="${escapeHtml(subcontractorName)}"` : ''}${subcontractorId ? ` data-sub-id="${escapeHtml(String(subcontractorId))}"` : ''} title="${escapeHtml(shift.title || '')} (${escapeHtml(shift.formatted_time || '')}) - ${escapeHtml(displayStaff)}">
                                <input type="checkbox" class="multi-shift-checkbox"${isSelected ? ' checked' : ''} data-id="${idStr}" aria-label="Select shift ${idStr}">
                                <div class="bar-content">
                                    ${shift.service_type ? `<div class="service-type">${escapeHtml(shift.service_type)}</div>` : ''}
                                    <div class="time-text">
                                        <span>${escapeHtml(shift.formatted_time || '')}</span>
                                        <div class="bar-actions">
                                            ${shift.note
                                                ? `<span class="view-note-icon" data-shift-id="${idStr}" title="View note"><i class="fa-solid fa-note-sticky"></i></span>`
                                                : `<span class="note-icon" data-shift-id="${idStr}" title="Add note"><i class="fa-solid fa-square-plus"></i></span>`}
                                            <span class="edit-shift-icon" data-shift-id="${idStr}" title="Edit shift"><i class="fa-solid fa-pen-to-square"></i></span>
                                        </div>
                                    </div>
                                    <div class="staff-name">${escapeHtml(displayStaff)}</div>
                                    ${subcontractorName ? `<div class="subcontractor-name" style="display:none;">${escapeHtml(subcontractorName)}</div>` : ''}
                                </div>
                            </div>`;
                        });
                        cellEl.innerHTML = cellHtml;
                    });
                });

                // Rendering complete — re-enable the MutationObserver and run
                // adjustGanttDayCellColumns() exactly once instead of once per bar.
                window._ganttRendering = false;
                try { adjustGanttDayCellColumns(); } catch (e) {}
                try { applySubcontractorVisibility(); } catch (e) {}

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

                    const minDayWidth = 110;
                    const daysToFitOnScreen = (ganttView === 'month') ? Math.min(totalDays, 10) : totalDays;
                    let dayWidth = Math.floor(timelineAvailableWidth / Math.max(1, daysToFitOnScreen));
                    if (dayWidth < minDayWidth) dayWidth = minDayWidth;

                    // Sync sidebar row widths to the header sidebar width
                    const rowSidebars = ganttChartEl.querySelectorAll('.gantt-row-sidebar');
                    rowSidebars.forEach(sb => {
                        sb.style.width = (sidebarHeaders[0] ? sidebarHeaders[0].getBoundingClientRect()
                            .width + 'px' : '160px');
                        sb.style.minWidth = (sidebarHeaders[0] ? sidebarHeaders[0]
                            .getBoundingClientRect().width + 'px' : '160px');
                        sb.style.boxSizing = 'border-box';
                    });

                    // Column/header widths are driven by content via adjustGanttDayCellColumns().
                    // Call it now so header and columns align after initial render.
                    if (typeof adjustGanttDayCellColumns === 'function') adjustGanttDayCellColumns();

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

                const term = String(searchTerm).toLowerCase();
                $('.gantt-row').each(function() {
                    const row = $(this);
                    const siteText = row.find('.gantt-row-sidebar').text().toLowerCase();
                    const shiftBars = row.find('.gantt-bar');
                    let anyVisible = false;

                    // Check if site/client name matches
                    const siteMatches = siteText.includes(term);

                    // Show/hide individual shift bars based on search
                    shiftBars.each(function() {
                        const $bar = $(this);
                        const orig = $bar.attr('data-orig-staff') || '';
                        let sub = $bar.attr('data-sub-name') || '';
                        // if name missing but id present, try client-side map
                        if (!sub) {
                            const sid = $bar.attr('data-sub-id');
                            if (sid && window._subcontractorMap && window._subcontractorMap[sid])
                                sub = window._subcontractorMap[sid];
                        }

                        // Build searchable text from bar contents and attributes
                        const barContentText = ($bar.find('.bar-content').text() || '').trim();
                        const barTitle = ($bar.attr('title') || '').trim();
                        const combined = (orig + ' ' + sub + ' ' + barContentText + ' ' + barTitle)
                            .toLowerCase();

                        const staffMatch = combined.includes(term);

                        if (siteMatches || staffMatch) {
                            $bar.show();
                            anyVisible = true;
                        } else {
                            $bar.hide();
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
            const restoredState = restoreSchedulingStateFromUrl(shiftFilterFormEl);
            window._ganttCurrentFilters = restoredState.filters || {};

            if (shiftFilterFormEl) {
                shiftFilterFormEl.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const form = e.target;
                    const filters = collectActiveFiltersFromForm(form);

                    // Persist these filters so background reloads respect the user's selection
                    window._ganttCurrentFilters = filters;
                    syncSchedulingStateToUrl(filters, {
                        ganttSearch: $('#ganttSearch').val()
                    });

                    // When the user applies explicit date-range filters, re-fetch from
                    // the server so we get shifts that may be outside the current buffer.
                    if (filters.from_shift || filters.to_shift) {
                        loadAllShiftsData(filters);
                    } else {
                        const filteredShifts = applyFiltersToShifts(allShiftsData, filters);
                        renderCurrentView(filteredShifts, filters);
                    }
                    try {
                        bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
                    } catch (err) {}
                });

                const resetShiftFiltersBtn = document.getElementById('resetShiftFilters');
                if (resetShiftFiltersBtn) {
                    resetShiftFiltersBtn.addEventListener('click', function() {
                        shiftFilterFormEl.reset();

                        if (window.jQuery) {
                            window.jQuery(shiftFilterFormEl).find('select').val('').trigger('change');
                        }

                        window._ganttCurrentFilters = {};
                        $('#ganttSearch').val('');
                        syncSchedulingStateToUrl({}, {
                            ganttSearch: ''
                        });

                        loadAllShiftsData({});

                        try {
                            bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
                        } catch (err) {}
                    });
                }

            }

            if (!new URLSearchParams(window.location.search).get('ganttView')) {
                try {
                    setActiveGanttView('#viewWeekBtn');
                } catch (err) {}
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
            $('#deleteSelectedBtn').prop('hidden', !selectionMode);
            $('#deleteSelectedBtn').prop('disabled', selectedShiftIds.length === 0);
            $('#bulkUnassignBtn').prop('hidden', !selectionMode);

            // Show/hide checkboxes
            $('.multi-shift-checkbox').each(function() {
                $(this).css('display', selectionMode ? 'inline-block' : 'none');
                if (!selectionMode) this.checked = false;
            });

            // Reset selected IDs
            if (!selectionMode) {
                selectedShiftIds = [];
                $('#selectedShiftsCount').text(0);
                $('#deleteSelectedBtn').prop('disabled', true);
            }
        });

        // Track checkbox changes
        $(document).on('change', '.multi-shift-checkbox', function() {
            const shiftId = $(this).data('id');
            if (this.checked) selectedShiftIds.push(shiftId);
            else selectedShiftIds = selectedShiftIds.filter(id => id != shiftId);

            $('#selectedShiftsCount').text(selectedShiftIds.length);
            $('#deleteSelectedBtn').prop('disabled', selectedShiftIds.length === 0);
        });

        $('#deleteSelectedBtn').on('click', function() {
            const ids = [...new Set(selectedShiftIds.map(id => parseInt(id, 10)).filter(id => !Number.isNaN(id)))];

            if (ids.length === 0) {
                toast_danger('Please select at least one shift to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected shifts?')) return;

            const deleteButton = $(this);
            deleteButton.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: '{{ route('shifts.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: ids,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success(response?.message || 'Selected shifts deleted successfully!');

                    let updatedImmediately = false;
                    if (typeof window.removeDeletedShiftsFromGantt === 'function') {
                        updatedImmediately = window.removeDeletedShiftsFromGantt(ids);
                    }

                    selectedShiftIds = [];
                    $('#selectedShiftsCount').text(0);
                    selectionMode = false;
                    $('#enableSelectBtn').text('Multi Select');
                    $('#editSelectedBtn').prop('hidden', true);
                    $('#deleteSelectedBtn').prop('hidden', true);
                    $('.multi-shift-checkbox').prop('checked', false).css('display', 'none');

                    if (!updatedImmediately && window.loadAllShiftsData && typeof window.loadAllShiftsData === 'function') {
                        window.loadAllShiftsData(window._ganttCurrentFilters || {});
                    }
                },
                error: function(xhr) {
                    const msg = xhr?.responseJSON?.message ||
                        'Something went wrong during bulk delete.';
                    toast_danger(msg);
                },
                complete: function() {
                    deleteButton.prop('disabled', selectedShiftIds.length === 0).text(
                        'Delete Selected');
                }
            });
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
            $('.subcontractor-select-filter').select2({
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

            $('.select2_modal').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#edit_shift'), // make sure this matches your modal ID
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
                        /* ignore */
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

        // Handle both add-note and view-note icons (global delegated handler).
        $(document).on('click', '.note-icon, .view-note-icon', function(e) {
            e.stopPropagation(); // Prevent the bar click from firing

            const shiftId = $(this).data('shift-id');
            if (!shiftId) return;

            if ($(this).hasClass('note-icon')) {
                // No notes yet -> open the simple "add first note" modal.
                $('#shiftId').val(shiftId);
                $('#noteForm')[0].reset();
                $('#noteType').val('guard');
                $('#noteText').val('');
                $('#noteModal').modal('show');
            } else if ($(this).hasClass('view-note-icon')) {
                // Notes exist -> open the full thread.
                if (typeof window.openNotesThread === 'function') {
                    window.openNotesThread(shiftId);
                }
            }
        });

        // ---------- Multiple notes per shift: thread view ----------

        // Render the list of notes (newest first) inside the view modal.
        function renderNotesThread(notes) {
            const $thread = $('#notesThread');
            $thread.empty();

            if (!notes || !notes.length) {
                $thread.html('<p class="notes-empty">No notes yet. Use “Add Note” to create one.</p>');
                return;
            }

            const escapeHtml = (s) => $('<div>').text(s == null ? '' : String(s)).html();

            notes.forEach(function(n) {
                const card = `
                    <div class="note-card" data-note-id="${n.id}">
                        <div class="note-meta">
                            <span>
                                <strong>${escapeHtml(n.author)}</strong>
                                · <span class="note-type-badge badge bg-secondary">${escapeHtml(n.note_type)}</span>
                            </span>
                            <span>${escapeHtml(n.created_at)}</span>
                        </div>
                        <div class="note-body">${escapeHtml(n.note)}</div>
                        <div class="note-actions">
                            <button type="button" class="btn btn-outline-primary btn-sm note-edit-btn"
                                data-note-id="${n.id}" data-note-type="${escapeHtml(n.note_type)}">Edit</button>
                            <button type="button" class="btn btn-outline-danger btn-sm note-delete-btn"
                                data-note-id="${n.id}">Delete</button>
                        </div>
                    </div>`;
                $thread.append(card);
            });

            // Stash the raw note bodies so Edit can prefill the textarea safely.
            $thread.find('.note-card').each(function(i) {
                $(this).find('.note-edit-btn').data('note-body', notes[i].note);
            });
        }

        // Fetch a shift's notes and open the thread modal.
        window.openNotesThread = function(shiftId) {
            $('#viewNoteShiftId').val(shiftId);
            $('#addNoteInline').hide();
            $('#notesThread').html('<p class="notes-empty">Loading…</p>');
            $('#viewNoteModal').modal('show');

            $.get(`/shift-dates/${shiftId}/note`)
                .done(function(data) {
                    renderNotesThread(data && data.notes ? data.notes : []);
                })
                .fail(function() {
                    $('#notesThread').html('<p class="text-danger">Failed to load notes.</p>');
                });
        };

        // Reload just the thread (after add/edit/delete) without closing the modal.
        function reloadNotesThread() {
            const shiftId = $('#viewNoteShiftId').val();
            if (!shiftId) return;
            $.get(`/shift-dates/${shiftId}/note`)
                .done(function(data) {
                    renderNotesThread(data && data.notes ? data.notes : []);
                    // Keep the gantt icon in sync with whether any notes remain.
                    const hasNotes = !!(data && data.count);
                    if (typeof refreshShiftBar === 'function') {
                        refreshShiftBar(shiftId, hasNotes ? true : null);
                    }
                });
        }

        // Show the inline add-note form (in "add" mode).
        $(document).on('click', '#addNewNoteBtn', function() {
            $('#inlineNoteId').val('');
            $('#inlineNoteType').val('guard');
            $('#inlineNoteText').val('');
            $('#addNoteInlineTitle').text('Add a new note');
            $('#addNoteInline').show();
            $('#inlineNoteText').focus();
        });

        // Edit an existing note: load it into the inline form (in "edit" mode).
        $(document).on('click', '.note-edit-btn', function() {
            $('#inlineNoteId').val($(this).data('note-id'));
            $('#inlineNoteType').val($(this).data('note-type') || 'guard');
            $('#inlineNoteText').val($(this).data('note-body') || '');
            $('#addNoteInlineTitle').text('Edit note');
            $('#addNoteInline').show();
            $('#inlineNoteText').focus();
        });

        // Cancel the inline add/edit form.
        $(document).on('click', '#inlineNoteCancelBtn', function() {
            $('#addNoteInline').hide();
        });

        // Save the inline form — POST a new note or PUT an existing one.
        $(document).on('click', '#inlineNoteSaveBtn', function() {
            const shiftId = $('#viewNoteShiftId').val();
            const noteId = $('#inlineNoteId').val();
            const noteType = $('#inlineNoteType').val();
            const noteText = $('#inlineNoteText').val().trim();
            const token = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

            if (!noteText) {
                showToast('Please enter a note.', 'error', 4000);
                return;
            }

            const isEdit = !!noteId;
            const ajaxOpts = isEdit ? {
                url: `/shift-notes/${noteId}`,
                type: 'PUT'
            } : {
                url: `/shift-dates/${shiftId}/note`,
                type: 'POST'
            };

            $.ajax({
                url: ajaxOpts.url,
                type: ajaxOpts.type,
                data: {
                    _token: token,
                    note_type: noteType,
                    note: noteText
                },
                success: function() {
                    $('#addNoteInline').hide();
                    showToast(isEdit ? 'Note updated!' : 'Note added!', 'success', 4000);
                    reloadNotesThread();
                },
                error: function(xhr) {
                    let msg = isEdit ? 'Error updating note' : 'Error adding note';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    showToast(msg, 'error', 5000);
                }
            });
        });

        // Delete a single note from the thread.
        $(document).on('click', '.note-delete-btn', function() {
            const noteId = $(this).data('note-id');
            if (!noteId) return;
            if (!confirm('Delete this note?')) return;
            const token = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

            $.ajax({
                url: `/shift-notes/${noteId}`,
                type: 'DELETE',
                data: {
                    _token: token
                },
                success: function() {
                    showToast('Note deleted!', 'success', 4000);
                    reloadNotesThread();
                },
                error: function() {
                    showToast('Error deleting note', 'error', 5000);
                }
            });
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
                    showToast('Success on saving note!', 'success', 5000);

                    // Flip the bar icon to "has notes" immediately, then reload the
                    // chart so it re-renders from authoritative data.
                    if (typeof refreshShiftBar === 'function') refreshShiftBar(shiftId, res.note);
                    if (window.loadAllShiftsData && typeof window.loadAllShiftsData === 'function') {
                        try {
                            window.loadAllShiftsData();
                        } catch (e) {
                            console.debug('window.loadAllShiftsData failed', e);
                        }
                    }
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

        // ----- Polling for recent notes (near-real-time) -----
        (function() {
            let lastNoteId = 0;

            // initialize lastNoteId to the highest note id currently present in the DOM (if any)
            function initLastNoteId() {
                try {
                    // check any existing note data attributes on bars
                    const ids = [];
                    $('.gantt-bar').each(function() {
                        const nid = $(this).data('note-id');
                        if (nid) ids.push(parseInt(nid, 10));
                    });
                    if (ids.length) lastNoteId = Math.max(...ids);
                } catch (e) {
                    lastNoteId = 0;
                }
            }

            function pollNotes() {
                $.get('/shift-dates/notes/updates', {
                        after: lastNoteId
                    })
                    .done(function(res) {
                        if (!res || !res.notes || !res.notes.length) return;
                        res.notes.forEach(function(note) {
                            // Update the UI for the affected shift_date
                            try {
                                if (typeof refreshShiftBar === 'function') {
                                    // pass full note object so refreshShiftBar can use note.id when available
                                    refreshShiftBar(note.shift_date_id, note);
                                }
                                // also set data-note-id on any existing bar
                                const $bar = $(`.gantt-bar[data-shift-id="${note.shift_date_id}"]`);
                                if ($bar && $bar.length && note.id) $bar.attr('data-note-id', note.id);
                            } catch (e) {}
                            lastNoteId = Math.max(lastNoteId, note.id);
                        });
                    })
                    .fail(function() {
                        // ignore transient failures
                    });
            }

            $(document).ready(function() {
                initLastNoteId();

                // Visibility-aware polling with exponential backoff
                let baseInterval = 3000; // 3s
                const maxInterval = 30000; // 30s
                let currentInterval = baseInterval;

                function scheduleNextPoll() {
                    // If page not visible, pause polling and retry later with a small interval
                    if (document.hidden) {
                        setTimeout(scheduleNextPoll, 5000);
                        return;
                    }

                    // Execute poll
                    const jq = pollNotes();
                    if (jq && typeof jq.done === 'function') {
                        jq.done(function() {
                            // success -> reset interval
                            currentInterval = baseInterval;
                        }).fail(function() {
                            // failure -> exponential backoff
                            currentInterval = Math.min(maxInterval, Math.max(currentInterval * 2,
                                baseInterval));
                        }).always(function() {
                            setTimeout(scheduleNextPoll, currentInterval);
                        });
                    } else {
                        // fallback: schedule again
                        setTimeout(scheduleNextPoll, currentInterval);
                    }
                }

                // Start polling loop
                scheduleNextPoll();

                // If user hides/returns to tab, adjust polling promptly
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        // resume immediately when tab becomes visible
                        scheduleNextPoll();
                    }
                });
            });
        })();

        // (Note delete is handled per-note by .note-delete-btn in the thread view.)

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
                        // Ensure a view-note-icon exists and handlers are bound correctly
                        if (noteIcon.length) {
                            noteIcon.off('click').removeClass('note-icon').addClass('view-note-icon').attr(
                                'title', 'View note').html('<i class="fa-solid fa-note-sticky"></i>');
                            // bind view-note click -> open the notes thread
                            noteIcon.on('click', function(e) {
                                e.stopPropagation();
                                const sid = $(this).data('shift-id');
                                if (typeof window.openNotesThread === 'function') window.openNotesThread(sid);
                            });
                        }
                        // also set bar-level data attribute if note id present
                        try {
                            const $bar = $(`.gantt-bar[data-shift-id="${idStr}"]`);
                            if ($bar && $bar.length && noteData.id) $bar.attr('data-note-id', noteData.id);
                            // ensure edit icon exists when note state updates
                            if ($bar && $bar.length && $bar.find('.edit-shift-icon').length === 0) {
                                const $editIcon = $(`
                                        <span class="edit-shift-icon" data-shift-id="${idStr}" title="Edit shift">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="currentColor"/>
                                                <path d="M20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    `);
                                const $actions = $bar.find('.bar-actions').first();
                                if ($actions.length) $actions.append($editIcon);
                                else $bar.find('.time-text').first().append($('<div class="bar-actions"></div>').append($editIcon));
                                $editIcon.on('click', function(e) {
                                    e.stopPropagation();
                                    const sid = $(this).data('shift-id');
                                    $('#shift_id').val(sid);
                                    try {
                                        $('#edit_shift-form')[0].reset();
                                    } catch (err) {}
                                    const editUrls = [
                                        `${baseUrl}/shifts/${sid}/edit`,
                                        `${baseUrl}/shift-dates/${sid}/edit`,
                                        `${baseUrl}/shifts/${sid}`
                                    ];
                                    const populate = function(data) {
                                        try {
                                            if (data.shift_date) $('#shift_date').val(data.shift_date);
                                            if (data.start_time) $('#start_shift').val(data.start_time);
                                            if (data.end_time) $('#end_shift').val(data.end_time);
                                            if (data.guard_rate) $('#guard_rate').val(data.guard_rate);
                                            if (data.book_on) $('#book_on').val(data.book_on);
                                            if (data.book_off) $('#book_off').val(data.book_off);
                                            if (typeof data.status_id !== 'undefined') $('#status_id').val(data
                                                .status_id);
                                            if (typeof data.staff_id !== 'undefined') $('#staff_id').val(data
                                                .staff_id).trigger('change');
                                            if (typeof data.subcontractor_id !== 'undefined') $(
                                                '#subcontractor').val(data.subcontractor_id).trigger(
                                                'change');
                                        } catch (err) {
                                            console.debug(err);
                                        }
                                        $('#edit_shift').modal('show');
                                    };
                                    (function tryNext(i) {
                                        if (i >= editUrls.length) {
                                            $('#edit_shift').modal('show');
                                            return;
                                        }
                                        $.get(editUrls[i]).done(function(resp) {
                                            if (resp && typeof resp === 'object') populate(resp);
                                            else if (typeof resp === 'string' && resp.indexOf(
                                                    '<form') !== -1) {
                                                try {
                                                    $('#edit_shift').replaceWith(resp);
                                                } catch (e) {}
                                                $('#edit_shift').modal('show');
                                            } else {
                                                try {
                                                    const parsed = JSON.parse(resp);
                                                    populate(parsed);
                                                } catch (e) {
                                                    $('#edit_shift').modal('show');
                                                }
                                            }
                                        }).fail(function() {
                                            tryNext(i + 1);
                                        });
                                    })(0);
                                });
                            }
                        } catch (e) {}

                    } else {
                        // No note -> show inactive note-icon and bind add-note handler
                        if (viewIcon.length) {
                            viewIcon.off('click').removeClass('view-note-icon').addClass('note-icon').attr(
                                'title', 'Add note').html('<i class="fa-solid fa-square-plus"></i>');
                            viewIcon.on('click', function(e) {
                                e.stopPropagation();
                                const sid = $(this).data('shift-id');
                                $('#shiftId').val(sid);
                                $('#noteForm')[0].reset();
                                $('#noteType').val('guard');
                                $('#noteText').val('');
                                $('#noteModal').modal('show');
                            });
                        }
                        if (noteIcon.length) {
                            noteIcon.off('click').attr('title', 'Add note').html(
                                '<i class="fa-solid fa-square-plus"></i>');
                            noteIcon.on('click', function(e) {
                                e.stopPropagation();
                                const sid = $(this).data('shift-id');
                                $('#shiftId').val(sid);
                                $('#noteForm')[0].reset();
                                $('#noteType').val('guard');
                                $('#noteText').val('');
                                $('#noteModal').modal('show');
                            });
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
                            if (noteIcon.length) noteIcon.removeClass('note-icon').addClass('view-note-icon').attr(
                                'title', 'View note').html('<i class="fa-solid fa-note-sticky"></i>');
                            if (viewIcon.length) viewIcon.attr('title', 'View note').html(
                                '<i class="fa-solid fa-note-sticky"></i>');
                        } else {
                            if (viewIcon.length) viewIcon.removeClass('view-note-icon').addClass('note-icon').attr(
                                'title', 'Add note').html('<i class="fa-solid fa-square-plus"></i>');
                            if (noteIcon.length) noteIcon.attr('title', 'Add note').html(
                                '<i class="fa-solid fa-square-plus"></i>');
                        }

                        // If no direct icon present (bar was re-rendered), attempt to find the bar element and inject/update the icon
                        const bar = $(`.gantt-bar[data-shift-id="${idStr}"]`);
                        if (bar.length && bar.find('.view-note-icon, .note-icon').length === 0) {
                            // create appropriate icon span
                            const iconClass = hasNote ? 'view-note-icon' : 'note-icon';
                            const iconHtml = hasNote ? '<i class="fa-solid fa-note-sticky"></i>' :
                                '<i class="fa-solid fa-square-plus"></i>';
                            const iconTitle = hasNote ? 'View note' : 'Add note';
                            const $icon = $(
                                `<span class="${iconClass}" data-shift-id="${idStr}" title="${iconTitle}">${iconHtml}</span>`);
                            // append to bar
                            const $actions = bar.find('.bar-actions').first();
                            if ($actions.length) $actions.prepend($icon);
                            else bar.find('.time-text').first().append($('<div class="bar-actions"></div>').append($icon));

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
                                    if (typeof window.openNotesThread === 'function') window.openNotesThread(sid);
                                }
                            });

                            // Ensure edit icon exists for this bar as well
                            if (bar.find('.edit-shift-icon').length === 0) {
                                const $editIcon = $(`
                                    <span class="edit-shift-icon" data-shift-id="${idStr}" title="Edit shift">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="currentColor"/>
                                            <path d="M20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                        </svg>
                                    </span>
                                `);
                                const $actions = bar.find('.bar-actions').first();
                                if ($actions.length) $actions.append($editIcon);
                                else bar.find('.time-text').first().append($('<div class="bar-actions"></div>').append($editIcon));

                                // bind edit handler (same logic as creation path)
                                $editIcon.on('click', function(e) {
                                    e.stopPropagation();
                                    const sid = $(this).data('shift-id');
                                    $('#shift_id').val(sid);
                                    try {
                                        $('#edit_shift-form')[0].reset();
                                    } catch (err) {}
                                    const editUrls = [
                                        `${baseUrl}/shifts/${sid}/edit`,
                                        `${baseUrl}/shift-dates/${sid}/edit`,
                                        `${baseUrl}/shifts/${sid}`
                                    ];
                                    const populate = function(data) {
                                        try {
                                            if (data.shift_date) $('#shift_date').val(data.shift_date);
                                            if (data.start_time) $('#start_shift').val(data.start_time);
                                            if (data.end_time) $('#end_shift').val(data.end_time);
                                            if (data.guard_rate) $('#guard_rate').val(data.guard_rate);
                                            if (data.book_on) $('#book_on').val(data.book_on);
                                            if (data.book_off) $('#book_off').val(data.book_off);
                                            if (typeof data.status_id !== 'undefined') $('#status_id').val(data
                                                .status_id);
                                            if (typeof data.staff_id !== 'undefined') $('#staff_id').val(data
                                                .staff_id).trigger('change');
                                            if (typeof data.subcontractor_id !== 'undefined') $(
                                                '#subcontractor').val(data.subcontractor_id).trigger(
                                                'change');
                                        } catch (err) {
                                            console.debug(err);
                                        }
                                        $('#edit_shift').modal('show');
                                    };
                                    (function tryNext(i) {
                                        if (i >= editUrls.length) {
                                            $('#edit_shift').modal('show');
                                            return;
                                        }
                                        $.get(editUrls[i]).done(function(resp) {
                                            if (resp && typeof resp === 'object') populate(resp);
                                            else if (typeof resp === 'string' && resp.indexOf(
                                                    '<form') !== -1) {
                                                try {
                                                    $('#edit_shift').replaceWith(resp);
                                                } catch (e) {}
                                                $('#edit_shift').modal('show');
                                            } else {
                                                try {
                                                    const parsed = JSON.parse(resp);
                                                    populate(parsed);
                                                } catch (e) {
                                                    $('#edit_shift').modal('show');
                                                }
                                            }
                                        }).fail(function() {
                                            tryNext(i + 1);
                                        });
                                    })(0);
                                });
                            }
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
                $.ajax({ url: `${baseUrl}/api/shifts`, method: 'GET', cache: false,
                    headers: { 'Cache-Control': 'no-cache, no-store, must-revalidate', 'Pragma': 'no-cache' }
                }).done(function(resp) {
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
                        if (noteIcon.length) noteIcon.removeClass('note-icon').addClass('view-note-icon').attr(
                            'title', 'View note').html('<i class="fa-solid fa-note-sticky"></i>');
                        if (viewIcon.length) viewIcon.attr('title', 'View note').html(
                            '<i class="fa-solid fa-note-sticky"></i>');
                    } else {
                        if (viewIcon.length) viewIcon.removeClass('view-note-icon').addClass('note-icon').attr(
                            'title', 'Add note').html('<i class="fa-solid fa-square-plus"></i>');
                        if (noteIcon.length) noteIcon.attr('title', 'Add note').html(
                            '<i class="fa-solid fa-square-plus"></i>');
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
            const backendStaffRaw2 = shift.staff_name_raw || shift.staff_name || '';
            const backendStaffClean2 = shift.staff_name_clean || shift.staff_name || '';
            const parenthesisedMatches2 = (shift.staff_name || backendStaffRaw2) ? ((shift.staff_name || backendStaffRaw2)
                .match(/\([^)]*\)/g)) : null;
            const parenthesisedTag2 = (parenthesisedMatches2 && parenthesisedMatches2.length) ? parenthesisedMatches2[0] :
                '';

            let displayStaff2 = backendStaffClean2 || '';
            if (!displayStaff2) {
                let tmp = backendStaffRaw2 || '';
                while (/\([^()]*\)/.test(tmp)) {
                    tmp = tmp.replace(/\s*\([^()]*\)/g, '');
                }
                displayStaff2 = tmp.replace(/\s+/g, ' ').trim();
            }

            const subcontractorId = shift.subcontractor_id || shift.subcontractorId || null;
            let subcontractorName2 = shift.subcontractor_name || shift.subcontractor || null;
            if (!subcontractorName2 && subcontractorId && window._subcontractorMap && window._subcontractorMap[
                    subcontractorId]) {
                subcontractorName2 = window._subcontractorMap[subcontractorId];
            }
            subcontractorName2 = subcontractorName2 || (subcontractorId ? parenthesisedTag2 : '');
            const hasNote = !!(shift.note || (shift.note && shift.note.note));

            const $bar = $(`
            <div class="gantt-bar shift-${shift.color_class || ''}" data-shift-id="${idStr}" title="${(shift.title || '')} - ${safeEscape(displayStaff2 || '')}">
    <input type="checkbox" class="multi-shift-checkbox" data-id="${idStr}" aria-label="Select shift ${idStr}">
    <div class="bar-content">
        ${shift.service_type ? `<div class="service-type">${safeEscape(shift.service_type)}</div>` : ''}
        <div class="time-text">
            <span>${safeEscape(shift.formatted_time || shift.start_time || '')}</span>
            <div class="bar-actions">
                ${hasNote ? `<span class="view-note-icon" data-shift-id="${idStr}" title="View note"><i class="fa-solid fa-note-sticky"></i></span>` : `<span class="note-icon" data-shift-id="${idStr}" title="Add note"><i class="fa-solid fa-square-plus"></i></span>`}
                <span class="edit-shift-icon" data-shift-id="${idStr}" title="Edit shift">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="currentColor"/>
                        <path d="M20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="duration-text">${safeEscape(shift.duration || '')}</div>
        <div class="staff-name">${safeEscape(displayStaff2)}</div>
        ${subcontractorName2 ? `<div class="subcontractor-name" style="display:none; font-weight:bold">${safeEscape(subcontractorName2)}</div>` : ''}
    </div>
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
                if (typeof window.openNotesThread === 'function') window.openNotesThread(sid);
            });

            // edit icon: open edit modal and populate
            $bar.find('.edit-shift-icon').on('click', function(e) {
                e.stopPropagation();
                const sid = $(this).data('shift-id');
                $('#shift_id').val(sid);
                try {
                    $('#edit_shift-form')[0].reset();
                } catch (err) {}

                const editUrls = [
                    `${baseUrl}/shifts/${sid}/edit`,
                    `${baseUrl}/shift-dates/${sid}/edit`,
                    `${baseUrl}/shifts/${sid}`
                ];

                const populate = function(data) {
                    try {
                        if (data.shift_date) $('#shift_date').val(data.shift_date);
                        if (data.start_time) $('#start_shift').val(data.start_time);
                        if (data.end_time) $('#end_shift').val(data.end_time);
                        if (data.guard_rate) $('#guard_rate').val(data.guard_rate);
                        if (data.book_on) $('#book_on').val(data.book_on);
                        if (data.book_off) $('#book_off').val(data.book_off);
                        if (typeof data.status_id !== 'undefined') $('#status_id').val(data.status_id);
                        if (typeof data.staff_id !== 'undefined') $('#staff_id').val(data.staff_id).trigger(
                            'change');
                        if (typeof data.subcontractor_id !== 'undefined') $('#subcontractor').val(data
                            .subcontractor_id).trigger('change');
                    } catch (err) {
                        console.debug(err);
                    }
                    $('#edit_shift').modal('show');
                };

                (function tryNext(i) {
                    if (i >= editUrls.length) {
                        $('#edit_shift').modal('show');
                        return;
                    }
                    $.get(editUrls[i]).done(function(resp) {
                        if (resp && typeof resp === 'object') populate(resp);
                        else if (typeof resp === 'string' && resp.indexOf('<form') !== -1) {
                            try {
                                $('#edit_shift').replaceWith(resp);
                            } catch (e) {}
                            $('#edit_shift').modal('show');
                        } else {
                            try {
                                const parsed = JSON.parse(resp);
                                populate(parsed);
                            } catch (e) {
                                $('#edit_shift').modal('show');
                            }
                        }
                    }).fail(function() {
                        tryNext(i + 1);
                    });
                })(0);
            });

            // persist cleaned display name (orig), raw staff and resolved subcontractor on the bar for toggling
            try {
                // `data-orig-staff` should be the cleaned display name (what toggle restores to)
                $bar.attr('data-orig-staff', displayStaff2 || backendStaffRaw2 || shift.staff_name || '');
                // keep a raw backup if needed
                $bar.attr('data-staff-raw', backendStaffRaw2 || shift.staff_name || '');
                if (subcontractorName2) $bar.attr('data-sub-name', subcontractorName2 || '');
            } catch (err) {
                // ignore
            }

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
                try { applySubcontractorVisibility(); } catch (e) {}
            } catch (err) {
                console.error('rerenderDayCell error', err);
            }
        }
    </script>

    <script>
        // Handle edit shift form submission on scheduling page
        $(document).off('submit', '#edit_shift-form').on('submit', '#edit_shift-form', function(e) {
            e.preventDefault();
            const shiftId = $('#shift_id').val();
            const formData = $('#edit_shift-form').serialize();

            const submitData = (useOverride = false) => {
                const url = useOverride ? `${baseUrl}/updateshift/${shiftId}/override` :
                    `${baseUrl}/updateshift/${shiftId}`;
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        try {
                            $('#edit_shift').modal('hide');
                        } catch (e) {}
                        showToast(response.success || response.message ||
                            'Shift updated successfully!', 'success', 1200);
                        // Full page reload to ensure Gantt is fully in sync with server
                        try {
                            setTimeout(function() {
                                window.location.reload();
                            }, 600);
                        } catch (e) {
                            window.location.reload();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            let messages = Object.values(xhr.responseJSON.errors).flat();
                            if (messages.length) {
                                if (window.isSuperAdmin && !useOverride) {
                                    showRestrictionToast(messages[0], function() {
                                        submitData(true);
                                    });
                                } else {
                                    toast_danger(messages[0]);
                                }
                            } else {
                                toast_danger('Validation failed, but no message returned.');
                            }
                        } else if (xhr.responseJSON?.error) {
                            if (window.isSuperAdmin && !useOverride) {
                                showRestrictionToast(xhr.responseJSON.error, function() {
                                    submitData(true);
                                });
                            } else {
                                toast_danger(xhr.responseJSON.error);
                            }
                        } else {
                            toast_danger('An unexpected error occurred while updating the shift.');
                        }
                    }
                });
            };

            submitData(false);
        });
    </script>

    <script>
        // --- Edit modal subcontractor & Select2 wiring (from shift-detail) ---
        $(document).ready(function() {
            try {
                if ($.fn.select2) {
                    $('.selec2_assign_modal').each(function() {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            var opts = {
                                dropdownParent: $('#edit_shift'),
                                width: '100%'
                            };
                            if (typeof window.customMatcher === 'function') opts.matcher = window
                                .customMatcher;
                            try {
                                $(this).select2(opts);
                            } catch (e) {
                                console.warn('[scheduling] select2 init failed', e);
                            }
                        }
                    });
                }

                function populateEditSubcontractorsAssign(staffId, preserveValue) {
                    var $modal = $('#edit_shift');
                    var $sub = $modal.find('#subcontractor');
                    if (!$sub.length) return;
                    $sub.prop('disabled', true).html('<option value="">Loading...</option>');

                    if (!staffId) {
                        $sub.html('<option value="">--choose--</option>').prop('disabled', false).trigger('change');
                        return;
                    }

                    $.ajax({
                        url: `${baseUrl}/subcontractors/for-employee/${staffId}`,
                        method: 'GET',
                        dataType: 'json'
                    }).done(function(res) {
                        $sub.empty().append('<option value="">--choose--</option>');
                        if (res && res.data && res.data.length) {
                            res.data.forEach(function(s) {
                                var label = s.company_name || (s.first_name ? (s.first_name + ' ' +
                                    (s.last_name || '')) : ('Subcontractor ' + s.id));
                                $sub.append(`<option value="${s.id}">${label}</option>`);
                            });
                        }

                        if (typeof preserveValue !== 'undefined' && preserveValue) {
                            try {
                                $sub.val(preserveValue);
                            } catch (e) {}
                        }

                        $sub.prop('disabled', false);
                        if ($sub.hasClass('select2-hidden-accessible')) $sub.trigger('change.select2');
                        else $sub.trigger('change');
                    }).fail(function() {
                        $sub.empty().append('<option value="">--choose--</option>').prop('disabled', false);
                    });
                }

                // avoid duplicate handlers
                $('#edit_shift').off('change', '#staff_id');
                $('#edit_shift').off('select2:select', '#staff_id');

                // init subcontractor select2 if present
                (function initSubSelect() {
                    var $modal = $('#edit_shift');
                    var $sub = $modal.find('#subcontractor');
                    if (!$sub.length) return;
                    try {
                        if ($.fn.select2 && !$sub.hasClass('select2-hidden-accessible')) {
                            $sub.select2({
                                dropdownParent: $modal,
                                width: '100%',
                                minimumResultsForSearch: 0
                            });
                        }
                    } catch (e) {
                        console.warn('[scheduling] subcontractor select2 init failed', e);
                    }
                })();

                var _editSubDebounce = null;

                function debouncedPopulate(staffId, preserve) {
                    clearTimeout(_editSubDebounce);
                    _editSubDebounce = setTimeout(function() {
                        populateEditSubcontractorsAssign(staffId, preserve);
                    }, 120);
                }

                $('#edit_shift').on('change', '#staff_id', function() {
                    var staffId = $(this).val();
                    debouncedPopulate(staffId);
                });
                $('#edit_shift').on('select2:select', '#staff_id', function() {
                    var staffId = $(this).val();
                    debouncedPopulate(staffId);
                });

                $('#edit_shift').find('#staff_id').off('.editStaff').on('change.editStaff', function() {
                    var staffId = $(this).val();
                    debouncedPopulate(staffId);
                }).on('select2:select.editStaff', function() {
                    var staffId = $(this).val();
                    debouncedPopulate(staffId);
                });

                $('#edit_shift').on('shown.bs.modal', function() {
                    var $modal = $('#edit_shift');
                    var staffId = $modal.find('#staff_id').val();
                    var preserve = $modal.find('#subcontractor').val();

                    var doInit = function() {
                        if (staffId) debouncedPopulate(staffId, preserve);
                    };

                    if (window.requestIdleCallback) {
                        requestIdleCallback(doInit, {
                            timeout: 200
                        });
                    } else {
                        setTimeout(doInit, 80);
                    }

                    $modal.find('#staff_id').one('focus.editLazy click.editLazy', function() {
                        var $el = $(this);
                        if ($.fn.select2 && !$el.hasClass('select2-hidden-accessible')) {
                            try {
                                var opts = {
                                    dropdownParent: $modal,
                                    width: '100%'
                                };
                                if (typeof window.customMatcher === 'function') opts.matcher =
                                    window.customMatcher;
                                $el.select2(opts);
                            } catch (e) {
                                console.warn('[scheduling] lazy select2 init failed', e);
                            }
                        }
                    });

                    $modal.find('#subcontractor').one('focus.editLazy click.editLazy', function() {
                        var $el = $(this);
                        if ($.fn.select2 && !$el.hasClass('select2-hidden-accessible')) {
                            try {
                                $el.select2({
                                    dropdownParent: $modal,
                                    width: '100%',
                                    minimumResultsForSearch: 0
                                });
                            } catch (e) {
                                console.warn('[scheduling] lazy subcontractor select2 init failed',
                                    e);
                            }
                        }
                    });
                });

            } catch (e) {
                console.error('[scheduling edit_shift] wiring failed', e);
            }
        });
    </script>

    <script>
        (function() {
            function updateGanttTopScrollbarWidth() {
                var ganttContainer = document.querySelector('.gantt-container');
                var topScrollInner = document.getElementById('ganttTopScrollInner');
                var topScrollWrapper = document.getElementById('ganttTopScroll');
                if (!ganttContainer || !topScrollInner || !topScrollWrapper) return;
                var width = ganttContainer.scrollWidth || 0;
                var timelineHeader = ganttContainer.querySelector('.gantt-timeline-header');
                if (timelineHeader && timelineHeader.scrollWidth > width) width = timelineHeader.scrollWidth;
                topScrollInner.style.width = width + 'px';
            }

            function syncTopAndGanttScrolls() {
                var ganttContainer = document.querySelector('.gantt-container');
                var topScrollWrapper = document.getElementById('ganttTopScroll');
                if (!ganttContainer || !topScrollWrapper) return;
                var syncing = false;
                topScrollWrapper.addEventListener('scroll', function() {
                    if (syncing) return;
                    syncing = true;
                    ganttContainer.scrollLeft = topScrollWrapper.scrollLeft;
                    setTimeout(function() { syncing = false; }, 10);
                });
                ganttContainer.addEventListener('scroll', function() {
                    if (syncing) return;
                    syncing = true;
                    topScrollWrapper.scrollLeft = ganttContainer.scrollLeft;
                    setTimeout(function() { syncing = false; }, 10);
                });
            }

            function initGanttTopScrollbar() {
                updateGanttTopScrollbarWidth();
                syncTopAndGanttScrolls();
                var ganttChart = document.getElementById('ganttChart');
                if (ganttChart && window.MutationObserver) {
                    var mo = new MutationObserver(function() { updateGanttTopScrollbarWidth(); });
                    mo.observe(ganttChart, { childList: true, subtree: true, attributes: true });
                }
                if (window.ResizeObserver) {
                    try {
                        var ro = new ResizeObserver(function() { updateGanttTopScrollbarWidth(); });
                        var el = document.querySelector('.gantt-container') || document.getElementById('ganttChart');
                        if (el) ro.observe(el);
                    } catch (e) {}
                }
                window.addEventListener('resize', function() {
                    clearTimeout(window._ganttTopScrollResizeTimer);
                    window._ganttTopScrollResizeTimer = setTimeout(updateGanttTopScrollbarWidth, 150);
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initGanttTopScrollbar);
            } else {
                initGanttTopScrollbar();
            }
            window.updateGanttTopScrollbar = updateGanttTopScrollbarWidth;
        })();
    </script>

@endsection
