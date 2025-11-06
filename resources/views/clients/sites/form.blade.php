@extends('layouts.app')
@section('title', isset($site) && $site->id ? 'Edit Site' : 'Add Site')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ isset($site) && $site->id ? route('client.sites.update', $site->id) : route('client.sites.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $site->site_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Code</label>
                            <input type="text" name="site_code" class="form-control" value="{{ old('site_code', $site->site_code ?? '') }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control">{{ old('address', $site->address ?? '') }}</textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Post Code</label>
                            <input type="text" name="post_code" class="form-control" value="{{ old('post_code', $site->post_code ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $site->contact_person ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $site->contact_number ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guard Names (comma separated)</label>
                            <input type="text" name="guard_names" class="form-control" value="{{ old('guard_names', $site->guard_names ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control" value="{{ old('note', $site->note ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manager 1 (user id)</label>
                            <input type="number" name="manager_1_id" class="form-control" value="{{ old('manager_1_id', $site->manager_1_id ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manager 2 (user id)</label>
                            <input type="number" name="manager_2_id" class="form-control" value="{{ old('manager_2_id', $site->manager_2_id ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', isset($site->start_time) ? date('H:i', strtotime($site->start_time)) : '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', isset($site->end_time) ? date('H:i', strtotime($site->end_time)) : '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Break Time (minutes)</label>
                            <input type="number" name="break_time" class="form-control" value="{{ old('break_time', $site->break_time ?? '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Guard Rate</label>
                            <input type="number" step="0.01" name="guard_rate" class="form-control" value="{{ old('guard_rate', $site->guard_rate ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Office Rate</label>
                            <input type="number" step="0.01" name="office_rate" class="form-control" value="{{ old('office_rate', $site->office_rate ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Billable Rate</label>
                            <input type="number" step="0.01" name="billable_rate" class="form-control" value="{{ old('billable_rate', $site->billable_rate ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Payable Rate</label>
                            <input type="number" step="0.01" name="payable_rate" class="form-control" value="{{ old('payable_rate', $site->payable_rate ?? '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Has QR</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_qr" value="1" {{ old('has_qr', $site->has_qr ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Enable</label>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
