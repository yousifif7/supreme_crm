<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Client</th>
            <th>Site</th>
            <th>Notes</th>
            <th>Shift Date</th>
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
        @foreach($shiftDates as $shiftDate)
        <tr>
            <td>{{ $shiftDate->staff->first_name ?? 'N/A' }} {{ $shiftDate->staff->last_name ?? '' }}</td>
            <td>{{ $shiftDate->shift->client->name ?? 'N/A' }}</td>
            <td>{{ $shiftDate->shift->site->site_name ?? 'N/A' }}</td>
            <td>{{ optional($shiftDate->note)->note ?? '' }}</td>
            <td>{{ $shiftDate->shift_date ? format_date($shiftDate->shift_date) : 'N/A' }}</td>
            <td>{{ $shiftDate->start_time ?? 'N/A' }}</td>
            <td>{{ $shiftDate->end_time ?? 'N/A' }}</td>
            <td>{{ $shiftDate->planned_duration_hours !== null ? number_format($shiftDate->planned_duration_hours, 2) : '' }}</td>
            <td>{{ $shiftDate->absentee_start_time ?? '-' }}</td>
            <td>{{ $shiftDate->absentee_end_time ?? '-' }}</td>
            <td>{{ $shiftDate->actual_duration_hours !== null ? number_format($shiftDate->actual_duration_hours, 2) : '' }}</td>
            <td>{{ ($shiftDate->book_on_late_minutes ?? 0) > 0 ? $shiftDate->book_on_late_minutes : '' }}</td>
            <td>{{ \App\Models\ShiftDate::getStatusLabels()[$shiftDate->is_assign] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
