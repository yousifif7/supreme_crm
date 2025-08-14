@extends('layouts.app')
@section('title', 'CRM - Employee')
@section('contents')
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Document Report</h2>
                </div>

            </div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                       {{--  <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('employees.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('employees.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul> --}}
                    </div>
                </div>
                <div class="me-2 mb-2 filter_area">

                  

                   
                </div>
            </div>
            <!-- /Breadcrumb -->
            <div class="card">
                <div class="card-body">
                    
<form method="GET" action="{{ route('documents.report') }}">
    <div class="row">
        <div class="col-md-3">
            <label>Start Date:</label>
    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
    
    
        </div>
        <div class="col-md-3">
            <label>End Date:</label>
    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
    
        </div>
        <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>

        </div>
    </div>
</form>
                </div>
            </div>

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">




@if($documents->count())
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Document Type</th>
                <th>Description</th>
                <th>Status</th>
                <th>Expiry Date</th>
                <th>Uploaded At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $index => $doc)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $doc->user->name ?? 'N/A' }}</td>
                    <td>{{ $doc->document_type }}</td>
                    <td>{{ $doc->description }}</td>
                    <td>{{ ucfirst($doc->status) }}</td>
                    <td>{{ $doc->expiry_date }}</td>
                    <td>{{ $doc->created_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>No documents found.</p>
@endif

                    </div>
                </div>
            </div>
        </div>


        <!-- /Generate Employee Payroll-->

        <!-- Edit Employee -->
     
        <!-- /Edit Employee -->

        <!-- Add Employee Success -->
        <div class="modal fade" id="success_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center p-3">
                            <span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
                                    class="ti ti-check fs-24"></i></span>
                            <h5 class="mb-2" id="success_message"></h5>

                            </p>
                            <div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="{{ url('employees') }}" class="btn btn-dark w-100">Back to
                                            List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add employee Success -->

        <!-- Delete Modal -->
     
      
    </div>


@endsection
