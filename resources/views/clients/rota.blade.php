@extends('layouts.app')
@section('title','Rota')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <h4>Rota / Schedule</h4>
                <table class="table">
                    <thead>
                        <tr><th>Site</th><th>Date</th><th>Staff</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $s)
                            <tr>
                                <td>{{ $s->shift->site->site_name ?? 'N/A' }}</td>
                                <td>{{ format_date($s->shift_date) }}</td>
                                <td>{{ $s->staff?->first_name ?? 'Unassigned' }} {{ $s->staff?->last_name ?? '' }}</td>
                                <td>{{ optional(\Carbon\Carbon::createFromFormat('H:i:s', $s->start_time))->format('h:i A') ?? '' }} - {{ optional(\Carbon\Carbon::createFromFormat('H:i:s', $s->end_time))->format('h:i A') ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
