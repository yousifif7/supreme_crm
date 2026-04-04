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
            <td>{!! \App\Models\ShiftDate::getStatusBadge($shiftDate->is_assign) !!}</td>
        </tr>
        @endforeach
    </tbody>
</table>
