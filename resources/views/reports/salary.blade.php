@extends('layouts.app')
@section('title', 'Salary Report')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.3.1/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
}
</style>
@endsection

@section('contents')
<div class="page-wrapper">
  <div class="content">
    <div class="d-flex justify-content-between mb-3">
      <h2>Salary Statement</h2>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('salary.report') }}" class="row g-2">
          <div class="col-md-5">
            <label class="form-label">Staff</label>
            <select name="staff_id" class="form-select select2" required>
                <option value="">-- choose staff --</option>
                @foreach($staffOptions as $s)
                    <option value="{{ $s->id }}" {{ (string)($selectedStaff ?? '') === (string)$s->id ? 'selected' : '' }}>
                        {{ $s->first_name }} {{ $s->last_name }}
                    </option>
                @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="text" name="from_date" value="{{ $fromDate ?? '' }}" class="form-control datepicker">
          </div>
          <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="text" name="to_date" value="{{ $toDate ?? '' }}" class="form-control datepicker">
          </div>

          <div class="col-md-3 d-flex align-items-end gap-2">
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

    @if(request()->hasAny(['from_date','to_date','client_id','site_id','staff_id']))

    @if(!empty($invoices) && $invoices->count())
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <div>
              <strong>Invoices:</strong> {{ $totals['count'] }} &nbsp; | &nbsp;
              <strong>Total Hours:</strong> {{ $totals['hours'] }} &nbsp; | &nbsp;
              <strong>Total Gross:</strong> {{ number_format($totals['gross'], 2) }} &nbsp; | &nbsp;
              <strong>Total Net:</strong> {{ number_format($totals['net'], 2) }}
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                   <th>#</th>
                   <th>Invoice #</th>
                  <th>Staff</th>
                  <th>Issue Date</th>
                  <th>Period</th>
                  <th>Hours</th>
                  <th>Gross</th>
                  <th>Net</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($invoices as $index => $inv)
                  <tr>
                    <td>{{ $index+1}}</td>
                    <td>{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->securityStaff?->first_name ?? '' }} {{ $inv->security_staff?->last_name ?? '' }}</td>
                    <td>{{ optional($inv->issue_date)->toDateString() ?? $inv->issue_date }}</td>
                    <td>{{ optional($inv->date_from)->toDateString() ?? $inv->date_from }} — {{ optional($inv->date_to)->toDateString() ?? $inv->date_to }}</td>
                    <td>{{ $inv->total_shift_hours ?? $inv->total_duration_hours ?? 0 }}</td>
                    <td>{{ number_format($inv->gross_amount ?? 0, 2) }}</td>
                    <td>{{ number_format($inv->net_amount ?? 0, 2) }}</td>
                    <td><a href="{{ url('payrolls/'.$inv->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @elseif(request()->filled('staff_id'))
      <div class="alert alert-info">No invoices found for the selected staff / date range.</div>
    @endif
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.datepicker', {dateFormat: 'Y-m-d', allowInput: true});

    // Initialize Select2 on the server-rendered staff list
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select staff',
        allowClear: true
    });

    // Keep the select2 dropdown open to show selected when page loads (useful when option is preselected)
    @if(!empty($selectedStaff))
        $('.select2').val('{{ $selectedStaff }}').trigger('change');
    @endif
});
</script>
@endsection