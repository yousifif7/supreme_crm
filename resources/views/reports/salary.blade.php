@extends('layouts.app')
@section('title', 'Salary Report')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
@endsection

@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="d-flex justify-content-between mb-3">
            <h2>Salary Report</h2>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('salary.report') }}" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select select2" required>
                            <option value="">-- choose staff --</option>
                            @foreach($staffOptions as $s)
                                <option value="{{ $s->id }}" {{ (string)($selectedStaff ?? '') === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->first_name }} {{ $s->last_name }} ({{ $s->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="text" name="from_date" value="{{ $fromDate ?? '' }}" class="form-control datepicker">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="text" name="to_date" value="{{ $toDate ?? '' }}" class="form-control datepicker">
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Show</button>

                        @if(!empty(request('staff_id')))
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Export</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">Export PDF</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">Export Excel</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Export CSV</a></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if($report)
            <div class="row g-2">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>{{ $report['staff']->first_name }} {{ $report['staff']->last_name }}</h5>
                            <p class="text-muted">Period: {{ $report['date_from']->toDateString() }} — {{ $report['date_to']->toDateString() }}</p>

                            <table class="table table-sm">
                                <tbody>
                                    <tr><th>Rate (per hour)</th><td>{{ $report['payroll']['rate'] ?? '-' }}</td></tr>
                                    <tr><th>Total shift hours</th><td>{{ $report['payroll']['total_hours'] ?? 0 }}</td></tr>
                                    <tr><th>Total breaks</th><td>{{ $report['payroll']['total_breaks'] ?? 0 }}</td></tr>
                                    <tr><th>Gross</th><td>{{ $report['payroll']['gross_amount'] ?? 0 }}</td></tr>
                                    <tr><th>Net</th><td>{{ $report['payroll']['net_amount'] ?? 0 }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bank / payment info (bank-statement style) -->
                    <div class="card">
                        <div class="card-body">
                            <h6>Bank details</h6>
                            <p>
                                Bank: {{ $bankInfo['bank_name'] ?? '-' }}<br>
                                Account name: {{ $bankInfo['account_name'] ?? '-' }}<br>
                                Account number: {{ $bankInfo['account_number'] ?? '-' }}<br>
                                Sort code: {{ $bankInfo['sort_code'] ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Payroll breakdown</h6>
                            @php $p = $report['payroll'] @endphp
                            <ul class="list-unstyled">
                                <li>Shift hours: {{ $p['total_hours'] ?? 0 }}</li>
                                <li>Book On hours: {{ $p['total_book_on_hours'] ?? 0 }}</li>
                                <li>Book Off hours: {{ $p['total_book_off_hours'] ?? 0 }}</li>
                                <li>Breaks: {{ $p['total_breaks'] ?? 0 }}</li>
                                <li>Gross: {{ $p['gross_amount'] ?? 0 }}</li>
                                <li>Net: {{ $p['net_amount'] ?? 0 }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(request()->filled('staff_id'))
            <div class="alert alert-info">No payroll data available for the selected staff/date range.</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.datepicker', {dateFormat: 'Y-m-d', allowInput: true});
});
</script>
@endsection