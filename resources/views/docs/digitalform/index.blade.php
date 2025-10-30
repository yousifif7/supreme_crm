<?php $page = 'userlist'; ?>
@extends('layouts.app')
@section('contents')
@section('title')
    Digital Form List
@endsection
<style>

</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Digital Form</h2>
                @if (session('success'))
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                @endif
                {{-- show validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div>
                @can('digitalform create')
                    <div class="page-btn">
                        <a href="{{ route('digital.form.create') }}" class="btn btn-success">
                            + Add New Digitalform
                        </a>
                    </div>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="" class="table datanew" data-update-route="{{ route('digital.form.order') }}">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>receiver mail</th>
                                @php
                                    $hasActionPermission =
                                        auth()->user()->can('digitalform edit') ||
                                        auth()->user()->can('digitalform delete');
                                @endphp

                                @if ($hasActionPermission)
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="tablecontents">
                            @foreach (App\Models\Docs\DigitalForm::orderBy('order', 'ASC')->get() as $key => $item)
                                <tr class="row1" data-id="{{ $item->id }}">
                                    <td>{!! $key + 1 !!}</td>
                                    <td>{{ $item->title }}</td>
                                    <td>
                                        <input type="checkbox" class="status-toggle-paginate"
                                            data-id="{{ $item->id }}" id="switch{{ $item->id }}" switch="danger"
                                            {{ $item->others == 1 ? 'checked' : '' }} />
                                        <label for="switch{{ $item->id }}" data-on-label="Yes"
                                            data-off-label="No"></label>
                                    </td>
                                    <td>{{ $item->receiver_mail }}</td>
                                    @if ($hasActionPermission)
                                        <td>
                                            @can('digitalform edit')
                                                <a class="btn btn-xs btn-success btn-change"
                                                    href="{{ url('digital/form/edit' . '/' . $item->id) }}">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                            @endcan
                                            @can('dynamicinput')
                                                <a class="btn btn-xs btn-info btn-change"
                                                    href="{{ url('digital/form/field' . '/' . $item->id) }}">
                                                    <i class="fa-solid fa-bars"></i>
                                                </a>
                                            @endcan
                                            @can('digitalform delete')
                                                <form method="POST"
                                                    action="{{ route('digital.form.destroy', $item->id) }}"
                                                    style="display: contents;">
                                                    @csrf
                                                    <input name="_method" type="hidden" value="DELETE">
                                                    <button type="submit"
                                                        class="confirm-text btn btn-xs btn-danger btn-change"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            @endcan
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /product list -->
    </div>
</div>
@endsection
@section('scripts')
@include('docs.partial.script')
@endsection
