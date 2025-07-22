@extends('layouts.app')
@section('title', 'CRM - Vehicle Maintenance')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Service & Maintenances</h2>
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
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('maintenances.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('maintenances.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_maintenance"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Maintenance
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
                        {{ $dataTable->setTableHeadClass('thead-light')->table(['class' => 'table datatable']) }}
                    </div>
                </div>

            </div>
            <!-- Add Maintenance Modal -->
            <div class="modal fade" id="add_maintenance">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Maintenance Info</h5>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="add_maintenance_form" action="{{ route('maintenances.store') }}">
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
                                            <label class="form-label">Last Service Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="last_service_date" class="form-control"
                                                id="last_service_date">
                                            <span class="text-danger form-error" id="error_last_service_date"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Next Service Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="next_service_due_date" class="form-control"
                                                id="next_service_due_date">
                                            <span class="text-danger form-error" id="error_next_service_due_date"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Work Type <span class="text-danger">*</span></label>
                                            <input type="text" name="work_type" class="form-control" id="work_type">
                                            <span class="text-danger form-error" id="error_work_type"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Maintenance Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="maintenance_date" class="form-control"
                                                id="maintenance_date">
                                            <span class="text-danger form-error" id="error_maintenance_date"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Garage Provider <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="garage_provider" class="form-control"
                                                id="garage_provider">
                                            <span class="text-danger form-error" id="error_garage_provider"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reported By <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="reported_by" class="form-control"
                                                id="reported_by">
                                            <span class="text-danger form-error" id="error_reported_by"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Reported <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="date_reported" class="form-control"
                                                id="date_reported">
                                            <span class="text-danger form-error" id="error_date_reported"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Resolution Status <span
                                                    class="text-danger">*</span></label>
                                            <select name="resolution_status" class="form-select" id="resolution_status">
                                                <option value="" selected hidden>Choose...</option>
                                                <option value="pending">Pending</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="in_progress">In Progress</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_resolution_status"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="add_maintenance_form" id="savemaintenance"
                                    class="btn btn-primary">Save
                                    Maintenance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Maintenance Modal -->
            <div class="modal fade" id="edit_maintenance">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Maintenance Info</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="edit_maintenance_form" action="#">
                            @csrf
                            <input type="hidden" name="maintenance_id" id="maintenance_id">
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
                                            <label class="form-label">Last Service Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="last_service_date" id="edit_last_service_date"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_last_service_date"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Next Service Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="next_service_due_date"
                                                id="edit_next_service_due_date" class="form-control">
                                            <span class="text-danger form-error"
                                                id="edit_error_next_service_due_date"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Work Type <span class="text-danger">*</span></label>
                                            <input type="text" name="work_type" id="edit_work_type"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_work_type"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Maintenance Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="maintenance_date" id="edit_maintenance_date"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_maintenance_date"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Garage Provider <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="garage_provider" id="edit_garage_provider"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_garage_provider"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reported By <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="reported_by" id="edit_reported_by"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_reported_by"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Reported <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="date_reported" id="edit_date_reported"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_date_reported"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Resolution Status <span
                                                    class="text-danger">*</span></label>
                                            <select name="resolution_status" id="edit_resolution_status"
                                                class="form-select">
                                                <option value="">-- Choose --</option>
                                                <option value="pending">Pending</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="in_progress">In Progress</option>
                                            </select>
                                            <span class="text-danger form-error" id="edit_error_resolution_status"></span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="edit_maintenance_form" class="btn btn-primary"
                                    id="edit_maintenance_btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Vehicle Compliances Success -->
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
                        <form action="{{ route('maintenances.import') }}" method="POST" enctype="multipart/form-data">
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
                // Add Maintenance
                $('#add_maintenance_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#savemaintenance'); // Adjust to your button ID

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
                            closeBsModal('#add_maintenance');
                            toast_success('Maintenance record added successfully.');
                            reloadDatatable('#vehicle-maintenances-table');
                            $('#add_maintenance_form')[0].reset(); // Optional: reset form
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
                            submitButton.prop('disabled', false).html('Save');
                        }
                    });
                });

                // Edit Maintenance
                $('#edit_maintenance_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='edit_error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#edit_maintenance_btn');

                    let maintenanceId = $('#maintenance_id').val(); // Hidden input

                    submitButton.prop('disabled', true).html('Updating...');

                    $.ajax({
                        url: `${baseUrl}/updatemaintenance/${maintenanceId}`, // Adjust route if different
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            closeBsModal('#edit_maintenance');
                            toast_success('Maintenance record updated successfully.');
                            reloadDatatable('#vehicle-maintenances-table');
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
                            submitButton.prop('disabled', false).html('Update');
                        }
                    });
                });
            });

            function editMaintenance(maintenance_id) {
                $.get(`${baseUrl}/editmaintenance/` + maintenance_id, function(data) {
                    if (data.maintenance) {
                        $('#maintenance_id').val(data.maintenance.id); // hidden input
                        $('#edit_vehicle_id').val(data.maintenance.vehicle_id);
                        $('#edit_last_service_date').val(data.maintenance.last_service_date);
                        $('#edit_next_service_due_date').val(data.maintenance.next_service_due_date);
                        $('#edit_work_type').val(data.maintenance.work_type);
                        $('#edit_maintenance_date').val(data.maintenance.maintenance_date);
                        $('#edit_garage_provider').val(data.maintenance.garage_provider);
                        $('#edit_reported_by').val(data.maintenance.reported_by);
                        $('#edit_date_reported').val(data.maintenance.date_reported);
                        $('#edit_resolution_status').val(data.maintenance.resolution_status);

                        $('#edit_maintenance').modal('show');
                    } else {
                        toast_danger('No maintenance data found for this record.');
                    }
                });
            }

            let selectedId = null;

            function deleteMaintenance(record_id) {
                selectedId = record_id;
                $('#delete_modal').modal('show');
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (selectedId !== null) {
                    $.ajax({
                        url: `${baseUrl}/deletemaintenance/${selectedId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            closeBsModal('#delete_modal');
                            toast_success('Vehicle Maintenance Deleted Successfully!');
                            reloadDatatable('#vehicle-maintenances-table');
                        },
                        error: function(xhr) {
                            closeBsModal('#delete_modal');
                            toast_danger('Something went wrong. Please try again.');
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
                    toast_danger('Please select at least one vehicle maintenance to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected vehicle maintenance?')) return;
                $.ajax({
                    url: '{{ route('maintenances.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected vehicles maintenance deleted successfully!');
                        reloadDatatable('#vehicle-maintenances-table');
                    },
                    error: function() {
                        toast_danger('Something went wrong during bulk delete.');
                    }
                });
            });
        </script>
        {!! $dataTable->scripts() !!}
    @endsection
