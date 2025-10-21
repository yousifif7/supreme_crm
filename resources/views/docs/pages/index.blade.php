<?php $page="userlist";?>
@extends('layouts.app')
@section('contents')
@section('title') Page List @endsection
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="page-wrapper">
    
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Pages</h2>
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
                
                @can('page create')
                <div class="page-btn">
                    <a href="{{ route('page.form.create') }}" class="btn btn-success">
                        + Add New Pages Form
                    </a>
                </div>
                @endcan
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="myTable" class="table datanew" data-update-route="{{ route('digital.form.order') }}">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Form Name</th>
                                @php
                                $hasActionPermission = auth()->user()->can('page edit') ||
                                auth()->user()->can('page delete');
                                @endphp
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tablecontents">
                            @foreach (App\Models\Docs\Page::get() as $key=>$item)
                            @php
                            $digiform=App\Models\Docs\DigitalForm::where('id',$item->select_form_id)->first();
                            
                            @endphp
                            <tr class="row1" data-id="{{ $item->id }}">
                                <td>{!!$key+1!!}</td>
                                <td>{{$item->title}}</td>
                                <td>{{$digiform->title ?? ''}}</td>
                                <td>
                                    <a class="btn btn-xs btn-info btn-change" target="_blank" href="{{route('page.form.design',$item->slug)}}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if($hasActionPermission)
                                    @can('page edit')
                                    <a class="btn btn-xs btn-success btn-change" href="{{url('page/form/edit'.'/'.$item->id)}}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @endcan
                                    @can('page delete')
                                    <form method="POST" action="{{ route('page.form.destroy', $item->id) }}" style="display: contents;">
                                        @csrf
                                        <input name="_method" type="hidden" value="DELETE">
                                        <button type="submit" class="confirm-text btn btn-xs btn-danger btn-change"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                    @endcan
                                    
                                    @endif
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