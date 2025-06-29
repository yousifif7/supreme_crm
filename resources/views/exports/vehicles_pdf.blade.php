<!DOCTYPE html>
<html>

<head>
    <title>Vehicles Export</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <h2>Vehicles List</h2>
    <table>
        <thead>
            <tr>
                <th>Reg. No</th>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>VIN</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle->registration_number }}</td>
                    <td>{{ $vehicle->make }}</td>
                    <td>{{ $vehicle->model }}</td>
                    <td>{{ $vehicle->year_of_manufacture }}</td>
                    <td>{{ $vehicle->vin }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
