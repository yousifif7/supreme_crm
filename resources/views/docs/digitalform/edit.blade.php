<?php $page="addproduct";?>
@extends('layouts.app')
@section('contents')
@section('title') Digital Form Update @endsection

<style>
    [dir="ltr"] {
    direction: ltr;
    text-align: left;
}
</style>

<div class="page-wrapper">
    <div class="content">
        <!-- /add -->
    <form action="{{route('digital.form.update')}}" class="form" method="POST" enctype="multipart/form-data">
         @csrf
         <input type="hidden" name="id" value="{{$career->id}}">
        
        <div class="card">
            <div class="card-header">
                <h4> Update Digital Form</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label for="title" class="col-sm-2 col-form-label">Name <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input class="form-control slug-input" type="text" name="title" placeholder="Enter name" value="{{ old('title', $career->title) }}">
                            </div>
                        </div>
                    </div>
        
                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="name">Add Description<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <textarea class="form-control basic-conf" placeholder="Description.." name="desc" style="height: 150px">{{ old('desc', $career->desc) }}</textarea>
                            </div>
                        </div>
                    </div>
                    

                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="name">Add Mail Message<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <textarea class="form-control basic-conf" placeholder="Description.." name="mail_desc" style="height: 150px" id="summernotemail">{{ old('desc', $career->mail_desc) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
        
                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="name">Success Message<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="success_message" placeholder="Enter Success Message" value="{{ old('success_message', $career->success_message) }}">
                            </div>
                        </div>
                    </div>
        
                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="name">Failure Message<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="failure_message" placeholder="Enter Failure Message" value="{{ old('failure_message', $career->failure_message) }}">
                            </div>
                        </div>
                    </div>
        
                    <div class="col-md-12">
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="name">Receiver mail<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="receiver_mail" placeholder="Enter Receiver mail" value="{{ $career->receiver_mail }}">
                            </div>
                        </div>
                    </div>
                    

                  <!-- Toggle Switch -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="switchheader" name="header_status" {{ $career->header_status ? 'checked' : '' }}>
                        <label class="form-check-label" for="switchheader">Enable Invoice Fields?</label>
                    </div>
                
                    <!-- Invoice Fields -->
                    <div id="invoiceFields" style="{{ $career->header_status == 0 ? 'display: none;' : '' }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Invoice To</label>
                                <input type="text" class="form-control" name="invoice_to" value="{{ $career->invoice_to }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Invoice From</label>
                                <input type="text" class="form-control" name="invoice_from" value="{{ $career->invoice_from }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">SIA</label>
                                <input type="text" class="form-control" name="sia" value="{{ $career->sia }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">VAT</label>
                                <input type="text" class="form-control" name="vat" value="{{ $career->vat }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tax Date</label>
                                <input type="date" class="form-control" name="tax_date" value="{{ $career->tax_date }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Invoice Number</label>
                                <input type="text" class="form-control" name="invoice_number" value="{{ $career->invoice_number }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Terms</label>
                                <input type="text" class="form-control" name="terms" value="{{ $career->terms }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" value="{{ $career->due_date }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Invoice</label>
                                <input type="date" class="form-control" name="invoice_date" value="{{ $career->invoice_date }}">
                            </div>
                        </div>
                    </div>
                

                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-submit p-2 btn-primary">Update</button>
                </div>
            </div>
        </div>
    </form>
        <!-- /add -->
    </div>
</div>		
@endsection	  
@section('scripts')
   
        <script>
        $(document).ready(function() {
        $('#summernotemail').summernote();
    });
      </script>
<script>
    $(document).ready(function () {
        $('#switchheader').change(function () {
            if ($(this).is(':checked')) {
                $('#invoiceFields').slideDown();
            } else {
                $('#invoiceFields').slideUp();
            }
        });
    });
</script>
@endsection