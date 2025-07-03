@extends('layouts.app')
@section('title', 'CRM - Alert & Reminders')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Alert & Reminders</h2>
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
                                <a href="{{ route('reminders.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('reminders.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_reminder"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Reminder
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
            <!-- Add Alert & Reminder Modal -->
            <div class="modal fade" id="add_reminder">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Alert & Reminder</h5>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="add_reminder_form" action="{{ route('reminders.store') }}">
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
                                            <label class="form-label">MOT Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="mot_due_date" class="form-control"
                                                id="mot_due_date">
                                            <span class="text-danger form-error" id="error_mot_due_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Insurance Renewal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="insurance_renewal_date" class="form-control"
                                                id="insurance_renewal_date">
                                            <span class="text-danger form-error" id="error_insurance_renewal_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tax Renewal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tax_renewal_date" class="form-control"
                                                id="tax_renewal_date">
                                            <span class="text-danger form-error" id="error_tax_renewal_date"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Service Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="service_due_date" class="form-control"
                                                id="service_due_date">
                                            <span class="text-danger form-error" id="error_service_due_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tachograph Calibration Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tachograph_calibration_date" class="form-control"
                                                id="tachograph_calibration_date">
                                            <span class="text-danger form-error"
                                                id="error_tachograph_calibration_date"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="savereminder" class="btn btn-primary">Save Reminder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Edit Alert & Reminder Modal -->
            <div class="modal fade" id="edit_reminder">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Alert & Reminder</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="edit_reminder_form" action="#">
                            @csrf
                            <input type="hidden" name="reminder_id" id="reminder_id">
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
                                            <label class="form-label">MOT Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="mot_due_date" id="edit_mot_due_date"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_mot_due_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Insurance Renewal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="insurance_renewal_date"
                                                id="edit_insurance_renewal_date" class="form-control">
                                            <span class="text-danger form-error"
                                                id="edit_error_insurance_renewal_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tax Renewal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tax_renewal_date" id="edit_tax_renewal_date"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_tax_renewal_date"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Service Due Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="service_due_date" id="edit_service_due_date"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_service_due_date"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tachograph Calibration Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tachograph_calibration_date"
                                                id="edit_tachograph_calibration_date" class="form-control">
                                            <span class="text-danger form-error"
                                                id="edit_error_tachograph_calibration_date"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="edit_reminder_form" class="btn btn-primary"
                                    id="edit_reminder_btn">
                                    Update Reminder
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
                        <form action="{{ route('reminders.import') }}" method="POST" enctype="multipart/form-data">
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
                // Add Reminder
                $('#add_reminder_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#savereminder');

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
                            $('#add_reminder').modal('hide');
                            toast_success('Reminder added successfully.');
                            reloadDatatable('#alert-reminders-table')
                            $('#add_reminder_form')[0].reset();
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
                            submitButton.prop('disabled', false).html('Save Reminder');
                        }
                    });
                });

                // Edit Reminder
                $('#edit_reminder_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='edit_error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#edit_reminder_btn');
                    let reminderId = $('#reminder_id').val();

                    submitButton.prop('disabled', true).html('Updating...');

                    $.ajax({
                        url: `${baseUrl}/updatereminder/${reminderId}`, // Adjust this route to your backend
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            $('#edit_reminder').modal('hide');
                            toast_success('Reminder updated successfully.');
                            reloadDatatable('#alert-reminders-table')
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
                            submitButton.prop('disabled', false).html('Update Reminder');
                        }
                    });
                });
            });


            function editReminder(reminder_id) {
                $.get(`${baseUrl}/editreminder/` + reminder_id, function(data) {
                    if (data.reminder) {
                        $('#reminder_id').val(data.reminder.id); // Hidden input
                        $('#edit_vehicle_id').val(data.reminder.vehicle_id);
                        $('#edit_mot_due_date').val(data.reminder.mot_due_date);
                        $('#edit_insurance_renewal_date').val(data.reminder.insurance_renewal_date);
                        $('#edit_tax_renewal_date').val(data.reminder.tax_renewal_date);
                        $('#edit_service_due_date').val(data.reminder.service_due_date);
                        $('#edit_tachograph_calibration_date').val(data.reminder.tachograph_calibration_date);

                        $('#edit_reminder').modal('show');
                    } else {
                        alert('No reminder data found for this record.');
                    }
                });
            }


            let selectedId = null;

            function deleteReminder(record_id) {
                selectedId = record_id;
                $('#delete_modal').modal('show');
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (selectedId !== null) {
                    $.ajax({
                        url: `${baseUrl}/deletereminder/${selectedId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#delete_modal').modal('hide');
                            toast_success('Alert Reminder Deleted Successfully!');
                            reloadDatatable('#alert-reminders-table')
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
                    alert('Please select at least one vehicle maintenance to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected vehicle maintenance?')) return;
                $.ajax({
                    url: '{{ route('reminders.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected alert reminder deleted successfully!');
                        reloadDatatable('#alert-reminders-table')
                    },
                    error: function() {
                        alert('Something went wrong during bulk delete.');
                    }
                });
            });
        </script>
        {!! $dataTable->scripts() !!}
    @endsection
