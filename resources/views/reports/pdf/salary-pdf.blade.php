<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Salary Statement</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#222; margin:20px; }
    h3 { margin-bottom: 4px; }
    .meta { margin-bottom: 12px; color:#555; }
    table { width:100%; border-collapse:collapse; margin-top:8px; }
    th, td { padding:8px 6px; border:1px solid #ddd; text-align:left; vertical-align:top; }
    th { background:#f5f5f5; font-weight:700; }
    .right { text-align:right; }
    .totals { margin-top:12px; font-weight:700; }
    .small { font-size:11px; color:#666; }
  </style>
</head>
<body>
  <h3>Salary Statement</h3>

  @if(!empty($staff))
    <div class="meta">
      <strong>{{ $staff->first_name ?? '' }} {{ $staff->last_name ?? '' }}</strong>
      @if(!empty($staff->email)) — <span class="small">{{ $staff->email }}</span>@endif
    </div>
  @endif

  <div class="small">
    Period: {{ $filters['from'] ?? '-' }} — {{ $filters['to'] ?? '-' }}
    @if(!empty($filters['site_id'])) | Site ID: {{ $filters['site_id'] }} @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:18%">#</th>
        <th style="width:18%">Staff</th>
        <th style="width:18%">Invoice #</th>
        <th style="width:12%">Issue Date</th>
        <th style="width:20%">Period</th>
        <th style="width:8%" class="right">Hours</th>
        <th style="width:12%" class="right">Gross</th>
        <th style="width:12%" class="right">Net</th>
      </tr>
    </thead>
    <tbody>
      @forelse($invoices as $index =>$inv)
        <tr>
          <td>{{$index + 1}}</td>
            <td>{{ $inv->securityStaff?->first_name ?? '' }} {{ $inv->security_staff?->last_name ?? '' }}</td>
          <td>{{ $inv->invoice_number }}</td>
          <td>{{ optional($inv->issue_date)->toDateString() ?? $inv->issue_date }}</td>
          <td>
            {{ optional($inv->date_from)->toDateString() ?? $inv->date_from ?? '-' }}
            — 
            {{ optional($inv->date_to)->toDateString() ?? $inv->date_to ?? '-' }}
          </td>
          <td class="right">{{ number_format($inv->total_shift_hours ?? $inv->total_duration_hours ?? 0, 2) }}</td>
          <td class="right">{{ number_format($inv->gross_amount ?? 0, 2) }}</td>
          <td class="right">{{ number_format($inv->net_amount ?? 0, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="small">No Payroll for the selected staff / date range.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="totals">
    <table style="width:50%; margin-top:12px;">
      <tbody>
        <tr>
          <td><strong>Payrolls</strong></td>
          <td class="right">{{ $totals['count'] ?? 0 }}</td>
        </tr>
        <tr>
          <td><strong>Total Hours</strong></td>
          <td class="right">{{ number_format($totals['hours'] ?? 0, 2) }}</td>
        </tr>
        <tr>
          <td><strong>Total Gross</strong></td>
          <td class="right">{{ number_format($totals['gross'] ?? 0, 2) }}</td>
        </tr>
        <tr>
          <td><strong>Total Net</strong></td>
          <td class="right">{{ number_format($totals['net'] ?? 0, 2) }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="small" style="margin-top:18px;">
    Generated: {{ \Carbon\Carbon::now()->toDayDateTimeString() }}
  </div>
</body>
</html>