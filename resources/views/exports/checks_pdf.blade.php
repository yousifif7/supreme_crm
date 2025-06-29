<!DOCTYPE html>
<html>

<head>
    <title>RoadworthinessCheck Records</title>
</head>

<body>
    <h3>RoadworthinessCheck Report</h3>
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <thead>
            <tr>
                <th>id</th>
                <th>Date Completed</th>
                <th>Checked By</th>
                <th>Defects Found</th>
                <th>Corrective Action Taken</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($checks as $check)
                <tr>
                    <td>{{ $check->id }}</td>
                    <td>{{ $check->date_completed }}</td>
                    <td>{{ $check->checked_by }}</td>
                    <td>{{ $check->defects_found }}</td>
                    <td>{{ $check->corrective_action_taken }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
