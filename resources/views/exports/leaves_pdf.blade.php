<!DOCTYPE html>
<html>

<head>
    <title>Leaves PDF</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <h2>Leaves List</h2>
    <table>
        <thead>
            <tr>
                <th>Details</th>
                <th>Date From</th>
                <th>Date To</th>
                <th>Employee</th>
                <th>Status</th>
                <th>Applied At</th>
                <th>Approved At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leaves as $lv)
                <tr>
                    <td>{{ $lv->leave_entitlement }}</td>
                    <td>{{ $lv->from_date }}</td>
                    <td>{{ $lv->to_date }}</td>
                    <td>{{ $lv->employee?->fore_name .' '. $lv->employee?->sur_name }}</td>
                    <td>{{ ucwords($lv->status) }}</td>
                    <td>{{ $lv->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $lv->approved_at?->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
