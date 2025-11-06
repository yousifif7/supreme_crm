@extends('layouts.app')
@section('title','My Sites')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="d-flex justify-content-between mb-2">
            <h3>My Sites</h3>
            <a href="{{ route('client.sites.create') }}" class="btn btn-primary">Add Site</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>#</th><th>Name</th><th>Address</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($sites as $site)
                            <tr>
                                <td>{{ $site->id }}</td>
                                <td>{{ $site->site_name }}</td>
                                <td>{{ $site->address }}</td>
                                <td>
                                    <a href="{{ route('client.sites.show', $site->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('client.sites.edit', $site->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
