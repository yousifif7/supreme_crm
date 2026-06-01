<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shift Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Shift Report</h2>
    <p>Generated: {{ now()->format('d M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Site</th>
                <th>Employee</th>
                <th>Notes</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Total Hours</th>
                <th>Book On</th>
                <th>Book Off</th>
                <th>Worked Hours</th>
                <th>Delay to Book On (mins)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shiftDates as $shiftDate)
                <tr>
                    <td>{{ $shiftDate->shift->client->name ?? 'N/A' }}</td>
                    <td>{{ $shiftDate->shift->site->site_name ?? 'N/A' }}</td>
                    <td>{{ $shiftDate->staff->first_name ?? '' }} {{ $shiftDate->staff->last_name ?? '' }}</td>
                    <td>{{ optional($shiftDate->note)->note ?? '-' }}</td>
                    <td>{{ format_date($shiftDate->shift_date) }}</td>
                    <td>{{ $shiftDate->start_time }}</td>
                    <td>{{ $shiftDate->end_time }}</td>
                    <td>{{ $shiftDate->planned_duration_hours !== null ? number_format($shiftDate->planned_duration_hours, 2) : '-' }}</td>
                    <td>{{ $shiftDate->absentee_start_time ?? '-' }}</td>
                    <td>
                        {{ $shiftDate->absentee_end_time ?? '-' }}
                        @if($shiftDate->book_off_early_minutes > 0)
                            <br><span style="color:#c00; font-size:0.85em;">{{ $shiftDate->book_off_early_minutes }} mins early</span>
                        @endif
                    </td>
                    <td>{{ $shiftDate->actual_duration_hours !== null ? number_format($shiftDate->actual_duration_hours, 2) : '-' }}</td>
                    <td>
                        @if(($shiftDate->book_on_late_minutes ?? 0) > 0)
                            <span style="color:#c00;">{{ $shiftDate->book_on_late_minutes }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{!! \App\Models\ShiftDate::getStatusBadge($shiftDate->is_assign) !!}</td>
                </tr>
            @empty
                <tr><td colspan="13" style="text-align:center;">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
