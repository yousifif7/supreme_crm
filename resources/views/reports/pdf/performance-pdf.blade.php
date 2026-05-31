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

    <table>
        <thead>
            <tr>
                <th>Staff</th>
                <th>Completed Checkcalls</th>
                <th>Missed Checkcalls</th>
                <th>Completed Patrols</th>
                <th>Missed Patrols</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats as $row)
                <tr>
                    <td>{{ $row['staff_name'] }}</td>
                    <td>{{ $row['completed_checkcalls'] }}</td>
                    <td>{{ $row['missed_checkcalls'] }}</td>
                    <td>{{ $row['completed_patrols'] }}</td>
                    <td>{{ $row['missed_patrols'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>