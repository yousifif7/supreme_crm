<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Client Report</h2>

    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Company</th>
                <th>Manager</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Contract Start</th>
                <th>Contract End</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->client_name }}</td>
                    <td>{{ $client->company->name ?? 'N/A' }}</td>
                    <td>{{ $client->manager ? $client->manager->fore_name . ' ' . $client->manager->sur_name : 'N/A' }}</td>
                    <td>{{ $client->contact_person ?? 'N/A' }}</td>
                    <td>{{ $client->email ?? 'N/A' }}</td>
                    <td>{{ $client->contact_number ?? 'N/A' }}</td>
                    <td>{{ $client->contract_start ? \Carbon\Carbon::parse($client->contract_start)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $client->contract_end ? \Carbon\Carbon::parse($client->contract_end)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $client->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
