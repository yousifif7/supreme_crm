<?php $page="userlist";?>
@extends('layouts.app')
@section('contents')
@section('title') Client Detail List @endsection
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
    .bx{
        font-size: 40px;
    }
</style>
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-wrapper">
    <div class="content">
        <div class="row">
            @php
                $cat=App\Models\Docs\DigitalForm::get();
                
            @endphp
            @foreach ($cat as $item)
            @php
                $dynamicinput =App\Models\Docs\DigitalFormSubmit::where('form_id',$item->id)->count();
            @endphp
            <div class="col-md-4">
                <a href="{{ route('form_detail.index',$item->id) }}">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">{{ $item->title }}</p>
                                <h4 class="mb-0">{{ $dynamicinput }}</h4>
                            </div>

                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary  p-2">
                                    <span class="avatar-title">
                                        <i class="bx bx-copy-alt font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            </div>
            @endforeach
        </div>
        <!-- /product list -->
    </div>
</div>

@endsection