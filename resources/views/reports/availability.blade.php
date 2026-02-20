@extends('layouts.app')
@section('title', 'SPL Connect - Availability Report')

@section('contents')
    <div class="page-wrapper" id="client-report">
        <div class="content">
            <h2 class="mb-3">Availability Report</h2>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-3">

                        @php $dowNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; @endphp
                        <div class="col-md-4">
                            <label class="form-label">Days (pick one or more)</label>
                            <div class="d-flex flex-wrap gap-2 day-toggle" role="group" aria-label="Days of week">
                                @foreach($dowNames as $idx => $label)
                                    @php $selectedDays = (array) request()->input('days', []); @endphp
                                    <input type="checkbox" class="btn-check" name="days[]" id="day-{{ $idx }}" value="{{ $idx }}" autocomplete="off" {{ in_array((string)$idx, $selectedDays) ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary btn-sm" for="day-{{ $idx }}">{{ $label }}</label>
                                @endforeach
                            </div>
                            <div class="form-text small">Tap to toggle days; works on mobile without needing Ctrl/Cmd.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="employee_id" class="form-label">Staff</label>
                            <select name="employee_id" id="employee_id" class="form-control select2_employee">
                                <option value="">All staff</option>
                                @foreach(($employees ?? []) as $id => $name)
                                    <option value="{{ $id }}" {{ (string)$id === (string) request()->input('employee_id') ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ request()->input('start_date') }}">
                        </div>

                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ request()->input('end_date') }}">
                        </div>

                        <div class="col-md-2">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control"
                                value="{{ request()->input('start_time') }}">
                        </div>

                        <div class="col-md-2">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control"
                                value="{{ request()->input('end_time') }}">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
            @if (request()->hasAny(['start_date', 'start_time', 'end_date', 'end_time', 'employee_id', 'client_id']))

                <div class="card">
                    <div class="card-body p-0">
                        @if ($bookings->isEmpty())
                            <div class="alert alert-warning m-3">No availability found for current filters.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table datatables table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Day</th>
                                            <th>Start</th>
                                            <th>End</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $dowNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; @endphp
                                        @foreach ($bookings as $key => $av)
                                            <tr>
                                                <td>{{ ++$key }}</td>
                                                <td>{{ optional($av->user)->first_name }} {{ optional($av->user)->last_name }}</td>
                                                <td>{{ $dowNames[$av->day_of_week] ?? $av->day_of_week }}</td>
                                                <td>{{ \Carbon\Carbon::parse($av->start_time)->format('H:i') }}</td>
                                                <td>{{ \Carbon\Carbon::parse($av->end_time)->format('H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body p-3">
                        <div class="alert alert-info mb-0">Please apply start & end date/time filters above to view availability.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
@section('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 JS (if not already included in your layout) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables core + Bootstrap 5 integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 for enhanced selects -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function() {
            $('.datatables').DataTable({
                responsive: true,
                pageLength: 10,
                order: [
                    [0, 'asc']
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search client..."
                }
            });

            // init select2 for employee selector if available
            try {
                if ($.fn.select2 && $('.select2_employee').length) {
                    $('.select2_employee').select2({
                        placeholder: 'Select staff',
                        allowClear: true,
                        width: '100%',
                        minimumResultsForSearch: 10
                    });
                }
            } catch (e) {
                // ignore
            }
        });
    </script>

    <style>
        /* Inline day toggle styling */
        .day-toggle .btn {
            min-width: 76px;
            padding: 6px 8px;
            text-align: center;
        }
        @media (max-width: 576px) {
            .day-toggle .btn {
                min-width: 100%;
            }
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ccc;
            padding: 6px 10px;
            width: 250px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
        }
    </style>
@endsection
