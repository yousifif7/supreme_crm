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

                            <p id="viewDeadlineRow"><strong>Complete By Date:</strong> <span id="viewDeadline"></span></p>
                            <p id="viewImplementationDateRow"><strong>Implementation Date:</strong> <span
                                    id="viewImplementationDate"></span></p>
                            <p id="acknowledgeByDateRow"><strong>Acknowledge By Date:</strong> <span
                                    id="acknowledgeByDate"></span></p>

                            <p><strong>File:</strong> <span id="viewFile"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add material -->
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
                                                    <label class="form-label">Title <span
                                                            class="text-danger">*</span></label>
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
                                                        <option value="general_guidelines">General Guidelines</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6" id="implementation-wrapper" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label">Implementation Date <span
                                                            class="text-danger">*</span></label>
                                                    <input type="date" name="implementation_date"
                                                        class="form-control">
                                                    <span class="text-danger form-error"
                                                        id="error_implementation_date"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6" id="deadline-wrapper" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label">Complete By Date<span
                                                            class="text-danger">*</span></label>
                                                    <input type="date" name="deadline" class="form-control">
                                                    <span class="text-danger form-error" id="error_deadline"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6" id="acknowledge_by_date-wrapper"
                                                style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label">Acknowledge by date <span
                                                            class="text-danger">*</span></label>
                                                    <input type="date" name="acknowledge_by_date"
                                                        class="form-control">
                                                    <span class="text-danger form-error"
                                                        id="error_acknowledge_by_date"></span>
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
            <div class="modal fade" id="deleteMaterialModal">
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

            <!-- Edit Material Modal -->
            <div class="modal fade" id="editMaterialModal" tabindex="-1">
                <div class="modal-dialog">
                    <form id="editMaterialForm" enctype="multipart/form-data">
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
                                    <span class="text-danger form-error" id="error_title"></span>
                                </div>

                                <div class="mb-2">
                                    <label>Description</label>
                                    <textarea name="description" id="editDescription" class="form-control"></textarea>
                                    <span class="text-danger form-error" id="error_description"></span>
                                </div>

                                <div class="mb-2">
                                    <label>Type</label>
                                    <select name="type" id="editType" class="form-control">
                                        <option value="">-- Select Type --</option>
                                        <option value="policy">Policy</option>
                                        <option value="training">Training</option>
                                        <option value="general_guidelines">General Guidelines</option>
                                    </select>
                                    <span class="text-danger form-error" id="error_type"></span>
                                </div>

                                <div id="edit-implementation-wrapper" class="mb-2" style="display:none;">
                                    <label>Implementation date</label>
                                    <input type="date" name="implementation_date" id="editImplementationDate"
                                        class="form-control">
                                    <span class="text-danger form-error" id="error_implementation_date"></span>
                                </div>

                                <div id="edit-deadline-wrapper" class="mb-2" style="display:none;">
                                    <label>Deadline</label>
                                    <input type="date" name="deadline" id="editDeadline" class="form-control">
                                    <span class="text-danger form-error" id="error_deadline"></span>
                                </div>

                                <div id="edit-acknowledge_by_date-wrapper" class="mb-2" style="display:none;">
                                    <label>Acknowledge by date</label>
                                    <input type="date" name="acknowledge_by_date" id="editAcknowledgeByDate"
                                        class="form-control">
                                    <span class="text-danger form-error" id="error_acknowledge_by_date"></span>
                                </div>

                                <div class="mb-2">
                                    <label>Upload File (replace)</label>
                                    <input type="file" name="pdf_url" id="editPdfFile" class="form-control"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                    <div id="currentFile" class="mt-2"></div>
                                    <span class="text-muted small">Leave blank to keep current file.</span>
                                    <span class="text-danger form-error" id="error_pdf_url"></span>
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



            <div class="modal fade" id="acknowledgedModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Acknowledged Users</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <ul id="acknowledgedList" class="list-group list-unstyled"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- /Page Wrapper -->
        @endsection
        @section('scripts')
            {{ $dataTable->scripts() }}

            <script>
                $(function() {
                    let selectedMaterialId = null;

                    // ✅ Select All Checkbox
                    $(document).on('change', '#selectAll', function() {
                        $('.rowCheckbox').prop('checked', $(this).is(':checked'));
                    });

                    // ✅ Show/Hide Fields Based on Type
                    function toggleTypeFields(type, prefix = '') {
                        const impl = $(`#${prefix}implementation-wrapper`);
                        const dl = $(`#${prefix}deadline-wrapper`);
                        const ack = $(`#${prefix}acknowledge_by_date-wrapper`);

                        // hide all first
                        impl.hide().find('input').prop('required', false).val('');
                        dl.hide().find('input').prop('required', false).val('');
                        ack.hide().find('input').prop('required', false).val('');

                        // show based on type
                        if (type === 'training') dl.show().find('input').prop('required', true);
                        if (type === 'policy') {
                            impl.show().find('input').prop('required', true);
                            ack.show().find('input').prop('required', true);
                        }
                        if (type === 'general_guidelines') ack.show().find('input').prop('required', true);
                    }

                    // Add Modal: react to type change
                    $(document).on('change', '#add_materials select[name="type"]', function() {
                        toggleTypeFields($(this).val());
                    });

                    // Edit Modal: react to type change
                    $(document).on('change', '#editMaterialForm select[name="type"]', function() {
                        toggleTypeFields($(this).val(), 'edit-');
                    });

                    // ✅ Add Material AJAX
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
                            success: function() {
                                $('#add_material_form')[0].reset();
                                $('#addMaterialModal').modal('hide');
                                toast_success('HR Material created successfully!');
                                reloadDatatable('#materials-table');
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

                    // ✅ View Material
                    $(document).on('click', '.viewMaterial', function() {
                        let id = $(this).data('id');
                        $.get(`/materials/${id}`, function(data) {
                            $('#viewTitle').text(data.title);
                            $('#viewDescription').text(data.description);
                            $('#viewType').text(data.type);
                            $('#viewFile').html(data.pdf_url ?
                                `<a href="${data.pdf_url}" target="_blank">Download</a>` : '—');

                            (data.type === 'training' && data.deadline) ?
                            $('#viewDeadlineRow').show().find('#viewDeadline').text(data.deadline): $(
                                '#viewDeadlineRow').hide();

                            (data.type === 'policy' && data.implementation_date) ?
                            $('#viewImplementationDateRow').show().find('#viewImplementationDate').text(data
                                .implementation_date): $('#viewImplementationDateRow').hide();

                            ((data.type === 'policy' || data.type === 'general_guidelines') && data
                                .acknowledge_by_date) ?
                            $('#acknowledgeByDateRow').show().find('#acknowledgeByDate').text(data
                                .acknowledge_by_date): $('#acknowledgeByDateRow').hide();

                            $('#viewMaterialModal').modal('show');
                        }).fail(() => toast_danger('Failed to fetch material details.'));
                    });

                    // ✅ Show Acknowledged
                    $(document).on('click', '.showAcknowledged', function() {
                        let id = $(this).data('id');

                        $.get(`/show/acknowledged/${id}`, function(data) {
                            let list = $('#acknowledgedList').empty();

                            if (!data.users || data.users.length === 0) {
                                list.append(
                                    '<li class="list-group-item text-muted">No acknowledgements yet.</li>'
                                );
                            } else {
                                data.users.forEach((u, i) => {
                                    list.append(
                                        `<li class="list-group-item">${i + 1}. ${u.name}</li>`);
                                });
                            }

                            $('#acknowledgedModal').modal('show');
                        }).fail(() => toast_danger('Failed to fetch acknowledged users.'));
                    });

                    // ✅ Edit Modal Helper
                    // function updateEditFieldsByType(type) {
                    //     type = (type || '').trim().toLowerCase(); // normalize

                    //     $('#implementation-wrapper, #deadline-wrapper, #acknowledge_by_date-wrapper').hide();
                    //     $('#editImplementationDate, #editDeadline, #editAcknowledgeByDate').prop('required', false);

                    //     if (type === 'policy') {
                    //         $('#implementation-wrapper, #acknowledge_by_date-wrapper').show();
                    //         $('#editImplementationDate, #editAcknowledgeByDate').prop('required', true);
                    //     } else if (type === 'training') {
                    //         $('#deadline-wrapper').show();
                    //         $('#editDeadline').prop('required', true);
                    //     } else if (type === 'general_guidelines') {
                    //         $('#acknowledge_by_date-wrapper').show();
                    //         $('#editAcknowledgeByDate').prop('required', true);
                    //     }
                    // }


                    // ✅ Open Edit Modal
                    $(document).on('click', '.editMaterial', function(e) {
                        e.preventDefault();
                        let id = $(this).data('id');

                        $.get(`/materials/${id}`, function(data) {
                            $('#editMaterialId').val(data.id);
                            $('#editTitle').val(data.title || '');
                            $('#editDescription').val(data.description || '');
                            $('#editType').val(data.type || '');

                            $('#editImplementationDate').val(data.implementation_date || '');
                            $('#editDeadline').val(data.deadline || '');
                            $('#editAcknowledgeByDate').val(data.acknowledge_by_date || '');

                            if (data.pdf_url) {
                                let fileName = data.pdf_url.split('/').pop();
                                $('#currentFile').html(
                                    `<small>Current File: <a href="${data.pdf_url}" target="_blank">${fileName}</a></small>`
                                );
                            } else {
                                $('#currentFile').html('<small>No file uploaded.</small>');
                            }

                            // toggle fields based on type for Edit modal
                            toggleTypeFields(data.type, 'edit-');

                            $('.form-error').text('');
                            $('#editMaterialModal').modal('show');
                        }).fail(function() {
                            toast_danger('Failed to fetch material details.');
                        });
                    });

                    // when type changes in the modal
                    $(document).on('change', '#editType', function() {
                        updateEditFieldsByType($(this).val());
                    });

                    // ✅ Submit Edit
                    $('#editMaterialForm').on('submit', function(e) {
                        e.preventDefault();
                        let id = $('#editMaterialId').val();
                        let formData = new FormData(this);

                        $.ajax({
                            url: `/materials/${id}`,
                            type: 'POST', // method spoofing
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'X-HTTP-Method-Override': 'PUT'
                            },
                            success: function() {
                                $('#editMaterialModal').modal('hide');
                                toast_success('Material updated successfully!');
                                reloadDatatable('#materials-table');
                            },
                            error: function(xhr) {
                                $('.form-error').text('');
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    $.each(xhr.responseJSON.errors, function(key, value) {
                                        $('#error_' + key).text(value[0]);
                                    });
                                } else {
                                    toast_danger('Failed to update material.');
                                }
                            }
                        });
                    });

                    // ✅ Delete
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
                            success: function() {
                                $('#deleteMaterialModal').modal('hide');
                                toast_success('HR material deleted successfully!');
                                reloadDatatable('#materials-table');
                            },
                            error: function() {
                                toast_danger('Failed to delete material.');
                            }
                        });
                    });
                });

                // Bulk delete button
                $('#bulkDeleteBtn').on('click', function() {
                    const selected = $('#materials-table input[type="checkbox"]:checked')
                        .not('#selectAll')
                        .map(function() {
                            return $(this).val();
                        }).get();

                    if (selected.length === 0) {
                        toast_danger('Please select at least one material to delete.');
                        return;
                    }

                    if (!confirm('Are you sure you want to delete the selected materials?')) return;

                    $.ajax({
                        url: '{{ route('materials.bulkDelete') }}',
                        type: 'POST',
                        data: {
                            ids: selected,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            toast_success('Selected materials deleted successfully!');
                            reloadDatatable('#materials-table');
                        },
                        error: function() {
                            toast_danger('Something went wrong during bulk delete.');
                        }
                    });
                });
            </script>
        @endsection
