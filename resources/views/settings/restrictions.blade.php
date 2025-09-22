@extends('layouts.app')
@section('title', 'Resrictions')
@section('contents')
<div id="roles-wrapper" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div><h3>Manage Restrictions</h3>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Entity</th>
                <th>Field</th>
                <th>Type</th>
                <th>Message</th>
                <th>Status</th>
                <th>Toggle</th>
            </tr>
        </thead>
        <tbody>
        @foreach($restrictions as $index=> $restriction)
            <tr>
                <td>{{ $index + 1}}</td>
                <td>{{ class_basename($restriction->entity_type) }}</td>
                <td>{{ $restriction->field_name }}</td>
                <td>{{ $restriction->restriction_type }}</td>
                <td>{{ $restriction->error_message }}</td>
                <td>
                    @if($restriction->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('restrictions.toggle', $restriction->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            {{ $restriction->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
