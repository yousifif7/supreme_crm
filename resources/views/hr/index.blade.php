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
            <!-- View Material Modal -->
            <div class="modal fade" id="viewMaterialModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="viewTitle" class="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Description:</strong> <span id="viewDescription"></span></p>
                            <p><strong>Type:</strong> <span id="viewType"></span></p>
                            <p><strong>Expiry:</strong> <span id="viewExpiry"></span></p>
                            <p><strong>File:</strong> <span id="viewFile"></span></p>
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

        <!-- Delete Material Modal -->
        <div class="modal fade" id="deleteMaterialModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">Are you sure you want to delete this material?</div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Edit Material Modal -->
        <div class="modal fade" id="editMaterialModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="editMaterialForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Material</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="editMaterialId">
                            <div class="mb-2">
                                <label>Title</label>
                                <input type="text" name="title" id="editTitle" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label>Description</label>
                                <textarea name="description" id="editDescription" class="form-control"></textarea>
                            </div>
                            <div class="mb-2">
                                <label>Type</label>
                                <input type="text" name="type" id="editType" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" id="editExpiry" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>PDF URL</label>
                                <input type="text" name="pdf_url" id="editPdf" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" type="submit">Save Changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
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
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#add_material_form')[0].reset();
                        $('#addMaterialModal').modal('hide');
                        toast_success('HR Material created successfully!');
                        reloadDatatable('#training_materials-table');
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

            let selectedMaterialId = null;

            // View
            $(document).on('click', '.viewMaterial', function() {
                let id = $(this).data('id');
                $.get(`/materials/${id}`, function(data) {
                    $('#viewTitle').text(data.title);
                    $('#viewDescription').text(data.description);
                    $('#viewType').text(data.type);
                    $('#viewExpiry').text(data.expiry_date);
                    $('#viewFile').html(data.pdf_url ?
                        `<a href="${data.pdf_url}" target="_blank">Download</a>` : '—');
                    $('#viewMaterialModal').modal('show');
                }).fail(() => toast_danger('Failed to fetch material details.'));
            });

            // Edit
            $(document).on('click', '.editMaterial', function() {
                let id = $(this).data('id');
                $.get(`/materials/${id}`, function(data) {
                    $('#editMaterialId').val(data.id);
                    $('#editTitle').val(data.title);
                    $('#editDescription').val(data.description);
                    $('#editType').val(data.type);
                    $('#editExpiry').val(data.expiry_date);
                    $('#editPdf').val(data.pdf_url);
                    $('#editMaterialModal').modal('show');
                }).fail(() => toast_danger('Failed to fetch material details.'));
            });

            $('#editMaterialForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#editMaterialId').val();
                $.ajax({
                    url: `/materials/${id}`,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: $(this).serialize(),
                    success: function(resp) {
                        $('#editMaterialModal').modal('hide');
                        toast_success('Hr material edited successfully!');
                        reloadDatatable('#training_materials-table');

                    },
                    error: function() {
                        toast_danger('Failed to update material.');
                    }
                });
            });

            // Delete
            $(document).on('click', '.deleteMaterial', function() {
                selectedMaterialId = $(this).data('id');
                $('#deleteMaterialModal').modal('show');
            });

            $('#confirmDeleteBtn').on('click', function() {
                if (!selectedMaterialId) return;
                $.ajax({
                    url: `/materials/${selectedMaterialId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(resp) {
                        $('#deleteMaterialModal').modal('hide');
                        toast_success('Hr material Deleted succesfully!');
                        reloadDatatable('training_materials-table');
                    },
                    error: function() {
                        toast_danger('Failed to delete material.');
                    }
                });
            });
        </script>
    @endsection
