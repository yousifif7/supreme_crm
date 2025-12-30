@extends('layouts.app')
@section('title', 'SPL Connect | Checkpoint Report')
@section('contents')
    <div class="page-wrapper">
        <div class="content">
            <h2 class="mb-3">Checkpoint Report</h2>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('report.checkpoints') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="site_id" class="form-label">Site</label>
                                <select name="site_id" id="site_id" class="form-select select2">
                                    <option value="">All Sites</option>
                                    @foreach ($sites as $id => $name)
                                        <option value="{{ $id }}" {{ $selectedSite == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('report.checkpoints', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                                        class="btn btn-danger">
                                        <i class="fas fa-file-pdf"></i> Export PDF
                                    </a>
                                    <a href="{{ route('report.checkpoints', array_merge(request()->query(), ['export' => 'excel'])) }}"
                                        class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            @if (request()->hasAny(['shift_date', 'employee_id', 'type', 'client_id']))

                <div class="card">
                    <div class="card-body p-0">
                        @if ($checkpoints->isEmpty())
                            <div class="alert alert-warning m-3">No checkpoints found for the selected filters.</div>
                        @else
                            <div class="table-responsive">
                                <table id="checkpointTable" class="table datatables table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Checkpoint Name</th>
                                            <th>Site</th>
                                            <th>Required</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($checkpoints as $index => $c)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $c->name }}</td>
                                                <td>{{ $c->site->site_name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $c->required ? 'success' : 'secondary' }}">
                                                        {{ $c->required ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                                <td>{{ $c->latitude ?? '-' }}</td>
                                                <td>{{ $c->longitude ?? '-' }}</td>
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
                        <div class="alert alert-info mb-0">Please apply filters above to view Checkpoints data.</div>
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
