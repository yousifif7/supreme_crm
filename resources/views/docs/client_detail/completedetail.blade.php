<?php $page = "userlist"; ?>
@extends('layouts.app')
@section('contents')
@section('title') Client Detail @endsection

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-header">
                <h4>Complete Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach ($decodedData as $key => $value)
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded shadow-sm bg-light">
                                @php
                                      $dynamicFields = App\Models\Docs\DynamicInputs::where('child_id', 0)->where('id',$key)
                                        ->distinct()
                                        ->first();
                                @endphp
                                <strong>{{ $dynamicFields->title }}</strong>
                                <br>
                                @if (Str::startsWith($value, 'uploads/'))
                                    <a href="{{ asset($value) }}" target="_blank">
                                        <img src="{{ asset($value) }}" alt="Uploaded Image" class="img-fluid rounded" style="max-width: 100px; max-height: 100px;">
                                    </a>
                                @else
                                    <p class="text-muted">{!! nl2br(e($value)) !!}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
