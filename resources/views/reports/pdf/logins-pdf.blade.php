<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login Activity Report</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; font-size: 12px; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h3>Login Activity Report</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>User</th>
                <th>Email</th>
                <th>Login At</th>
                <th>Logout At</th>
                <th>Duration (mins)</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $k => $a)
                <tr>
                    <td>{{ $k + 1 }}</td>
                    <td>{{ optional($a->user)->first_name }} {{ optional($a->user)->last_name }}</td>
                    <td>{{ optional($a->user)->email }}</td>
                    <td>{{ optional($a->login_at)->format('Y-m-d H:i:s') ?? '' }}</td>
                    <td>{{ optional($a->logout_at)->format('Y-m-d H:i:s') ?? '' }}</td>
                    <td>@if($a->login_at && $a->logout_at){{ $a->login_at->diffInMinutes($a->logout_at) }}@endif</td>
                    <td>{{ $a->ip_address ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
