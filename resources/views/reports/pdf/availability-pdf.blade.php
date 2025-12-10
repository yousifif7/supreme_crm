<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Availability Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h2 { text-align: center; margin-bottom: 15px; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #888; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h2>Availability Report</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
            </tr>
        </thead>
        <tbody>
            @php $dowNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; @endphp
            @forelse($availabilities as $index => $av)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($av->user)->first_name }} {{ optional($av->user)->last_name }}</td>
                    <td>{{ $dowNames[$av->day_of_week] ?? $av->day_of_week }}</td>
                       <td>{{ \Carbon\Carbon::parse($av->start_time)->format('H:i') }}</td>
                       <td>{{ \Carbon\Carbon::parse($av->end_time)->format('H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No availability found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="text-align: right; font-size: 11px; color: #777;">Generated on {{ now()->format('d M Y, H:i') }}</p>
</body>
</html>
