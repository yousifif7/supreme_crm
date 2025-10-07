<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Checkpoint Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Checkpoint Report</h2>
    <p><strong>Site:</strong> {{ $selectedSite ? \App\Models\Site::find($selectedSite)?->name : 'All Sites' }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Checkpoint</th>
                <th>Site</th>
                <th>Required</th>
                <th>Latitude</th>
                <th>Longitude</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($checkpoints as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->site->site_name ?? 'N/A' }}</td>
                    <td>{{ $c->required ? 'Yes' : 'No' }}</td>
                    <td>{{ $c->latitude ?? '-' }}</td>
                    <td>{{ $c->longitude ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
