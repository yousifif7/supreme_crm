@extends('layouts.app')
@section('title', 'SPL Connect - SIA Licence Reports')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="alert-box-container"></div>

        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">SIA Licence Check Reports</h2>
                <p class="text-muted mb-0">One row per check run. Click a run to see per-employee detail.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                @if ($runs->isEmpty())
                    <div class="alert alert-info m-3">No SIA check reports yet. Reports are generated automatically each time the SIA checker runs.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Run Date / Time</th>
                                <th class="text-center">Total Scanned</th>
                                <th class="text-center">Changed</th>
                                <th class="text-center text-success">Activated</th>
                                <th class="text-center text-danger">Deactivated</th>
                                <th class="text-center text-warning">Errors</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($runs as $run)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($run->run_date)->format('d M Y, H:i') }}</td>
                                <td class="text-center">{{ $run->total_scanned }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $run->total_changed }}</span>
                                </td>
                                <td class="text-center">
                                    @if($run->activated > 0)
                                        <span class="badge bg-success">{{ $run->activated }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($run->deactivated > 0)
                                        <span class="badge bg-danger">{{ $run->deactivated }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($run->errors > 0)
                                        <span class="badge bg-warning text-dark">{{ $run->errors }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('reports.sia.show', $run->run_id) }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                    <a href="{{ route('reports.sia.csv', $run->run_id) }}" class="btn btn-sm btn-success">
                                        <i class="ti ti-download"></i> CSV
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $runs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
