<!DOCTYPE html>
<html>

<head>
    <title>Clients PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Client List</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Contact Number</th>
                <th>Fax</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->client_name }}</td>
                    <td>{{ $client->address }}</td>
                    <td>{{ $client->contact_number }}</td>
                    <td>{{ $client->fax }}</td>
                    <td>{{ $client->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
