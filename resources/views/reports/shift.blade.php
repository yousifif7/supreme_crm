@extends('layouts.app')
@section('title', 'CRM - Document Report')

@section('contents')
    <div id="shift-report" class="page-wrapper">
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shift Report</h2>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.shift') }}">
                        <div class="row g-3">
                            <!-- Client -->
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

                            <!-- Employee -->
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select name="employee_id" id="employee_id" class="form-select select2"
                                    data-placeholder="Select Employee">
                                    <option value="">All Employees</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ $selectedEmployee == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Shift Status</label>
                                <select name="status[]" id="status" class="form-select select2" multiple>
                                    @foreach ($statusOptions as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ in_array($key, (array) $selectedStatus) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Shift Date -->
                            <div class="col-md-3">
                                <label for="shift_date" class="form-label">Shift Date</label>
                                <input type="date" name="shift_date" id="shift_date" class="form-control"
                                    value="{{ $filterDate }}">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <a href="{{ route('reports.shift', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                                    class="btn btn-danger" style="margin-right:10px;">
                                    <i class="fa fa-file-pdf"></i> Export PDF
                                </a>
                                <a href="{{ route('reports.shift', array_merge(request()->all(), ['export' => 'excel'])) }}"
   class="btn btn-success">
   <i class="fa fa-file-excel"></i> Export Excel
</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="card">
                <div class="card-body p-0">
                    @if ($shifts->isEmpty())
                        <div class="alert alert-warning m-3">No shifts found for selected filters.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Site</th>
                                        <th>Employee</th>
                                        <th>Shift Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shifts as $shiftDate)
                                        <tr>
                                            <td>{{ $shiftDate->shift->client->name ?? 'N/A' }}</td>
                                            <td>{{ $shiftDate->shift->site->site_name ?? 'N/A' }}</td>
                                            <td>{{ $shiftDate->staff->first_name ?? 'N/A' }}
                                                {{ $shiftDate->staff->last_name ?? '' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($shiftDate->shift_date)->format('d/m/Y') }}</td>
                                            <td>{{ $shiftDate->start_time ?? '-' }}</td>
                                            <td>{{ $shiftDate->end_time ?? '-' }}</td>
                                            <td>{!! \App\Models\ShiftDate::getStatusBadge($shiftDate->is_assign) !!}</td>
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
