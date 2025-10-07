<!DOCTYPE html>
<html>

<head>
    <title>Materials PDF</title>
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
    <h2>Materials List</h2>
    <table>
        <thead>
            <tr>
                <th>Material No</th>
                <th>Material Title</th>
                <th>Material Description</th>
                <th>Material Type</th>
                <th>Implementation Date</th>
                <th>Deadline</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($materials as $material)
                <tr>
                    <td>{{ $material->id }}</td>
                    <td>{{ $material->title }}</td>
                    <td>{{ $material->description }}</td>
                    <td>{{ $material->type }}</td>
                    <td>{{ $material->implementation_date }}</td>
                    <td>{{ $material->deadline }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
