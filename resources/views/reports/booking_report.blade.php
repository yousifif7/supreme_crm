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
                            <table class="table table-striped">
                                <thead>
                                    <tr>
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
                                    @foreach ($bookings as $booking)
                                        <tr>
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
                                            <td>{{ $booking->shift?->shift_date ?? 'N/A' }}</td>
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
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            $('.select2').select2({
                placeholder: "Select an option",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
