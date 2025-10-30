<?php $page = 'userlist'; ?>
@extends('layouts.app')
@section('contents')
@section('title')
    Incident Form List
@endsection

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Incident Data</h2>
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
                <div class="page-btn">
                    <a href="{{ url('form/incident') }}" class="btn btn-success">
                        + I Incident User Form
                    </a>
                </div>
            </div>

        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="myTable" class="table datanew" data-update-route="{{ route('digital.form.order') }}">
                        <thead>
                            <tr>

                                <th>#</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Job Title</th>
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
                            @foreach (App\Models\Docs\Incident::get() as $key => $item)
                                <tr class="row1" data-id="{{ $item->id }}">
                                    <td>{!! $key + 1 !!}</td>
                                    <td>{{ $item->first_name }}</td>
                                    <td>{{ $item->last_name }}</td>
                                    <td>{{ $item->job_title }}</td>
                                    @if ($hasActionPermission)
                                        <td>

                                            @can('incident form edit')
                                                <a class="btn btn-xs btn-info btn-change"
                                                    href="{{ url('User/form/incident/data/view' . '/' . $item->id) }}">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
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
