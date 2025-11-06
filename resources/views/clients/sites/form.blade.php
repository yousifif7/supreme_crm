@extends('layouts.app')
@section('title', isset($site) && $site->id ? 'Edit Site' : 'Add Site')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ isset($site) && $site->id ? route('client.sites.update', $site->id) : route('client.sites.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $site->site_name ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control">{{ old('address', $site->address ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $site->contact_person ?? '') }}">
                    </div>
                    <button class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
