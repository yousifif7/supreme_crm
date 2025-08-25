@extends('layouts.app')
@section('title', 'CRM - Materials')
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Materials</h2>
                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">

                        <button class="btn btn-primary" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('materials.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('materials.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_leave"
                        class=" add_btn btn btn-white d-inline-flex align-items-center">
                        <i class="ti ti-plus me-2"></i>Material
                    </a>

                    <!-- Search -->
                    {{-- <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                        <!-- /Search -->
                    </div> --}}
                </div>
            </div>
            <!-- /Breadcrumb -->
            <div class="card">
                <div class="card-body">
                    {{ $dataTable->table(['class' => 'table table-bordered table-striped']) }}
                </div>
            </div>

            <!-- Material Details Modal -->
            <div class="modal fade" id="materialDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Material Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Title:</strong> <span id="materialTitle"></span></p>
                            <p><strong>Description:</strong> <span id="materialDescription"></span></p>
                            <p><strong>Type:</strong> <span id="materialType"></span></p>
                            <p><strong>Expiry Date:</strong> <span id="materialExpiry"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Client -->
        <div class="modal fade" id="add_leave">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Material</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('materials.store') }}" method="POST" enctype="multipart/form-data"
                        id="add_materials">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                                <input type="text" name="title" class="form-control">
                                                <span class="text-danger form-error" id="material_title"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Description <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="description" class="form-control">
                                                <span class="text-danger form-error" id="description"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Material Type <span
                                                        class="text-danger">*</span></label>
                                                <select type="text" name="type" class="form-control">
                                                    <option value="">----- Select material Type -----</option>
                                                    <option value="policy">Policy</option>
                                                    <option value="training">Training</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Expiry Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="expiry_date" class="form-control">
                                                <span class="text-danger form-error" id="error_expiry_date"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Upload File <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="pdf_url" class="form-control">
                                                <span class="text-danger form-error" id="file_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_materials" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Add Client -->

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                            <i class="ti ti-trash-x fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Delete</h4>
                        <p class="mb-3">This action cannot be undone. Are you sure you want to delete?</p>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Delete Modal -->
    </div>

    <!-- /Page Wrapper -->
@endsection
@section('scripts')
{{ $dataTable->scripts() }}

<script>
    // Select All Checkbox
$(document).on('change', '#selectAll', function() {
    $('.rowCheckbox').prop('checked', $(this).is(':checked'));
});

    // Add Material AJAX
$('#add_material_form').on('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);
    let submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            $('#add_material_form')[0].reset();
            $('#addMaterialModal').modal('hide');
            toast_success(response.message);
            reloadDatatable('#materials-table');
            materialsTable.ajax.reload(null, false); // reload without resetting pagination
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    $('#error_' + key).text(value[0]);
                });
            } else {
                toast_danger('Something went wrong!');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false).text('Save');
        }
    });
});

    // View Material
    $(document).on('click', '.viewMaterial', function() {
    let id = $(this).data('id');

    $.get(`/materials/${id}`, function(data) {
        $('#viewTitle').text(data.title);
        $('#viewDescription').text(data.description);
        $('#viewType').text(data.type);
        $('#viewExpiry').text(data.expiry_date);
        if(data.pdf_url) {
            $('#viewFile').html(`<a href="${data.pdf_url}" target="_blank">Download</a>`);
        } else {
            $('#viewFile').html('—');
        }
        $('#viewMaterialModal').modal('show');
    }).fail(function() {
        toast_danger('Failed to fetch material details.');
    });
});

    // Delete Material
    let selectedMaterialId = null;

$(document).on('click', '.deleteMaterial', function() {
    selectedMaterialId = $(this).data('id');
    $('#deleteMaterialModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!selectedMaterialId) return;

    $.ajax({
        url: `/materials/${selectedMaterialId}`,
        type: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            $('#deleteMaterialModal').modal('hide');
            toast_success(response.message);
            reloadDatatable('#materials-table');

            materialsTable.ajax.reload(null, false);
        },
        error: function() {
            toast_danger('Failed to delete material.');
        }
    });
});

    // Bulk Delete
   $('#bulkDeleteBtn').on('click', function() {
    let selectedIds = $('.rowCheckbox:checked').map(function() { return this.value; }).get();

    if (selectedIds.length === 0) {
        toast_danger('Select at least one material.');
        return;
    }

    if (!confirm('Are you sure you want to delete selected materials?')) return;

    $.ajax({
        url: '/materials/bulk-delete',
        type: 'POST',
        data: { ids: selectedIds },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            toast_success(response.message);
            reloadDatatable('#materials-table');

            materialsTable.ajax.reload(null, false);
        },
        error: function() {
            toast_danger('Failed to delete selected materials.');
        }
    });
});


</script>
@endsection
