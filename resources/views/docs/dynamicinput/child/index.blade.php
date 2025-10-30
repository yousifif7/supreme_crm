<?php $page = 'userlist'; ?>
@extends('layouts.app')
@section('contents')
@section('title')
    Dynamic form Category List
@endsection
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    #clientdetailTable th:last-child,
    td:last-child {
        text-align: center !important;
    }
</style>
<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Inputs</h2>
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
                    <a href="{{ route('digital.form.fields.create.child', $ids->id) }}" class="btn btn-success">
                        + Add New input
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-arrow mb-0">
                        <!-- Dashboard as the parent breadcrumb -->
                        <li class="breadcrumb-item"><a href="{{ route('digital.form.fields', $ids->parent_id) }}">Dynamic
                                Input</a></li>

                        <!-- Dynamic Child - based on specific input ID -->
                        <li class="breadcrumb-item active" aria-current="page"> /
                            {{ $ids->title ?? 'Default Title' }} <!-- Display the child title dynamically -->
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- /product list -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="myTable" class="table datanew"
                        data-update-route="{{ route('digital.form.field.child.order') }}">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ 'Name' }}</th>
                                <th>{{ 'Type' }}</th>
                                <th>{{ 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody id="tablecontents">
                            @foreach ($dynamic as $key => $input)
                                <tr class="row1" data-id="{{ $input->id }}">
                                    <td>{!! $key + 1 !!}</td>
                                    <td>{{ $input->title }}</td>
                                    <td>{{ $input->type }}</td>
                                    <td>
                                        {{-- @if ($input->type == 'checkbox' || $input->type == 'drop')
                                    <a href="{{url('dynamic-inputs-child'.'/'.$input->id)}}" class="btn btn-xs" data-toggle="tooltip" title="View Child">
                                        <i class="fe fe-eye"></i>
                                    </a>
                                    @endif --}}

                                        <a class="btn btn-xs btn-success btn-change"
                                            href="{{ route('digital.form.field.edit.child', $input->id) }}">
                                            <i class="fa-solid fa-pen-to-square"></i>

                                        </a>
                                        <form method="POST"
                                            action="{{ route('digital.form.field.destroy.child', $input) }}"
                                            style="display: contents;">
                                            @csrf
                                            <input name="_method" type="hidden" value="DELETE">
                                            <button type="submit"
                                                class="confirm-text btn btn-xs btn-danger btn-change">
                                                <i class="fa-solid fa-trash-can" data-toggle="tooltip"
                                                    title="Client Delete"></i>
                                            </button>
                                        </form>
                                    </td>
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
