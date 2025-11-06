@extends('layouts.app')
@section('title','Client Dashboard')
@section('contents')
<div class="page-wrapper">
    <div class="content">

        <h3>Welcome, {{auth()->user()->name}}</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Total Invoices</h6>
                    <h3>{{ $invoicesCount ?? 0 }}</h3>
                    <a href="{{ route('client.invoices.index') }}">View Invoices</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Outstanding</h6>
                    <h3>{{ number_format($outstanding ?? 0,2) }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Sites</h6>
                    <h3>{{ $sitesCount ?? 0 }}</h3>
                    <a href="{{ route('client.sites.index') }}">Manage Sites</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Upcoming Shifts</h6>
                    <ul class="list-unstyled">
                        @forelse($upcomingShifts as $s)
                            <li>{{ $s->shift->site->site_name ?? 'N/A' }} — {{ format_date($s->shift_date) }}</li>
                        @empty
                            <li>No upcoming shifts</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('client.rota') }}">View rota</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
