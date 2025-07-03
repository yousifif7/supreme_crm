@extends('layouts.app')
@section('title', 'CRM - Vehicle Documentation Upload ')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Documentation Uploads </h2>
                    @if (session('success'))
                        <div class="alert alert-success mt-3">
                            {{ session('success') }}
                        </div>
                    @endif

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary" id="bulkDeleteBtn">Delete Selected</button>



                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_documentation"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Document
                    </a>


                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                        <!-- /Search -->
                    </div>


                </div>


            </div>
            <!-- /Breadcrumb -->

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        {!! $dataTable->table(['class' => 'table datatable']) !!}
                    </div>
                </div>



            </div>
            <!-- Add Documentation Upload Modal -->
            <div class="modal fade" id="add_documentation">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Vehicle Documentation</h5>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="add_documentation_form" action="{{ route('documents.store') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                            <select name="vehicle_id" class="form-control" id="vehicle_id">
                                                <option value="">Select vehicle</option>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">
                                                        {{ $vehicle->registration_number }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="error_vehicle_id"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">MOT Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="mot_certificate" class="form-control">
                                            <span class="text-danger form-error" id="error_mot_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Insurance Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="insurance_certificate" class="form-control">
                                            <span class="text-danger form-error" id="error_insurance_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">V5C Logbook (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="v5c_logbook" class="form-control">
                                            <span class="text-danger form-error" id="error_v5c_logbook"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tax Confirmation (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="tax_confirmation" class="form-control">
                                            <span class="text-danger form-error" id="error_tax_confirmation"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tachograph Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="tachograph_certificate" class="form-control">
                                            <span class="text-danger form-error" id="error_tachograph_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Service Report (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="service_report" class="form-control">
                                            <span class="text-danger form-error" id="error_service_report"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Inspection Report (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="inspection_report" class="form-control">
                                            <span class="text-danger form-error" id="error_inspection_report"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="add_documentation_form" id="savedocumentation"
                                    class="btn btn-primary">
                                    Upload Documents
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Documentation Upload Modal -->
            <div class="modal fade" id="edit_documentation">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Vehicle Documentation</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="edit_documentation_form" action="#" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="documentation_id" id="documentation_id">
                            <div class="modal-body pb-0">
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                            <select name="vehicle_id" id="edit_vehicle_id" class="form-control">
                                                <option value="">Select vehicle</option>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">
                                                        {{ $vehicle->registration_number }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="edit_error_vehicle_id"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">MOT Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="mot_certificate" class="form-control">
                                            <span class="text-danger form-error" id="edit_error_mot_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Insurance Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="insurance_certificate" class="form-control">
                                            <span class="text-danger form-error"
                                                id="edit_error_insurance_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">V5C Logbook (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="v5c_logbook" class="form-control">
                                            <span class="text-danger form-error" id="edit_error_v5c_logbook"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tax Confirmation (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="tax_confirmation" class="form-control">
                                            <span class="text-danger form-error" id="edit_error_tax_confirmation"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tachograph Certificate (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="tachograph_certificate" class="form-control">
                                            <span class="text-danger form-error"
                                                id="edit_error_tachograph_certificate"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Service Report (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="service_report" class="form-control">
                                            <span class="text-danger form-error" id="edit_error_service_report"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Inspection Report (PDF) <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="inspection_report" class="form-control">
                                            <span class="text-danger form-error" id="edit_error_inspection_report"></span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="edit_documentation_form" class="btn btn-primary"
                                    id="edit_documentation_btn">
                                    Update Documents
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Delete Modal -->
            <div class="modal fade" id="delete_modal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                                <i class="ti ti-trash-x fs-36"></i>
                            </span>
                            <h4 class="mb-1">Confirm Delete</h4>
                            <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.
                            </p>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Delete Modal -->
            <!-- Import modal -->
            <div class="modal fade" id="import_modal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Import Excel</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form action="{{ route('checks.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                    aria-labelledby="info-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex gap-2">
                                                    <input type="file" name="import_file" class="form-control"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-light border me-2"
                                            data-bs-dismiss="modal">Cancel</button>

                                        <button class="btn btn-primary" type="submit">Import</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Page Wrapper -->
    @endsection
    @section('scripts')
        <script>
            $(document).ready(function() {
                // Add Documentation
                $('#add_documentation_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#savedocumentation');

                    submitButton.prop('disabled', true).html('Uploading...');

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            $('#add_documentation').modal('hide');
                            toast_success('Documents uploaded successfully.');
                            reloadDatatable('#documentation-table');
                            $('#add_documentation_form')[0].reset();
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    $('#error_' + key).text(value[0]);
                                });
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).html('Upload Documents');
                        }
                    });
                });

                // Edit Documentation
                $('#edit_documentation_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='edit_error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#edit_documentation_btn');
                    let docId = $('#documentation_id').val();

                    submitButton.prop('disabled', true).html('Updating...');

                    $.ajax({
                        url: `${baseUrl}/updatedocument/${docId}`,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            $('#edit_documentation').modal('hide');
                            toast_success('Documents updated successfully.');
                            reloadDatatable('#documentation-table');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    $('#edit_error_' + key).text(value[0]);
                                });
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).html('Update Documents');
                        }
                    });
                });
            });


            function editDocumentation(docId) {
                $.get(`${baseUrl}/editdocument/` + docId, function(data) {
                    if (data.document) {
                        $('#documentation_id').val(data.document.id); // Hidden input
                        $('#edit_vehicle_id').val(data.document.vehicle_id);

                        // Optionally show existing filenames or links (if desired)
                        // Otherwise, just open the modal — file inputs cannot be pre-filled for security reasons

                        $('#edit_documentation').modal('show');
                    } else {
                        alert('No documentation data found for this record.');
                    }
                });
            }


            let selectedId = null;

            function deleteDocumentation(record_id) {
                selectedId = record_id;
                $('#delete_modal').modal('show');
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (selectedId !== null) {
                    $.ajax({
                        url: `${baseUrl}/deletedocument/${selectedId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#delete_modal').modal('hide');
                            toast_success('Documentation Upload Deleted Successfully!');
                            reloadDatatable('#documentation-table');
                        },
                        error: function(xhr) {
                            $('#delete_modal').modal('hide');
                            alert('Something went wrong. Please try again.');
                        }
                    });
                }
            });

            // Bulk delete button
            $('#bulkDeleteBtn').on('click', function() {
                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (selected.length === 0) {
                    alert('Please select at least one documentation to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected documentation?')) return;
                $.ajax({
                    url: '{{ route('documents.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected documentation deleted successfully!');
                        reloadDatatable('#documentation-table');
                    },
                    error: function() {
                        alert('Something went wrong during bulk delete.');
                    }
                });
            });
        </script>
        {!! $dataTable->scripts() !!}
    @endsection
