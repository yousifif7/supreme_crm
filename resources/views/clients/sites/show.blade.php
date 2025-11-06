@extends('layouts.app')
@section('title', 'Site Details')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h3>{{ $site->site_name }}</h3>
                        <small class="text-muted">Code: {{ $site->site_code ?? '-' }}</small>
                    </div>
                    <div>
                        <a href="{{ route('client.sites.index') }}" class="btn btn-secondary">&larr; Go back</a>
                    </div>
                </div>

                <div class="mb-2"><strong>Address:</strong> {{ $site->address ?? '-' }}</div>
                <div class="mb-2"><strong>Post Code:</strong> {{ $site->post_code ?? '-' }}</div>
                <div class="mb-2"><strong>Contact Person:</strong> {{ $site->contact_person ?? '-' }}</div>
                <div class="mb-2"><strong>Contact Number:</strong> {{ $site->contact_number ?? '-' }}</div>
                <div class="mb-2"><strong>Guard Names:</strong> {{ $site->guard_names ?? '-' }}</div>
                <div class="mb-2"><strong>Note:</strong> {{ $site->note ?? '-' }}</div>
                <div class="mb-2"><strong>Manager 1:</strong> {{ optional($site->manager1)->name ?? $site->manager_1_id ?? '-' }}</div>
                <div class="mb-2"><strong>Manager 2:</strong> {{ optional($site->manager2)->name ?? $site->manager_2_id ?? '-' }}</div>
                <div class="mb-2"><strong>Start Time:</strong> {{ $site->start_time ?? '-' }}</div>
                <div class="mb-2"><strong>End Time:</strong> {{ $site->end_time ?? '-' }}</div>
                <div class="mb-2"><strong>Break Time (mins):</strong> {{ $site->break_time ?? '-' }}</div>
                <div class="mb-2"><strong>Guard Rate:</strong> {{ $site->guard_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Office Rate:</strong> {{ $site->office_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Billable Rate:</strong> {{ $site->billable_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Payable Rate:</strong> {{ $site->payable_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Has QR:</strong> {{ $site->has_qr ? 'Yes' : 'No' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
