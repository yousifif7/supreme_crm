<!DOCTYPE html>
<html>

<head>
    <title>Employees</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 5px;
        }

        th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>
    <h3>Employees</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>SIA</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $e)
                <tr>
                    <td>{{ $e->fore_name }} {{ $e->sur_name }}</td>
                    <td>{{ $e->email }}</td>
                    <td>{{ $e->status }}</td>
                    <td>{{ $e->sia_licence }}</td>
                    <td>{{ $e->contact }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
