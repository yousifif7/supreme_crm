<?php $page="userlist";?>
@extends('layouts.app')
@section('contents')
@section('title') Dynamic form List @endsection
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Dynamic Field</h2>
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
                
                @can('dynamicinput create')
                <div class="page-btn">
                    <a href="{{ url('digital/form/field/create/'.$form->id) }}" class="btn btn-success">
                        + Add New Dynamic Input
                    </a>
                </div>
                @endcan
            </div>
        </div>
        
        
        
        <!-- /product list -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="myTable" class="table datanew" data-update-route="{{ route('digital.form.field.order') }}">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>title</th>
                                <th>parent</th>
                                <th>status</th>
                                <th>type</th>
                                <th>required</th>
                                @php
                                $hasActionPermission = auth()->user()->can('dynamicinput edit') ||
                                auth()->user()->can('dynamicinput delete');
                                @endphp
                                
                                @if($hasActionPermission)
                                <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="tablecontents">
                            @foreach ($dynamic as $key=>$item)
                            <tr class="row1" data-id="{{ $item->id }}">
                                <td>{!!$key+1!!}</td>
                                <td>{{ \Illuminate\Support\Str::words($item->title, 5, '...') }}</td>
                                <td>{{$item->parent_digi->title ?? '' }}</td>
                                @if ($item->type == 'checkbox' || $item->type == 'drop' || $item->type == 'radio')
                                <td>
                                    <input type="checkbox" class="status-toggle" data-id="{{ $item->id }}" id="switch{{ $item->id }}"
                                    switch="danger" {{ $item->others ==1 ? 'checked' : '' }} />
                                    <label for="switch{{ $item->id }}" data-on-label="Yes" data-off-label="No"></label>
                                </td>
                                @else
                                <td></td>
                                @endif
                                <td>{{$item->type}}</td>
                                <td>{{ $item->required == 1 ? 'required' : 'no required' }}</td>
                                @if($hasActionPermission)
                                <td>
                                    @can('dynamicinput edit')
                                    <a class="btn btn-xs btn-success btn-change" href="{{url('digital/form/field/edit'.'/'.$item->id)}}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @endcan
                                    @can('dynamicinput')
                                    @if ($item->type == 'checkbox' || $item->type == 'drop' || $item->type == 'radio')
                                    <a class="btn btn-xs btn-info btn-change" href="{{url('digital/form/field/child'.'/'.$item->id)}}">
                                        <i class="fa-solid fa-bars"></i>
                                    </a>
                                    @endif
                                    @endcan
                                    @can('dynamicinput delete')
                                    <form method="POST" action="{{ route('digital.form.field.destroy', $item->id) }}" style="display: contents;">
                                        @csrf
                                        <input name="_method" type="hidden" value="DELETE">
                                        <button type="submit" class="confirm-text btn btn-xs btn-danger btn-change"><i class="fa-solid fa-trash-can"></i></button>
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