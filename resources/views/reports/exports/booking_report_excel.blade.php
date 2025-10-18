<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Client</th>
            <th>Site</th>
            <th>Type</th>
            <th>Shift Date</th>
            <th>Timestamp</th>
            <th>Address</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($bookings as $index => $booking)
                <tr>
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
                    <td>{{ $booking->shift?->shift_date ? format_date($booking->shift?->shift_date) : 'N/A' }}</td>
                                    <td>
                    @if (!empty($booking->timestamp))
                        {{ \Carbon\Carbon::parse($booking->timestamp)->format('d/m/Y H:i') }}
                    @else
                        N/A
                    @endif
                    </td>
                    <td>{{ $booking->address ?? 'N/A' }}</td>
                </tr>
        @endforeach
    </tbody>
</table>
