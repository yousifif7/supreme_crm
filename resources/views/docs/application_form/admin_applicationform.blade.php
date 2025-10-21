
@extends('layouts.app')
@section('contents')		
@section('title') Application Form @endsection
<div class="page-wrapper">
    <div class="content">
          <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Application Form</h2>
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

            </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="myTable" class="table datanew" data-update-route="{{ route('digital.form.order') }}">
                        <thead>
                            <tr>

                                <th>#</th>
                                <th>Name</th>
                                <th>Position Applied</th>
                                <th>Post Code</th>
                                @php
                                    $hasActionPermission = auth()->user()->can('digitalform edit') || 
                                                           auth()->user()->can('digitalform delete');
                                @endphp
                
                                @if($hasActionPermission)
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="tablecontents">
                              @foreach (App\Models\Docs\Application_Form::get() as $key=>$item)
                            <tr class="row1" data-id="{{ $item->id }}">
                                <td>{!!$key+1!!}</td>
                                <td>{{$item->title}}</td>
                                <td>
                                    {{$item->position_applied}}
                                </td>

                                <td>{{$item->post_code}}</td>
                                @if($hasActionPermission)
                                <td>
                                     <button type="button"
                                            class="btn btn-xs btn-primary copy-link"
                                            data-link="https://documents.voags.com/application/form/create">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                    @can('digitalform edit')
                                    <a class="btn btn-xs btn-success btn-change" href="{{url('application/form/edit'.'/'.$item->id)}}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
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
<script>
document.addEventListener('click', function (e) {
    if (e.target.closest('.copy-link')) {
        let btn = e.target.closest('.copy-link');
        let link = btn.getAttribute('data-link');

        navigator.clipboard.writeText(link).then(() => {
            // Optional: show alert/toastr
            alert('Link copied: ' + link);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
});
</script>

@endsection
