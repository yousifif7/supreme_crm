<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Shift Booking Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 15px;
            color: #222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #888;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            color: #fff;
            font-size: 11px;
            text-transform: capitalize;
        }
        .bg-success { background-color: #28a745; }
        .bg-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
    <h2>Shift Booking Report</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Client</th>
                <th>Site</th>
                <th>Type</th>
                <th>Shift Date</th>
                <th>Timestamp</th>
                <th>Face Verification</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $index => $booking)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $booking->shift?->staff?->first_name ?? '' }}
                        {{ $booking->shift?->staff?->last_name ?? '' }}
                    </td>
                    <td>{{ $booking->shift?->shift?->client?->name ?? 'N/A' }}</td>
                    <td>{{ $booking->shift?->shift?->site?->site_name ?? 'N/A' }}</td>
                    <td>
                        @php
                            $type = $booking->type ?? 'N/A';
                            $badgeClass = $type === 'book_on' ? 'bg-success' : 'bg-secondary';
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </span>
                    </td>
                    <td>{{ $booking->shift?->shift_date ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($booking->timestamp)->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($booking->face_verification_result ?? 'N/A') }}</td>
                    <td>{{ $booking->address ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">No bookings found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="text-align: right; font-size: 11px; color: #777;">
        Generated on {{ now()->format('d M Y, H:i') }}
    </p>
</body>
</html>
