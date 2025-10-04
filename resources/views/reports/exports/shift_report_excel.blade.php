<table>
    <thead>
        <tr>
            <th>Shift ID</th>
            <th>Employee</th>
            <th>Client</th>
            <th>Site</th>
            <th>Shift Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($shiftDates as $shiftDate)
        <tr>
            <td>{{ $shiftDate->id }}</td>
            <td>{{ $shiftDate->staff->first_name ?? 'N/A' }} {{ $shiftDate->staff->last_name ?? '' }}</td>
            <td>{{ $shiftDate->shift->client->name ?? 'N/A' }}</td>
            <td>{{ $shiftDate->shift->site->site_name ?? 'N/A' }}</td>
            <td>{{ $shiftDate->shift_date ? \Carbon\Carbon::parse($shiftDate->shift_date)->format('d/m/Y') : 'N/A' }}</td>
            <td>
                {!! \App\Models\ShiftDate::getStatusBadge($shiftDate->is_assign) !!}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
