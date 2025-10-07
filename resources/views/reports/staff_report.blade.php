@extends('layouts.app')
@section('title', 'CRM - Staff Report')
@section('contents')
    <div id="staff-report" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Staff Report</h2>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Filters Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('staff.report') }}">
                        <div class="row">
                            <!-- Employee Type -->
                            <div class="col-md-3">
                                <label for="employee_type" class="form-label">Employee Type</label>
                                <select name="employee_type[]" id="employee_type" class="form-select select2 employee-type-select"
                                    multiple="multiple">
                                    <option value="security"
                                        {{ in_array('security', (array) $selectedTypes) ? 'selected' : '' }}>Security Staff
                                    </option>
                                    <option value="subcontractor"
                                        {{ in_array('subcontractor', (array) $selectedTypes) ? 'selected' : '' }}>
                                        Subcontractor</option>
                                </select>
                            </div>

                            <!-- Employment / Engagement Date -->
                            <div class="col-md-3">
                                <label for="filter_date" class="form-label">Employment / Engagement Date</label>
                                <input type="date" name="filter_date" id="filter_date" class="form-control"
                                    value="{{ $filterDate }}">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="{{ route('staff.report', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                                    class="btn btn-danger" style="margin-right: 10px;">
                                    <i class="fa fa-file-pdf"></i> Export PDF
                                </a>
                                <a href="{{ route('staff.report', array_merge(request()->all(), ['export' => 'excel'])) }}"
                                    class="btn btn-success">
                                    <i class="fa fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Card -->
            <div class="card">
                <div class="card-body p-0">
                    @if ($employees->isEmpty())
                        <div class="alert alert-warning m-3">No staff match the current filters.</div>
                    @else
                        <div class="table-responsive">
                            <table id="staffTable" class="table table-striped datatables">
                                <thead>
                                    <tr>
                                        <th>Name / Company</th>
                                        <th>Type</th>
                                        <th>SIA Licence</th>
                                        <th>Status</th>
                                        <th>Expiry Date</th>
                                        <th>Days Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employees as $staff)
                                        <tr>
                                            <td>
                                                @if ($staff->model_type == 'employee')
                                                    {{ $staff->fore_name }} {{ $staff->sur_name }}
                                                @elseif($staff->model_type == 'subcontractor')
                                                    {{ $staff->company_name }} ({{ $staff->contact_person }})
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $staff->model_type == 'employee' ? 'primary' : 'info' }}">
                                                    {{ ucfirst($staff->model_type) }}
                                                </span>
                                            </td>
                                            <td>{{ $staff->sia_licence ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $isActive =
                                                        isset($staff->sia_expiry) &&
                                                        \Carbon\Carbon::parse($staff->sia_expiry)->isFuture();
                                                @endphp
                                                <span class="{{ $isActive ? 'text-primary' : 'text-danger' }} fw-bold">
                                                    {{ $isActive ? 'Active' : $staff->sia_status ?? 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ isset($staff->sia_expiry) ? \Carbon\Carbon::parse($staff->sia_expiry)->format('d/m/Y') : 'N/A' }}
                                            </td>
                                            <td>
                                                @if (isset($staff->sia_expiry))
                                                    @php
                                                        $daysRemaining = \Carbon\Carbon::now()->diffInDays(
                                                            \Carbon\Carbon::parse($staff->sia_expiry),
                                                            false,
                                                        );
                                                    @endphp
                                                    @if ($daysRemaining > 0)
                                                        <span class="badge bg-success">{{ $daysRemaining }} days</span>
                                                    @else
                                                        <span class="badge bg-danger">Expired {{ abs($daysRemaining) }}
                                                            days ago</span>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
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