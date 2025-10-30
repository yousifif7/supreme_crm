<?php $page = 'addproduct'; ?>
@extends('layouts.app')
@section('contents')
@section('title')
    Dynamic Form Update
@endsection

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

    .pt-6 {
        padding-top: 6px;
    }
</style>
<div class="page-wrapper">
    <div class="content">

        <!-- /add -->
        <form action="{{ route('digital.form.field.update') }}" method="POST">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $dynamicInput->parent_id }}">
            <input type="hidden" name="id" value="{{ $dynamicInput->id }}">
            <div class="">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card bg-white">
                                <div class="card-header">
                                    <h4>Update Dynamic Field</h4>
                                </div>
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" " for="title">
                                                    {{ 'Title' }}
                                                </label>
                                                @php
                                                    // Explode the title into an array using <br> as the separator
                                                    $tags = explode('<br>', $dynamicInput->title);
                                                    // Join the array into a comma-separated string
                                                    $tagsString = implode(',', $tags);
                                                @endphp


                                                <div class="col-sm-10">
                                                            <textarea class="form-control" name="title" style="height="50px" id="summernote">{!! old('title', $tagsString) !!}</textarea>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label"  for="info">{{ 'Info' }}</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="info" name="info" placeholder="{{ 'Info' }}"
                                                    value="{{ $dynamicInput->info }}">
                                                    <small class="form-text text-muted">Info text will display under field</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" "
                                                    for="placeholder">{{ 'Placeholder' }}</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="placeholder"
                                                        name="placeholder" placeholder="{{ 'Placeholder' }}"
                                                        value="{{ $dynamicInput->placeholder }}">
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" for="placeholder">Value</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="placeholder"
                                                        name="value" placeholder="{{ 'value' }}"
                                                        value="{{ $dynamicInput->value }}">
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" " for="desc">{{ 'Description' }}</label>
                                                <div class="col-sm-10">
                                                    <small class="form-text text-muted">Description will display Top field</small>
                                                    <input type="text" class="form-control" id="desc" name="desc" placeholder="{{ 'Description' }}" value="{{ $dynamicInput->desc }}">
                                                        
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="row">
                                        
                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" for="type">{{ 'Data Type' }}</label>
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
                                                                'Integer_value' =>
                                                                    'Integer Value (Without decimal places)',
                                                                'decimal_value' => 'Decimal Value',
                                                                'Paragraph' => 'Paragraph',
                                                            ];
                                                        @endphp
                                                         @foreach ($types as $value=>
                                                    $label)
                                                    <option value="{{ $value }}"
                                                        {{ $dynamicInput->type === $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                    @endforeach
                                                    </select>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="min_limit_check">{{ 'Has Min Character Limit' }}</label>
                                            <div class="col-sm-10 pt-6">
                                                <input type="checkbox" class="form-check-input" id="min_limit_check"
                                                    name="min_limit_check" value="1"
                                                    {{ isset($dynamicInput) && $dynamicInput->min_limit_check ? 'checked' : '' }}>
                                                <small class="form-text text-muted">Not Applied to 'Date', 'Bool',
                                                    'HTML' and 'File' Type fields</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="min_limit_input">{{ 'Min Character Limit' }}</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="min_limit_input"
                                                    name="min_limit_input"
                                                    placeholder="{{ 'Min Character Limit' }}"
                                                    value="{{ $dynamicInput->min_limit_input }}">
                                                <small class="form-text text-muted">Applied only if 'Has Min Character
                                                    Limit' is checked</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="max_limit_check">{{ 'Has Max Character Limit' }}</label>
                                            <div class="col-sm-10 pt-6">
                                                <input type="checkbox" class="form-check-input" id="max_limit_check"
                                                    name="max_limit_check" value="1"
                                                    {{ isset($dynamicInput) && $dynamicInput->max_limit_check ? 'checked' : '' }}>
                                                <small class="form-text text-muted">Not Applied to 'Date', 'Bool',
                                                    'HTML' and 'File' Type fields</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="max_limit_input">{{ 'Max Character Limit' }}</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="max_limit_input"
                                                    name="max_limit_input"
                                                    placeholder="{{ 'Max Character Limit' }}"
                                                    value="{{ $dynamicInput->max_limit_input }}">
                                                <small class="form-text text-muted">Applied only if 'Has Max Character
                                                    Limit' is checked</small>
                                            </div>
                                        </div>
                                    </div>



                                    {{--    <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label" for="send_email">{{ ('Send Email') }}</label>
                                                <div class="col-sm-10 pt-6">
                                                    <input type="checkbox" class="form-check-input" id="send_email" name="send_email" value="1" {{ isset($dynamicInput) && $dynamicInput->send_email ? 'checked' : '' }}>
                                                    <small class="form-text text-muted">Only for Email Type field (Used to send email to user)</small>
                                                </div>
                                            </div>
                                        </div>
                                        --}}
                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="lebelstatus">{{ 'label status' }}</label>
                                            <div class="col-sm-10 pt-6">
                                                <input type="checkbox" class="form-check-input" id="lebelstatus"
                                                    name="label_status" value="1"
                                                    {{ isset($dynamicInput) && $dynamicInput->label_status ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="unique">{{ 'Unique' }}</label>
                                            <div class="col-sm-10 pt-6">
                                                <input type="checkbox" class="form-check-input" id="unique"
                                                    name="unique" value="1"
                                                    {{ isset($dynamicInput) && $dynamicInput->unique ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label"
                                                for="required">{{ 'Required' }}</label>
                                            <div class="col-sm-10 pt-6">
                                                <input type="checkbox" class="form-check-input" id="required"
                                                    name="required" value="1"
                                                    {{ isset($dynamicInput) && $dynamicInput->required ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label" for="requiredheader">Header
                                                on/off</label>
                                            <div class="col-sm-10 pt-6">
                                                <select class="form-control" name="header_status">
                                                    <option value="other"
                                                        @if ($dynamicInput->header_status == 'other') selected @endif>Others
                                                    </option>
                                                    <option value="0"
                                                        @if ($dynamicInput->header_status == '0') selected @endif>Table</option>
                                                    <option value="1"
                                                        @if ($dynamicInput->header_status == '1') selected @endif>Header
                                                    </option>
                                                    <option value="2"
                                                        @if ($dynamicInput->header_status == '2') selected @endif>Footer
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-submit p-2 btn-primary">Submit</button>
                                </div>
                            </div>
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
