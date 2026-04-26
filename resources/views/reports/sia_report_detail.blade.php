@extends('layouts.app')
@section('title', 'SPL Connect - SIA Report Detail')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="alert-box-container"></div>

        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">SIA Check Run Detail</h2>
                <p class="text-muted mb-0">
                    Run: <strong>{{ \Carbon\Carbon::parse($runDate)->format('d M Y, H:i') }}</strong>
                    &mdash; ID: <code class="text-muted" style="font-size:.75rem;">{{ $runId }}</code>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('reports.sia') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> Back to Reports
                </a>
                <form method="get" action="" class="d-flex gap-2">
                    <input type="search" name="q" value="{{ $q ?? request('q') }}" class="form-control form-control-sm" placeholder="Search name or licence">
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="25" {{ (request('per_page',50) == 25) ? 'selected' : '' }}>25</option>
                        <option value="50" {{ (request('per_page',50) == 50) ? 'selected' : '' }}>50</option>
                        <option value="100" {{ (request('per_page',50) == 100) ? 'selected' : '' }}>100</option>
                    </select>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="ti ti-search"></i></button>
                </form>
                <a href="{{ route('reports.sia.csv', $runId) }}" class="btn btn-success">
                    <i class="ti ti-download"></i> Download CSV
                </a>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <h4 class="mb-1">{{ $stats['total_scanned'] }}</h4>
                        <small class="text-muted">Scanned</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card text-center border-0 shadow-sm bg-success bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-success">{{ $stats['active'] }}</h4>
                        <small class="text-muted">Active</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card text-center border-0 shadow-sm bg-danger bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-danger">{{ $stats['inactive'] }}</h4>
                        <small class="text-muted">Inactive</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card text-center border-0 shadow-sm bg-secondary bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-secondary">{{ $stats['revoked'] }}</h4>
                        <small class="text-muted">Revoked</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card text-center border-0 shadow-sm bg-warning bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-warning">{{ $stats['errors'] }}</h4>
                        <small class="text-muted">Errors</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Table --}}
        <div class="card">
            <div class="card-body p-0">
                @if ($entries->isEmpty())
                    <div class="alert alert-info m-3">No entries found for this run.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee Name</th>
                                <th>SIA Licence</th>
                                <th class="text-center">Status Before</th>
                                <th class="text-center">Status After</th>
                                <th class="text-center">Changed</th>
                                <th>Error</th>
                                <th>Checked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                            <tr class="{{ $entry->error ? 'table-warning' : '' }}">
                                <td>{{ $entry->employee_name ?? '—' }}</td>
                                <td><code>{{ $entry->sia_licence ?? '—' }}</code></td>
                                    <td class="text-center">
                                        @php($sb = strtolower($entry->status_before ?? ''))
                                        @if ($sb === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif ($sb === 'inactive')
                                            <span class="badge bg-danger">Inactive</span>
                                        @else
                                            <span class="text-muted">{{ $entry->status_before ?? '—' }}</span>
                                        @endif
                                    </td>
                                <td class="text-center">
                                        @php($sa = strtolower($entry->status_after ?? ''))
                                        @if ($sa === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif ($sa === 'inactive')
                                            <span class="badge bg-danger">Inactive</span>
                                        @else
                                            <span class="text-muted">{{ $entry->status_after ?? '—' }}</span>
                                        @endif
                                </td>
                                <td class="text-center">
                                    @if ($entry->changed)
                                        <span class="badge bg-primary">Yes</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->error)
                                        <span class="text-danger" title="{{ $entry->error }}">
                                            {{ Str::limit($entry->error, 60) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($entry->checked_at)->format('H:i:s') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 d-flex align-items-center justify-content-between">
                    <div>
                        {!! $entries->onEachSide(1)->links('pagination::bootstrap-5') !!}
                    </div>
                    <div class="text-muted small">
                        Showing {{ $entries->firstItem() ?? 0 }} to {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} results
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
