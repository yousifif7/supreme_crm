@extends('layouts.app')
@section('title', 'CRM - Vehicle Details')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Vehicles Details</h2>
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
                                <a href="{{ route('vehicles.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('vehicles.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_vehicle"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Vehicle
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
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>

                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Registration No.</th>
                                    <th>Make</th>
                                    <th>Model</th>
                                    <th>Assigned Driver/Dept</th>
                                    <th>Category</th>
                                    <th>Actions</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vehicles as $vehicle)
                                    <tr>
                                        <td><input type="checkbox" class="dT-row-checkbox" value="{{ $vehicle->id }}">
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $vehicle->registration_number }}</td>
                                        <td>{{ $vehicle->make }}</td>
                                        <td>{{ $vehicle->model }}</td>
                                        <td>{{ $vehicle->assigned_to }}</td>
                                        <td>{{ $vehicle->vehicle_category }}</td>
                                        <td>
                                            <div class="action-icon d-inline-flex">

                                                <a href="#" class="me-2"
                                                    onclick="editVehicle({{ $vehicle->id }})"><i
                                                        class="ti ti-edit"></i></a>
                                                <a href="javascript:void(0);"
                                                    onclick="deleteVehicle({{ $vehicle->id }})"><i
                                                        class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $vehicles->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Add Vehicle -->
        <div class="modal fade" id="add_vehicle">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Vehicle</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="add_vehicle-form" action="{{ route('vehicles.store') }}">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Registration Number <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="registration_number"
                                                            class="form-control" placeholder="Enter Registration Number">
                                                        <span class="text-danger form-error"
                                                            id="error_registration_number"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Make <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="make" class="form-control"
                                                            placeholder="Enter Make">
                                                        <span class="text-danger form-error" id="error_make"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Model <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="model" class="form-control"
                                                            placeholder="Enter Model">
                                                        <span class="text-danger form-error" id="error_model"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Year of Manufacture <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="year_of_manufacture"
                                                            class="form-control" placeholder="e.g. 2020">
                                                        <span class="text-danger form-error"
                                                            id="error_year_of_manufacture"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Colour <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="colour" class="form-control"
                                                            placeholder="Enter Colour">
                                                        <span class="text-danger form-error" id="error_colour"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Body Type <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="body_type" class="form-control"
                                                            placeholder="e.g. Car, Van">
                                                        <span class="text-danger form-error" id="error_body_type"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Fuel Type <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="fuel_type" class="form-control"
                                                            placeholder="e.g. Diesel, Petrol">
                                                        <span class="text-danger form-error" id="error_fuel_type"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Engine Size (L) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="engine_size" class="form-control"
                                                            placeholder="e.g. 2.0">
                                                        <span class="text-danger form-error"
                                                            id="error_engine_size"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">VIN <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="vin" class="form-control"
                                                            placeholder="Vehicle Identification Number">
                                                        <span class="text-danger form-error" id="error_vin"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Odometer Reading <span
                                                                class="text-danger">*</span></label>
                                                        <input type="number" name="odometer_reading"
                                                            class="form-control" placeholder="e.g. 100000">
                                                        <span class="text-danger form-error"
                                                            id="error_odometer_reading"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Date of First Registration <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="first_registration_date"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_first_registration_date"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Vehicle Category <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="vehicle_category"
                                                            class="form-control" placeholder="e.g. Private, Commercial">
                                                        <span class="text-danger form-error"
                                                            id="error_vehicle_category"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Assigned Driver or Department <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="assigned_to" class="form-control"
                                                            placeholder="Enter name or department">
                                                        <span class="text-danger form-error"
                                                            id="error_assigned_to"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_vehicle-form" id="savevehicle"
                                        class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Edit Vehicle -->
        <div class="modal fade" id="edit_vehicle">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Vehicle</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_vehicle-form">
                        @csrf
                        <input type="hidden" name="vehicle_id" id="vehicle_id">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Registration Number <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="registration_number"
                                                            id="registration_number" class="form-control"
                                                            placeholder="Enter Registration Number">
                                                        <span class="text-danger form-error"
                                                            id="editerror_registration_number"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Make <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="make" id="make"
                                                            class="form-control" placeholder="Enter Make">
                                                        <span class="text-danger form-error" id="editerror_make"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Model <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="model" id="model"
                                                            class="form-control" placeholder="Enter Model">
                                                        <span class="text-danger form-error" id="editerror_model"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Year of Manufacture <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="year_of_manufacture"
                                                            id="year_of_manufacture" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_year_of_manufacture"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Colour <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="colour" id="colour"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="editerror_colour"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Body Type <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="body_type" id="body_type"
                                                            class="form-control" placeholder="e.g. Car, Van">
                                                        <span class="text-danger form-error"
                                                            id="editerror_body_type"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Fuel Type <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="fuel_type" id="fuel_type"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_fuel_type"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Engine Size (L) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="engine_size" id="engine_size"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_engine_size"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">VIN <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="vin" id="vin"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="editerror_vin"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Odometer Reading <span
                                                                class="text-danger">*</span></label>
                                                        <input type="number" name="odometer_reading"
                                                            id="odometer_reading" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_odometer_reading"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">First Registration Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="first_registration_date"
                                                            id="first_registration_date" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_first_registration_date"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Vehicle Category <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="vehicle_category"
                                                            id="vehicle_category" class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_vehicle_category"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Assigned To (Driver/Department) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="assigned_to" id="assigned_to"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="editerror_assigned_to"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_vehicle-form" id="editvehicle"
                                        class="btn btn-primary">Update</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- /Edit Vehicle -->

        <!-- Add Vehicle Success -->
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
                                        <a href="{{ url('vehicle_details') }}" class="btn btn-dark w-100">Back to
                                            List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add Vehicle Success -->

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                            <i class="ti ti-trash-x fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Delete</h4>
                        <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.</p>
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
                    <form action="{{ route('vehicles.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="import_file" class="form-control" required>
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
            $('#add_vehicle-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#savevehicle'); // Update button ID

                // Disable button and show loading
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
                        $('#add_vehicle').modal('hide');
                        $('#success_message').html('Vehicle Added Successfully');
                        $('#success_modal').modal('show');
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
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });

            $('#edit_vehicle-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='editerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editvehicle'); // Update button ID

                let vehicleId = $('#vehicle_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatevehicle/${vehicleId}`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#edit_vehicle').modal('hide');
                        $('#success_message').html('Vehicle Updated Successfully!');
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]);
                            });
                        } else {
                            alert('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });
        });


        function editVehicle(record_id) {
            $.get('/editvehicle/' + record_id, function(data) {
                if (data.vehicle) {
                    $('#vehicle_id').val(data.vehicle.id);
                    $('#registration_number').val(data.vehicle.registration_number);
                    $('#make').val(data.vehicle.make);
                    $('#model').val(data.vehicle.model);
                    $('#year_of_manufacture').val(data.vehicle.year_of_manufacture);
                    $('#colour').val(data.vehicle.colour);
                    $('#body_type').val(data.vehicle.body_type);
                    $('#fuel_type').val(data.vehicle.fuel_type);
                    $('#engine_size').val(data.vehicle.engine_size);
                    $('#vin').val(data.vehicle.vin);
                    $('#odometer_reading').val(data.vehicle.odometer_reading);
                    $('#first_registration_date').val(data.vehicle.first_registration_date);
                    $('#vehicle_category').val(data.vehicle.vehicle_category);
                    $('#assigned_to').val(data.vehicle.assigned_to);

                    $('#edit_vehicle').modal('show');
                }
            });
        }

        let selectedId = null;

        function deleteVehicle(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deletevehicle/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');
                        $('#success_message').html('Vehicle Deleted Successfully!');
                        $('#success_modal').modal('show');
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
                alert('Please select at least one vehicle to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected vehicles?')) return;

            $.ajax({
                url: '{{ route('vehicles.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#success_message').text('Selected vehicles deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
                }
            });
        });
    </script>
@endsection
