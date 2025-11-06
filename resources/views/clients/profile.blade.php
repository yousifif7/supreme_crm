@extends('layouts.app')
@section('title','Client Profile')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('client.profile.update') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="client_name" class="form-control" value="{{ old('client_name', $client->client_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', optional($client)->email ?? auth()->user()->email) }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $client->contact_person ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $client->contact_number ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $client->address ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice Terms</label>
                            <input type="text" name="invoice_terms" class="form-control" value="{{ old('invoice_terms', $client->invoice_terms ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $client->payment_terms ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Guard Rate</label>
                            <input type="number" step="0.01" name="guard_rate" class="form-control" value="{{ old('guard_rate', $client->guard_rate ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Office Rate</label>
                            <input type="number" step="0.01" name="office_rate" class="form-control" value="{{ old('office_rate', $client->office_rate ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">VAT</label>
                            <input type="text" name="vat" class="form-control" value="{{ old('vat', $client->vat ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contract Start</label>
                            <input type="date" name="contract_start" class="form-control" value="{{ old('contract_start', isset($client->contract_start) ? date('Y-m-d', strtotime($client->contract_start)) : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contract End</label>
                            <input type="date" name="contract_end" class="form-control" value="{{ old('contract_end', isset($client->contract_end) ? date('Y-m-d', strtotime($client->contract_end)) : '') }}">
                        </div>

                    </div>

                    <button class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
