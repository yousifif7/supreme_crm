@extends('layouts.app')
@section('contents')
@section('title') Page Create @endsection
<style>
[dir="ltr"] {
direction: ltr;
text-align: left;
}
</style>
<div class="page-wrapper">
    <div class="content">
        
        <!-- /add -->
        <form action="{{route('page.form.store')}}" class="form" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h4> Create Page Form</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label for="title" class="col-sm-2 col-form-label">Name <span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                            <input class="form-control name-input" type="text" name="title" placeholder="Enter name" value="{{ old('name') }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label for="slug" class="col-sm-2 col-form-label">Link <span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                            <input class="form-control slug-input" type="text" name="slug" placeholder="Slug" value="{{ old('link') }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="name">Add Description<span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                            <textarea id="summernote" class="form-control basic-conf" placeholder="Description.." name="desc" style="height: 150px"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="longdesc">Add Description<span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                            <textarea id="summernote1" class="form-control basic-conf" placeholder="Description.." name="longdesc" style="height: 150px"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label" for="name">Select Form<span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                            <select name="select_form" id="" class="form-control">
                                                @foreach (App\Models\Docs\DigitalForm::get() as $item)
                                                <option value="{{ $item->id }}">{{ $item->title }}</option>
                                                @endforeach
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
        </div>
    </form>
    <!-- /add -->
</div>
</div>
@endsection