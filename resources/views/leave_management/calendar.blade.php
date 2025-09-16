@extends('layouts.app')
@section('title')
    CRM | Holidays Calendar
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <style>
        /* Container */
        #ukCalendar {
            width: 100%;
            /* full width of parent */
            max-width: 100%;
            /* remove fixed max-width */
            margin: 20px auto;
            /* keep top/bottom spacing */
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            flex: 1 1 auto;
            /* allow flex growth */
            min-height: 600px;
            /* ensures calendar isn’t too short */
        }

        /* FullCalendar header */
        .fc .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .fc .fc-button {
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 14px;
            background: #434444;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }

        .fc .fc-button:hover {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        /* Calendar cells */
        .fc .fc-daygrid-day-number {
            font-weight: 500;
            color: #555;
        }

        /* UK Holiday events */
        .fc-event {
            border: none !important;
            border-radius: 8px !important;
            padding: 4px 6px !important;
            font-size: 13px !important;
            font-weight: 500;
        }

        .holiday-event {
            background: #ff4d4f !important;
            color: #fff !important;
        }

        .content {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            padding: 0 20px;
            /* optional */
        }

        /* Today’s highlight */
        .fc .fc-day-today {
            background: #aae2fc !important;
        }
    </style>
    </style>
@endsection

@section('contents')
    <div class="page-wrapper">

        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <br>
                    <h2 class="mb-1">Calendar - Holidays</h2>
                </div>
            </div>
            <div id="ukCalendar"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const calendarEl = document.getElementById("ukCalendar");

            fetch("https://www.gov.uk/bank-holidays.json")
                .then(response => response.json())
                .then(data => {
                    const englandHolidays = data["england-and-wales"].events;

                    const events = englandHolidays.map(holiday => ({
                        title: holiday.title,
                        start: holiday.date,
                        allDay: true,
                        className: "holiday-event" // use custom style
                    }));

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: "dayGridMonth", // month view looks cleaner
                        headerToolbar: {
                            left: "prev,next today",
                            center: "title",
                            right: "dayGridMonth,dayGridYear" // switch between month/year
                        },
                        events: events,
                        height: "auto",
                    });

                    calendar.render();
                });
        });
    </script>
@endsection
