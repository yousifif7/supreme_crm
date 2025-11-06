@extends('layouts.app')
@section('title','My Invoices')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive custom-datatable-filter">
                    {!! $dataTable->table(['class' => 'table datatable']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    {!! $dataTable->scripts() !!}
@endsection
