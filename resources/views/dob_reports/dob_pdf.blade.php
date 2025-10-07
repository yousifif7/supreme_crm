<!DOCTYPE html>
<html>
<head>
    <title>DOB Entries Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>DOB Entries Report</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Title</th>
                <th>Type</th>
                <th>Timestamp</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dobs as $dob)
            @php
                $user = App\Models\User::find($dob->user_id);
                $shiftdate = App\Models\ShiftDate::find($dob->shift_id);
            @endphp
                <tr>
                    <td>{{ $dob->id }}</td>
                    <td>{{ $user? $user->first_name .' '. $user->last_name : 'Unknown' }}</td>
                    <td>{{ $dob->title}}</td>
                    <td>{{ $dob->entry_type }}</td>
                    <td>{{ \Carbon\Carbon::parse($dob->Timestamp)->format('d M Y, h:i A') }}</td>
                    <td>{{ $shiftdate? $shiftdate->shift->site->address : 'Unknown' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>