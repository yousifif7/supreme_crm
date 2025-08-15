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
                        <label for="document_field" class="form-label">Document Type</label>
                        <select name="document_field" id="document_field" class="form-select">
                            <option value="">Select Document</option>
                            @foreach($documentFields as $field => $label)
                                <option value="{{ $field }}" {{ $documentField == $field ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="upload_status" class="form-label">Upload Status</label>
                        <select name="upload_status" id="upload_status" class="form-select">
                            <option value="">Any</option>
                            <option value="uploaded" {{ $uploadStatus == 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                            <option value="missing" {{ $uploadStatus == 'missing' ? 'selected' : '' }}>Missing</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="expiry_status" class="form-label">Expiry Status</label>
                        <select name="expiry_status" id="expiry_status" class="form-select" 
                            {{ !$documentField || !array_key_exists($documentField, $expiryFields) || $uploadStatus == 'missing' ? 'disabled' : '' }}>
                            <option value="">Any</option>
                            <option value="valid" {{ $expiryStatus == 'valid' ? 'selected' : '' }}>Valid</option>
                            <option value="expired" {{ $expiryStatus == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="department_id" class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status" class="form-label">Employee Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
</form>
                </div>
            </div>

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">



  @if(!$hasFilters)
                <div class="alert alert-info">Please apply filters to view results.</div>
            @elseif($employees->isEmpty())
                <div class="alert alert-warning">No employees match the current filters.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Document Status</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td>{{ $employee->id }}</td>
                                    <td>{{ $employee->fore_name }} {{ $employee->sur_name }}</td>
                                    <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $employee->status == 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($employee->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($documentField)
                                            @if($employee->{$documentField})
                                                <span class="badge bg-success">Uploaded</span>
                                            @else
                                                <span class="badge bg-danger">Missing</span>
                                            @endif
                                        @else
                                            @php
                                                $uploadedCount = 0;
                                                foreach($documentFields as $field => $label) {
                                                    if($employee->{$field}) $uploadedCount++;
                                                }
                                            @endphp
                                            <span class="badge bg-{{ $uploadedCount > 0 ? 'success' : 'danger' }}">
                                                {{ $uploadedCount }}/{{ count($documentFields) }} documents
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($documentField && array_key_exists($documentField, $expiryFields) && $employee->{$documentField})
                                            @php
                                                $expiryField = $expiryFields[$documentField];
                                                $expiryDate = $employee->{$expiryField};
                                            @endphp
                                            @if($expiryDate)
                                                {{ \Carbon\Carbon::parse($expiryDate)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($documentField && array_key_exists($documentField, $expiryFields) && $employee->{$documentField} && $expiryDate)
                                            @php
                                                $daysRemaining = now()->diffInDays(\Carbon\Carbon::parse($expiryDate), false);
                                            @endphp
                                            
                                            @if($daysRemaining > 0)
                                                <span class="badge bg-success">{{ $daysRemaining }} days</span>
                                            @else
                                                <span class="badge bg-danger">Expired {{ abs($daysRemaining) }} days ago</span>
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ url('employees#'.$employee->id) }}" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
                    </div>
                </div>
            </div>
        </div>
     
      
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const documentFieldSelect = document.getElementById('document_field');
        const uploadStatusSelect = document.getElementById('upload_status');
        const expiryStatusSelect = document.getElementById('expiry_status');

        function updateExpiryStatusDisabledState() {
            const selectedField = documentFieldSelect.value;
            const uploadStatus = uploadStatusSelect.value;
            
            if (selectedField && @json(array_keys($expiryFields)).includes(selectedField) && uploadStatus !== 'missing') {
                expiryStatusSelect.disabled = false;
            } else {
                expiryStatusSelect.disabled = true;
                expiryStatusSelect.value = '';
            }
        }

        documentFieldSelect.addEventListener('change', updateExpiryStatusDisabledState);
        uploadStatusSelect.addEventListener('change', updateExpiryStatusDisabledState);
    });
</script>
@endsection
