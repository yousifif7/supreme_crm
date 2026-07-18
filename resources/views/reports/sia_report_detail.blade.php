@extends('layouts.app')
@section('title', brand_title('SIA Report Detail'))
@section('styles')
    <style>
        .filter-card {
            transition: transform .12s ease, box-shadow .12s ease;
            cursor: pointer;
        }
        .filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12) !important;
        }
    </style>
@endsection
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="alert-box-container"></div>

        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">SIA Check Detail Report</h2>
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
                    {{-- Preserve the active status filter while searching --}}
                    @if (!empty($status))
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
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

        {{-- Summary Stats — click a card to filter the whole run by that status --}}
        @php
            $activeStatus = $status ?? '';
            // Preserve search + page size across filter clicks; runId is a route param.
            $carry = array_filter([
                'q'        => request('q'),
                'per_page' => request('per_page'),
            ], fn ($v) => $v !== null && $v !== '');
            $cardLink = fn ($s) => route('reports.sia.show', array_merge([$runId], $carry, $s ? ['status' => $s] : []));
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <a href="{{ $cardLink(null) }}"
                    class="card text-center border-0 shadow-sm text-decoration-none filter-card {{ $activeStatus === '' ? 'border border-2 border-dark' : '' }}">
                    <div class="card-body py-3">
                        <h4 class="mb-1">{{ $stats['total_scanned'] }}</h4>
                        <small class="text-muted">Scanned</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ $cardLink('active') }}"
                    class="card text-center border-0 shadow-sm text-decoration-none filter-card bg-success bg-opacity-10 {{ $activeStatus === 'active' ? 'border border-2 border-success' : '' }}">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-success">{{ $stats['active'] }}</h4>
                        <small class="text-muted">Active</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ $cardLink('inactive') }}"
                    class="card text-center border-0 shadow-sm text-decoration-none filter-card bg-danger bg-opacity-10 {{ $activeStatus === 'inactive' ? 'border border-2 border-danger' : '' }}">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-danger">{{ $stats['inactive'] }}</h4>
                        <small class="text-muted">Inactive</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ $cardLink('revoked') }}"
                    class="card text-center border-0 shadow-sm text-decoration-none filter-card bg-secondary bg-opacity-10 {{ $activeStatus === 'revoked' ? 'border border-2 border-secondary' : '' }}">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-secondary">{{ $stats['revoked'] }}</h4>
                        <small class="text-muted">Revoked</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ $cardLink('errors') }}"
                    class="card text-center border-0 shadow-sm text-decoration-none filter-card bg-warning bg-opacity-10 {{ $activeStatus === 'errors' ? 'border border-2 border-warning' : '' }}">
                    <div class="card-body py-3">
                        <h4 class="mb-1 text-warning">{{ $stats['errors'] }}</h4>
                        <small class="text-muted">Errors</small>
                    </div>
                </a>
            </div>
        </div>

        @if ($activeStatus !== '')
            <div class="mb-3 d-flex align-items-center gap-2">
                <span class="badge bg-dark">Filtered by: {{ ucfirst($activeStatus) }}</span>
                <a href="{{ $cardLink(null) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x"></i> Clear filter
                </a>
            </div>
        @endif

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
