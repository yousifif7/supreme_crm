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
    <h3>Alert & Reminder List</h3>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>MOT Due</th>
                <th>Insurance</th>
                <th>Tax</th>
                <th>Service</th>
                <th>Tachograph</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reminders as $item)
                <tr>
                    <td>{{ $item->vehicle->registration_number ?? 'N/A' }}</td>
                    <td>{{ $item->mot_due_date }}</td>
                    <td>{{ $item->insurance_renewal_date }}</td>
                    <td>{{ $item->tax_renewal_date }}</td>
                    <td>{{ $item->service_due_date }}</td>
                    <td>{{ $item->tachograph_calibration_date }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
