@extends('layouts.app')
@section('title', 'Reports - Performance')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <!-- Select2 for searchable staff select -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .totals-row .card { min-width: 180px; }
    .report-table th, .report-table td { vertical-align: middle; }
</style>
@endsection

@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="d-flex justify-content-between mb-3">
            <h2>Performance Report</h2>
            <div>
                <a href="{{ route('performance.report') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form id="performanceFilterForm" method="GET" action="{{ route('performance.report') }}" class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="text" name="from_date" value="{{ old('from_date', $fromDate ?? '') }}" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="text" name="to_date" value="{{ old('to_date', $toDate ?? '') }}" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Client</label>
                        <select id="clientSelect" name="client_id" class="form-select">
                            <option value="">-- All Clients --</option>
                            @foreach($clients as $id => $name)
                                <option value="{{ $id }}" {{ (string)($selectedClient ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Site</label>
                        <select id="siteSelect" name="site_id" class="form-select">
                            <option value="">-- All Sites --</option>
                            @if(!empty($sites) && $sites->count())
                                @foreach($sites as $id => $name)
                                    <option value="{{ $id }}" {{ (string)($selectedSite ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select id="staffSelect" name="staff_id" class="form-select staff-select">
                            <option value="">-- All Staff --</option>
                            @if(!empty($staffs))
                                @foreach($staffs as $key => $item)
                                    @php
                                        // handle different shapes: object, array, or id=>name
                                        if (is_object($item)) {
                                            $sid = $item->id ?? $item->user_id ?? $key;
                                            $sname = trim(($item->first_name ?? $item->firstName ?? '') . ' ' . ($item->last_name ?? $item->lastName ?? '')) ?: ($item->name ?? $item->full_name ?? '');
                                        } elseif (is_array($item)) {
                                            $sid = $item['id'] ?? $key;
                                            $sname = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')) ?: ($item['name'] ?? '');
                                        } else {
                                            $sid = $key;
                                            $sname = $item;
                                        }
                                    @endphp
                                    <option value="{{ $sid }}" {{ (string)($selectedStaff ?? request('staff_id', '')) === (string)$sid ? 'selected' : '' }}>{{ $sname }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">Export PDF</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">Export Excel</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Export CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    @if(request()->hasAny(['from_date','to_date','client_id','site_id','staff_id']))
    <!-- Totals -->
    <div class="row totals-row mb-3 gx-2">
            <div class="col-auto">
                <div class="card p-2">
                    <div class="card-body text-center">
                        <h6 class="mb-1">Total Shifts</h6>
                        <div class="h4 mb-0">{{ number_format($totals['total_shifts_to_client'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card p-2">
                    <div class="card-body text-center">
                        <h6 class="mb-1">Missed Checkcalls</h6>
                        <div class="h4 mb-0">{{ number_format($totals['total_missed_checkcalls'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card p-2">
                    <div class="card-body text-center">
                        <h6 class="mb-1">Missed Patrols</h6>
                        <div class="h4 mb-0">{{ number_format($totals['total_missed_patrols'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card p-2">
                    <div class="card-body text-center">
                        <h6 class="mb-1">Unassigned Shifts</h6>
                        <div class="h4 mb-0">{{ number_format($totals['total_unassigned_shifts'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card p-2">
                    <div class="card-body text-center">
                        <h6 class="mb-1">Completed Shifts</h6>
                        <div class="h4 mb-0">{{ number_format($totals['total_completed_shifts'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        
    <!-- Results -->
        <div class="card">
            <div class="card-body p-3">
                @if($stats && $stats->count())
                    <div class="table-responsive">
                        <table class="table table-striped report-table">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Total Shifts</th>
                                    <th>Total Hours</th>
                                    @foreach($statusOptions as $code => $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats as $row)
                                    <tr>
                                        <td>
                                            @if($row['staff_id'] === 'unassigned')
                                                <em>Unassigned</em>
                                            @else
                                                <a href="{{ url('staff/' . $row['staff_id']) }}" target="_blank">{{ $row['staff_name'] }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $row['total_shifts'] }}</td>
                                        <td>{{ $row['total_hours'] }}</td>
                                        @foreach($statusOptions as $code => $label)
                                            <td>{{ $row['status_counts'][$code] ?? 0 }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">No data available for the selected filters.</div>
                @endif
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body p-3">
                <div class="alert alert-info mb-0">Please apply filters above to view report data.</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.datepicker', { dateFormat: 'Y-m-d', allowInput: true });

    const clientSelect = document.getElementById('clientSelect');
    const siteSelect = document.getElementById('siteSelect');

    clientSelect?.addEventListener('change', function () {
        const clientId = this.value;
        siteSelect.innerHTML = '<option value="">Loading...</option>';
        if (!clientId) {
            siteSelect.innerHTML = '<option value="">-- All Sites --</option>';
            return;
        }
        fetch(`/api/client/${clientId}`)
            .then(res => res.json())
            .then(data => {
                siteSelect.innerHTML = '<option value="">-- All Sites --</option>';
                if (data.sites && data.sites.length) {
                    data.sites.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.site_name;
                        siteSelect.appendChild(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No sites found';
                    siteSelect.appendChild(opt);
                }
            })
            .catch(err => {
                console.error('Error fetching sites', err);
                siteSelect.innerHTML = '<option value="">-- All Sites --</option>';
            });
    });

    // Initialize Select2 for staff select (if jQuery & select2 are present)
    try {
        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
            // use dropdownParent to ensure the dropdown appears inside the card/overflow container
            jQuery('.staff-select').select2({
                placeholder: '-- All Staff --',
                allowClear: true,
                width: '100%'
            });
        }
    } catch (e) {
        console.warn('Select2 init failed', e);
    }
});
</script>
@endsection