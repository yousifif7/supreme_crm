@extends('layouts.app')
@section('title', 'CRM - Booking Report')

@section('contents')
    <div class="page-wrapper" id="client-report">
        <div class="content">
            <h2 class="mb-3">Booking Report</h2>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select name="client_id" id="client_id" class="form-select select2">
                                <option value="">All Clients</option>
                                @foreach ($clients as $id => $name)
                                    <option value="{{ $id }}" {{ $selectedClient == $id ? 'selected' : '' }}>
                                        {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select select2">
                                <option value="">All Employees</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" {{ $selectedEmployee == $id ? 'selected' : '' }}>
                                        {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="book_on" {{ $selectedType == 'book_on' ? 'selected' : '' }}>Book on
                                </option>
                                <option value="book_off" {{ $selectedType == 'book_off' ? 'selected' : '' }}>Book off
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="shift_date" class="form-label">Date</label>
                            <input type="date" name="shift_date" id="shift_date" class="form-control"
                                value="{{ $selectedDate }}">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ route('booking.report', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                            class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('booking.report', array_merge(request()->query(), ['export' => 'excel'])) }}"
                            class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if ($bookings->isEmpty())
                        <div class="alert alert-warning m-3">No bookings found for current filters.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table datatables table-striped">
                                <thead>
                                    <tr> <th>#</th>
                                        <th>Employee</th>
                                        <th>Client</th>
                                        <th>Site</th>
                                        <th>Type</th>
                                        <th>Shift Date</th>
                                        <th>Timestamp</th>
                                        <th>Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bookings as $key=>$booking)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $booking->shift?->staff?->first_name ?? '' }}
                                                {{ $booking->shift?->staff->last_name ?? '' }}</td>
                                            <td>{{ $booking->shift?->shift?->client?->name ?? 'N/A' }}</td>
                                            <td>{{ $booking->shift?->shift?->site?->site_name ?? 'N/A' }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $booking->type === 'book_on' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $booking->type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $booking->shift?->shift_date ? format_date($booking->shift?->shift_date) : 'N/A' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($booking->timestamp)->format('d/m/Y H:i') }}</td>
                                            <td>{{ $booking->address ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
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

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function () {
            $('.datatables').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search client..."
                }
            });
        });
    </script>

    <style>
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
