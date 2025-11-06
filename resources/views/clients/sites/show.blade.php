@extends('layouts.app')
@section('title', 'Site Details')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <h3>{{ $site->site_name }}</h3>
                <p>{{ $site->address }}</p>
                <p>Contact: {{ $site->contact_person }} ({{ $site->contact_number }})</p>
            </div>
        </div>
    </div>
</div>
@endsection
