@php
    $approval = $approval ?? 'pending';
    $map = [
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending'  => 'bg-warning',
    ];
    $cls = $map[$approval] ?? 'bg-warning';
    $textCls = $approval === 'pending' ? 'text-dark' : 'text-white';
@endphp
<span class="status-pill {{ $cls }} {{ $textCls }}">{{ ucfirst($approval) }}</span>
