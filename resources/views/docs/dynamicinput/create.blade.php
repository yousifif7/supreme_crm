<?php $page="addproduct";?>
@extends('layouts.app')
@section('contents')
@section('title') Dynamic Form Create @endsection
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
        <form action="{{ route('digital.form.fields.store') }}" method="POST">
            @csrf
            <input type="hidden" name="parent_id" value="{{$create->id}}">
            <div class="card">
                <div class="card-header">
                    <h4>Create Dynamic Fields</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label for="title" class="col-sm-2 col-form-label">
                                            Title<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-sm-10">
                                            <textarea class="form-control" name="title" id="summernote" style="height:50px">{{ old('title') }}</textarea>
                                            
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="info">Info</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="info" name="info" placeholder="{{ ('Info') }}">
                                            <small class="form-text text-muted">Info text will display under field</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="placeholder">Placeholder</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="placeholder" name="placeholder" placeholder="{{ ('Placeholder') }}">
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="placeholder">Value</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="placeholder" name="value" placeholder="{{ ('Placeholder') }}">
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="desc">Description</label>
                                        <div class="col-sm-10">
                                            <small class="form-text text-muted">Description will display above the field</small>
                                            <input type="text" class="form-control" id="desc" name="desc" placeholder="{{ ('Description') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="type">{{ ('Data Type') }}</label>
                                        <div class="col-sm-10">
                                            <select class="js-example-basic-single form-control" name="type" required>
                                                @php
                                                $types = [
                                                'text' => 'Single Line Text',
                                                'time' => 'Time',
                                                'email' => 'Email Address',
                                                'number' => 'Number',
                                                'password' => 'Password',
                                                'checkbox' => 'Boolean (Checkbox)',
                                                'radio' => 'Radio buttons list',
                                                'date' => 'Date',
                                                'color' => 'Color',
                                                'file' => 'File',
                                                'hidden' => 'Hidden',
                                                'tel' => 'Mobile Number',
                                                'url' => 'Link URL',
                                                'textarea' => 'Textarea',
                                                'drop' => 'Drop Down List',
                                                'heading' => 'Heading Only',
                                                'Integer_value' => 'Integer Value (Without decimal places)',
                                                'decimal_value' => 'Decimal Value',
                                                'Paragraph' => 'Paragraph',
                                                ];
                                                @endphp
                                                @foreach($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="min_limit_check">Has Min Character Limit</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="min_limit_check" name="min_limit_check">
                                            <small class="form-text text-muted">Not Applied to 'Date', 'Bool', 'HTML' and 'File' Type fields</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="min_limit_input">Min Character Limit</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="min_limit_input" name="min_limit_input" placeholder="{{ ('Min Character Limit') }}">
                                            <small class="form-text text-muted">Applied only if 'Has Min Character Limit' is checked</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="max_limit_check">Has Max Character Limit</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="max_limit_check" name="max_limit_check">
                                            <small class="form-text text-muted">Not Applied to 'Date', 'Bool', 'HTML' and 'File' Type fields</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="max_limit_input">Max Character Limit</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="max_limit_input" name="max_limit_input" placeholder="{{ ('Max Character Limit') }}">
                                            <small class="form-text text-muted">Applied only if 'Has Max Character Limit' is checked</small>
                                        </div>
                                    </div>
                                </div>
                                
                                {{--  <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="send_email">Send Email}</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="send_email" name="send_email">
                                            <small class="form-text text-muted">Only for Email Type field (Used to send email to user)</small>
                                        </div>
                                    </div>
                                </div>
                                --}}
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="lebelstatus">label status</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="lebelstatus" name="label_status">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="unique">Unique</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="unique" name="unique">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="required">Required</label>
                                        <div class="col-sm-10 pt-6">
                                            <input type="checkbox" class="form-check-input" id="required" name="required">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="requiredheader">Header on/off</label>
                                        <div class="col-sm-10 pt-6">
                                            <select class="form-control" name="header_status">
                                                <option value="other">Others</option>
                                                <option value="0">Table</option>
                                                <option value="1">Header</option>
                                                <option value="2">Footer</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-submit p-2 btn-primary">Save</button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </form>
        
        
        
        <!-- /add -->
    </div>
</div>
@endsection
@section('js-script')
@endsection