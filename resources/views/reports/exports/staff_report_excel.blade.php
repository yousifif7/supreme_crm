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
        @foreach($staff as $s)
        <tr>
            <td>
                @if ($s->model_type == 'employee')
                    {{ $s->fore_name }} {{ $s->sur_name }}
                @elseif ($s->model_type == 'subcontractor')
                    {{ $s->company_name }} ({{ $s->contact_person }})
                @endif
            </td>
            <td>{{ ucfirst($s->model_type) }}</td>
            <td>{{ $s->sia_licence ?? 'N/A' }}</td>
            <td>
                @php
                    $isActive = isset($s->sia_expiry) && \Carbon\Carbon::parse($s->sia_expiry)->isFuture();
                @endphp
                {{ $isActive ? 'Active' : $s->sia_status ?? 'Inactive' }}
            </td>
            <td>{{ isset($s->sia_expiry) ? \Carbon\Carbon::parse($s->sia_expiry)->format('d/m/Y') : 'N/A' }}</td>
            <td>
                @if(isset($s->sia_expiry))
                    @php
                        $daysRemaining = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($s->sia_expiry), false);
                    @endphp
                    {{ $daysRemaining > 0 ? $daysRemaining . ' days' : 'Expired ' . abs($daysRemaining) . ' days ago' }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
