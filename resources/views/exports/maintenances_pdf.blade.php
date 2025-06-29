<!DOCTYPE html>
<html>

<head>
    <title>Maintenance Records</title>
</head>

<body>
    <h3>Maintenance Report</h3>
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Vehicle ID</th>
                <th>Last Service</th>
                <th>Next Service</th>
                <th>Work Type</th>
                <th>Maintenance Date</th>
                <th>Garage</th>
                <th>Reported By</th>
                <th>Date Reported</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($maintenances as $m)
                <tr>
                    <td>{{ $m->id }}</td>
                    <td>{{ $m->vehicle_id }}</td>
                    <td>{{ $m->last_service_date }}</td>
                    <td>{{ $m->next_service_due_date }}</td>
                    <td>{{ $m->work_type }}</td>
                    <td>{{ $m->maintenance_date }}</td>
                    <td>{{ $m->garage_provider }}</td>
                    <td>{{ $m->reported_by }}</td>
                    <td>{{ $m->date_reported }}</td>
                    <td>{{ $m->resolution_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
