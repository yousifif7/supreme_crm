<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        <div class="alert-box-container"></div>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Roadworthiness Check </h2>
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
                    <button class="btn btn-primary" id="checksbulkDeleteBtn">Delete Selected</button>
                    <a href="javascript:void(0);"
                        class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                        data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i>Export
                    </a>
                    <ul class="dropdown-menu  dropdown-menu-start p-3">
                        <li>
                            <a href="{{ route('checks.export.pdf') }}" class="dropdown-item rounded-1"><i
                                    class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                        </li>
                        <li>
                            <a href="{{ route('checks.export.excel') }}" class="dropdown-item rounded-1"><i
                                    class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                        </li>
                    </ul>


                </div>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

            <div class="me-2 mb-2 filter_area">

                <a href="#" data-bs-toggle="modal" data-bs-target="#add_check"
                    class=" add_btn btn btn-white d-inline-flex align-items-center"">
                    <i class="ti ti-plus me-2"></i>Check
                </a>
            </div>


        </div>
        <!-- /Breadcrumb -->

        <div class="card">

            <div class="card-body p-0">
                <div class="custom-datatable-filter table-responsive">
                    <table id="roadworthiness-checks-table"
                        class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold datatable"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="checksselectAll"></th>
                                <th>#</th>
                                <th>Date Completed</th>
                                <th>Checked By</th>
                                <th>Defects Found</th>
                                <th>Corrective Action Taken</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>


        </div>
        <!-- Add Roadworthiness Check Modal -->
        <div class="modal fade" id="add_check">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Roadworthiness Check</h5>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="add_check_form" action="{{ route('checks.store') }}">
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
                                                    {{ $vehicle->registration_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger form-error" id="error_vehicle_id"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date Completed <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="date_completed" class="form-control"
                                            id="date_completed">
                                        <span class="text-danger form-error" id="error_date_completed"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Checked By <span class="text-danger">*</span></label>
                                        <input type="text" name="checked_by" class="form-control" id="checked_by">
                                        <span class="text-danger form-error" id="error_checked_by"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Defects Found <span
                                                class="text-danger">*</span></label>
                                        <textarea name="defects_found" class="form-control" id="defects_found" rows="3"></textarea>
                                        <span class="text-danger form-error" id="error_defects_found"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Corrective Action Taken <span
                                                class="text-danger">*</span></label>
                                        <textarea name="corrective_action_taken" class="form-control" id="corrective_action_taken" rows="3"></textarea>
                                        <span class="text-danger form-error"
                                            id="error_corrective_action_taken"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="add_check_form" id="savecheck" class="btn btn-primary">
                                Save Check
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Roadworthiness Check Modal -->
        <div class="modal fade" id="edit_check">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Roadworthiness Check</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_check_form" action="#">
                        @csrf
                        <input type="hidden" name="check_id" id="check_id">
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
                                        <label class="form-label">Date Completed <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="date_completed" id="edit_date_completed"
                                            class="form-control">
                                        <span class="text-danger form-error" id="edit_error_date_completed"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Checked By <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="checked_by" id="edit_checked_by"
                                            class="form-control">
                                        <span class="text-danger form-error" id="edit_error_checked_by"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Defects Found <span
                                                class="text-danger">*</span></label>
                                        <textarea name="defects_found" id="edit_defects_found" class="form-control" rows="3"></textarea>
                                        <span class="text-danger form-error" id="edit_error_defects_found"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Corrective Action Taken <span
                                                class="text-danger">*</span></label>
                                        <textarea name="corrective_action_taken" id="edit_corrective_action_taken" class="form-control" rows="3"></textarea>
                                        <span class="text-danger form-error"
                                            id="edit_error_corrective_action_taken"></span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="edit_check_form" class="btn btn-primary"
                                id="edit_check_btn">
                                Update Check
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Delete Modal -->
        <div class="modal fade" id="checks_delete_modal">
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
                            <button type="button" id="confirmChecksDeleteBtn" class="btn btn-danger">Yes,
                                Delete</button>
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
    <script>
        $(document).ready(function() {
            // Add Check
            $('#add_check_form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#savecheck');

                submitButton.prop('disabled', true).html('Saving...');

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
                        closeBsModal('#add_check');
                        toast_success('Roadworthiness check added successfully.');
                        reloadDatatable('#roadworthiness-checks-table');
                        $('#add_check_form')[0].reset();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Save Check');
                    }
                });
            });

            // Edit Check
            $('#edit_check_form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='edit_error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#edit_check_btn');
                let checkId = $('#check_id').val();

                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatecheck/${checkId}`, // Your route to update the check
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_check');
                        toast_success('Roadworthiness check updated successfully.');
                        reloadDatatable('#roadworthiness-checks-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#edit_error_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Update Check');
                    }
                });
            });
        });

        function editCheck(check_id) {
            $.get('/editcheck/' + check_id, function(data) {
                if (data.check) {
                    $('#check_id').val(data.check.id); // Hidden input
                    $('#edit_vehicle_id').val(data.check.vehicle_id);
                    $('#edit_date_completed').val(data.check.date_completed);
                    $('#edit_checked_by').val(data.check.checked_by);
                    $('#edit_defects_found').val(data.check.defects_found);
                    $('#edit_corrective_action_taken').val(data.check.corrective_action_taken);

                    $('#edit_check').modal('show');
                } else {
                    toast_danger('No roadworthiness check data found for this record.');
                }
            });
        }


        function deleteCheck(record_id) {
            selectedId = record_id;
            $('#checks_delete_modal').modal('show');
        }

        document.getElementById('confirmChecksDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deletecheck/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#checks_delete_modal');
                        toast_success('Check Deleted Successfully!');
                        reloadDatatable('#roadworthiness-checks-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#checks_delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });

        // Bulk delete button
        $('#checksbulkDeleteBtn').on('click', function() {
            const selected = $('.dT-row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                toast_danger('Please select at least one check to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected check?')) return;
            $.ajax({
                url: '{{ route('checks.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected check deleted successfully!');
                    reloadDatatable('#roadworthiness-checks-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });

        $('#roadworthiness-checks-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('checks.data') }}',
            columns: [{
                    data: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'number',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'date_completed'
                },
                {
                    data: 'checked_by'
                },
                {
                    data: 'defects_found'
                },
                {
                    data: 'corrective_action_taken'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });
        $('#checksselectAll').on('click', function() {
            let checked = this.checked;
            $('.dT-row-checkbox').prop('checked', checked);
        });
    </script>
