<?php $page="addproduct";?>
@extends('layouts.app')
@section('contents')		
@section('title') Dynamic form Category Update @endsection

<style>

    [dir="ltr"] {
        direction: ltr;
        text-align: left;
    }
    .form-check-input {
        width: 0.9rem;
        height: 0.9rem;
        background-color: #ffffff;
        border: 1px solid #8b8e95;
    }
    .pt-6{
        padding-top: 6px;
    }
    </style>
<div class="page-wrapper">
    <div class="content">
        <!-- /add -->
        <form action="{{ route('digital.form.field.update.child') }}" method="POST">
            @csrf
            <input type="hidden" name="child_id" value="{{$menus->child_id}}">
            <input type="hidden" name="id" value="{{$menus->id}}">
            <input type="hidden" name="type" value="{{$menus->type}}">
            <input type="hidden" name="parent_id" value="{{$menus->parent_id}}">

            <div class="card">
                <div class="card-header">
                    <h5>UPdate new Input Child</h5>
                </div>
                <div class="card-body">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label" for="title">{{ ('Title') }}</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="title" name="title" value="{{ $menus->title }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-submit p-2 btn-primary">{{ ('Submit') }}</button>
                    </div>
                </div>
            </div>
            
        </form>
        
        
        
        <!-- /add -->
    </div>
</div>		
@endsection
