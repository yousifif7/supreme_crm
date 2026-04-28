<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Performance Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        .header { text-align: center; margin-bottom: 12px; }
        .totals { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
        .tot-card { border:1px solid #ddd; padding:8px 10px; border-radius:4px; min-width:160px; text-align:center; }
        table { width:80%; border-collapse: collapse; margin-top:8px; margin-right:10px; }
        th, td { border:1px solid #ddd; padding:6px 8px; text-align:left; }
        th { background:#f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Performance Report</h2>
        <div>
            @if(!empty($filters['from']) || !empty($filters['to']))
                <small>Period:
                    {{ $filters['from'] ?? '-' }} — {{ $filters['to'] ?? '-' }}
                </small>
            @endif
            @if(!empty($filters['client']))
                <div><small>Client ID: {{ $filters['client'] }}</small></div>
            @endif
            @if(!empty($filters['site']))
                <div><small>Site ID: {{ $filters['site'] }}</small></div>
            @endif
        </div>
    </div>

    <div class="totals">
        <div class="tot-card">
            <div>Total Shifts</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_shifts_to_client'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Missed Checkcalls</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_missed_checkcalls'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Missed Patrols</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_missed_patrols'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Total Checkcalls</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_checkcalls'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Completed Checkcalls</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_completed_checkcalls'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Total Patrols</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_patrols'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Completed Patrols</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_completed_patrols'] ?? 0) }}</div>
        </div>
        <div class="tot-card">
            <div>Completed Shifts</div>
            <div style="font-weight:bold; font-size:16px">{{ number_format($totals['total_completed_shifts'] ?? 0) }}</div>
        </div>
    </div>

    <table>
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
                        {{ $row['staff_name'] }}
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
</body>
</html>