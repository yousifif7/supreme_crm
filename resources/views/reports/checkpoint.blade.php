@extends('layouts.app')
@section('title', 'CRM | Checkpoint Report')
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
        <div class="card">
            <div class="card-body p-0">
                @if ($checkpoints->isEmpty())
                    <div class="alert alert-warning m-3">No checkpoints found for the selected filters.</div>
                @else
                    <div class="table-responsive">
                        <table id="checkpointTable" class="table table-striped">
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
