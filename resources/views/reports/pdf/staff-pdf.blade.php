<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        h2 { text-align: center; margin-bottom: 10px; }
        .badge { padding: 2px 5px; color: #fff; border-radius: 3px; }
        .bg-primary { background: #0d6efd; }
        .bg-info { background: #0dcaf0; }
        .text-primary { color: #0d6efd; font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .bg-success { background: #198754; }
    </style>
</head>
<body>
    <h2>Staff Report</h2>
    <p>Generated: {{ now()->format('d M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Name / Company</th>
                <th>Type</th>
                <th>SIA Licence</th>
                <th>Status</th>
                <th>Expiry Date</th>
                <th>Days Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staff as $s)
                <tr>
                    <td>
                        @if ($s->model_type == 'employee')
                            {{ $s->fore_name }} {{ $s->sur_name }}
                        @elseif ($s->model_type == 'subcontractor')
                            {{ $s->company_name }} ({{ $s->contact_person }})
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $s->model_type == 'employee' ? 'primary' : 'info' }}">
                            {{ ucfirst($s->model_type) }}
                        </span>
                    </td>
                    <td>{{ $s->sia_licence ?? 'N/A' }}</td>
                    <td>
                        @php
                            $isActive = isset($s->sia_expiry) && \Carbon\Carbon::parse($s->sia_expiry)->isFuture();
                        @endphp
                        <span class="{{ $isActive ? 'text-primary' : 'text-danger' }} fw-bold">
                            {{ $isActive ? 'Active' : $s->sia_status ?? 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ isset($s->sia_expiry) ? \Carbon\Carbon::parse($s->sia_expiry)->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        @if (isset($s->sia_expiry))
                            @php
                                $daysRemaining = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($s->sia_expiry), false);
                            @endphp
                            @if ($daysRemaining > 0)
                                <span class="badge bg-success">{{ $daysRemaining }} days</span>
                            @else
                                <span class="badge bg-danger">Expired {{ abs($daysRemaining) }} days ago</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No staff found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
