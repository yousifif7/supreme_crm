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
        @forelse($availabilities as $i => $av)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ optional($av->user)->first_name }} {{ optional($av->user)->last_name }}</td>
                <td>{{ $dowNames[$av->day_of_week] ?? $av->day_of_week }}</td>
                    <td>{{ \Carbon\Carbon::parse($av->start_time)->format('H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($av->end_time)->format('H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No availability found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
