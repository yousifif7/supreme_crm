@extends('layouts.app')
@section('title', 'CRM - Employment Report')
@section('contents')
    <div id="employment-report" class="page-wrapper">
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <h2 class="mb-1">Employment Report</h2>
            </div>

            <!-- Filter Form -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.employment') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="name" class="form-label">Employee Name</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    value="{{ request('name') }}" placeholder="Enter employee name">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card mt-3">
                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">

                        @if (!$hasFilters)
                            <div class="alert alert-info">Please enter a name to view results.</div>
                        @elseif($employees->isEmpty())
                            <div class="alert alert-warning">No employees match the current filters.</div>
                        @else
                            <table id="employmentTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Employment Start</th>
                                        <th>Employment End</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employees as $employee)
                                        @php
                                            $start = $employee->employment_start_date
                                                ? \Carbon\Carbon::parse($employee->employment_start_date)
                                                : null;
                                            $end = $employee->employment_end_date
                                                ? \Carbon\Carbon::parse($employee->employment_end_date)
                                                : now();
                                            $duration = $start
                                                ? $start->diff($end)->format('%y years, %m months, %d days')
                                                : 'N/A';
                                        @endphp
                                        <tr>
                                            <td>{{ $employee->id }}</td>
                                            <td>{{ $employee->fore_name }} {{ $employee->sur_name }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $employee->status == 'active' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($employee->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $start ? $start->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $employee->employment_end_date ? $end->format('d/m/Y') : 'Present' }}
                                            </td>
                                            <td>{{ $duration }}</td>
                                            <td>
                                                <a href="{{ url('employees#' . $employee->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                                {{-- <a href="{{ route('reports.employment.pdf', $employee->id) }}"
                                                    class="btn btn-sm btn-danger">
                                                    Export PDF
                                                </a> --}}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#employmentTable').DataTable({
                pageLength: 10,
                ordering: true,
                searching: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employees..."
                }
            });
        });
    </script>
@endsection
