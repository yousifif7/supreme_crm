<!DOCTYPE html>
<html>

<head>
    <title>Alert & Reminders PDF</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h3>Shifts List</h3>
    <table>
        <thead>
            <th>Staff Name</th>
            <th>Client Name</th>
            <th>Site </th>
            <th>shift Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Total Hours</th>
            <th>Break TIme</th>
            <th>Book On</th>
            <th>Book Off</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shifts as $item)
            @php
                $staff = App\Models\User::find($item->staff_id);
                $client = App\Models\User::role('client')->where('id',$item->shift->client_id)->first();
            @endphp
                <tr>
                    <td>{{ $staff->first_name ?? 'N/A' }} {{ $staff->last_name ?? '' }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $item->shift->site->site_name }}</td>
                    <td>{{ $item->shift_date }}</td>
                    <td>{{ $item->start_time }}</td>
                    <td>{{ $item->end_time }}</td>
                    <td>{{ $item->total_hours }}</td>
                    <td>{{ $item->break_time }}</td>
                    <td>{{ $item->absentee_start_time }}</td>
                    <td>{{ $item->absentee_end_time }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
