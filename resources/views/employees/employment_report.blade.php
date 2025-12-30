@extends('layouts.app')
@section('title', 'SPL Connect - Employment Report')
@section('contents')
    <div id="employment-report" class="page-wrapper">
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <h2 class="mb-1">Employment Report</h2>
            </div>

            <!-- Filter Form -->
            <div class="card">
                <div class="card-body">
                    @php
                        // Load all employees for the dropdown
                        $allEmployees = App\Models\Employee::orderBy('fore_name')->get();
                    @endphp
                    <form method="GET" action="{{ route('reports.employment') }}">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Select Employee</label>
                                <select name="name" id="name" class="form-select select2">
                                    <option value="">-- Choose Employee --</option>
                                    @foreach ($allEmployees as $emp)
                                        @php $fullName = trim($emp->fore_name . ' ' . $emp->sur_name); @endphp
                                        <option value="{{ $fullName }}"
                                            {{ request('name') == $fullName ? 'selected' : '' }}>
                                            {{ $fullName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                            <div class="alert alert-info">Please select an employee to view results.</div>
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

                                            if ($start) {
                                                $diff = $start->diff($end);
                                                $parts = [];
                                                if ($diff->y > 0) {
                                                    $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
                                                }
                                                if ($diff->m > 0) {
                                                    $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
                                                }
                                                if ($diff->d > 0) {
                                                    $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
                                                }
                                                $duration = implode(', ', $parts) ?: '0 days';
                                            } else {
                                                $duration = 'N/A';
                                            }
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

    <!-- DataTables + Select2 -->


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
