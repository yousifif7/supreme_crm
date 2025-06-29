<!DOCTYPE html>
<html>

<head>
    <title>Sites PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <h2>Site List</h2>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Name</th>
                <th>Code</th>
                <th>Post Code</th>
                <th>Contact</th>
                <th>Managers</th>
                <th>Time</th>
                <th>Rates</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sites as $site)
                <tr>
                    <td>{{ $site->client_id }}</td>
                    <td>{{ $site->site_name }}</td>
                    <td>{{ $site->site_code }}</td>
                    <td>{{ $site->post_code }}</td>
                    <td>{{ $site->contact_number }}</td>
                    <td>{{ $site->manager_1_id }}, {{ $site->manager_2_id }}</td>
                    <td>{{ $site->start_time }} - {{ $site->end_time }}</td>
                    <td>Guard: {{ $site->guard_rate }}, Office: {{ $site->office_rate }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
