@extends('layouts.app')
@section('title', 'CRM - Vehicle Compliances')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Legal & Compliances Documents</h2>
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
                                <a href="{{ route('compliances.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('compliances.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_compliance"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Compliance
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
                                    <th>Vehicle RN</th>
                                    <th>MOT Certificate</th>
                                    <th>MOT Expiry</th>
                                    <th>Insurance Provider</th>
                                    <th>Insurance Expiry</th>
                                    <th>Tax Status</th>
                                    <th>Tax Expiry</th>
                                    <th>LEZ/ULEZ</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($compliances as $compliance)
                                    <tr>
                                        <td><input type="checkbox" class="dT-row-checkbox" value="{{ $compliance->id }}">
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $compliance->vehicle->registration_number }}</td>
                                        <td>{{ $compliance->mot_certificate_number }}</td>
                                        <td>{{ $compliance->mot_expiry_date }}</td>
                                        <td>{{ $compliance->insurance_provider }}</td>
                                        <td>{{ $compliance->insurance_expiry_date }}</td>
                                        <td>{{ $compliance->vehicle_tax_status }}</td>
                                        <td>{{ $compliance->tax_expiry_date }}</td>
                                        <td>
                                            @if ($compliance->lez_ulez_compliant)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-danger">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <a href="#" class="me-2"
                                                    onclick="editCompliance({{ $compliance->id }})">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="javascript:void(0);"
                                                    onclick="deleteCompliance({{ $compliance->id }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="card-footer d-flex justify-content-center">
                            {{ $compliances->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <!-- Add Compliance Modal -->
        <div class="modal fade" id="add_compliance">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Compliance Info</h5>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="add_compliance_form" action="{{ route('compliances.store') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                        <select name="vehicle_id" class="form-control">
                                            <option value="">Select vehicle</option>
                                            @foreach ($vehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger form-error" id="error_vehicle_id"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">MOT Certificate Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="mot_certificate_number" class="form-control">
                                        <span class="text-danger form-error" id="error_mot_certificate_number"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">MOT Expiry Date <span class="text-danger">*</span></label>
                                        <input type="date" name="mot_expiry_date" class="form-control">
                                        <span class="text-danger form-error" id="error_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Provider <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="insurance_provider" class="form-control">
                                        <span class="text-danger form-error" id="error_insurance_provider"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Policy Number <span class="text-danger">*</span></label>
                                        <input type="text" name="insurance_policy_number" class="form-control">
                                        <span class="text-danger form-error" id="error_insurance_policy_number"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="insurance_expiry_date" class="form-control">
                                        <span class="text-danger form-error" id="error_insurance_expiry_date"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vehicle Tax Status <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="vehicle_tax_status" class="form-control">
                                        <span class="text-danger form-error" id="error_vehicle_tax_status"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tax Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tax_expiry_date" class="form-control">
                                        <span class="text-danger form-error" id="error_tax_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tax Class <span class="text-danger">*</span></label>
                                        <input type="text" name="tax_class" class="form-control">
                                        <span class="text-danger form-error" id="error_tax_class"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">V5C Logbook Reference Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="v5c_logbook_reference_number" class="form-control">
                                        <span class="text-danger form-error"
                                            id="error_v5c_logbook_reference_number"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">LEZ/ULEZ Compliant? <span
                                                class="text-danger">*</span></label>
                                        <select name="lez_ulez_compliant" class="form-select">
                                            <option value="" selected hidden>Choose...</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        <span class="text-danger form-error" id="error_lez_ulez_compliant"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tachograph Certificate Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="tachograph_certificate_number" class="form-control">
                                        <span class="text-danger form-error"
                                            id="error_tachograph_certificate_number"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Calibration Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tachograph_calibration_expiry" class="form-control">
                                        <span class="text-danger form-error"
                                            id="error_tachograph_calibration_expiry"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="add_compliance_form" id="savevehicle"
                                class="btn btn-primary">Save Compliance</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Compliance Modal -->
        <div class="modal fade" id="edit_compliance">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Vehicle Compliance</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_compliance_form" action="#">
                        @csrf
                        <input type="hidden" name="edit_vehicle_id" id="edit_vehicle_id">
                        <div class="modal-body pb-0">
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                        <select name="vehicle_id" id="vehicle_id" class="form-control">
                                            <option value="">Select vehicle</option>
                                            @foreach ($vehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger form-error" id="error_vehicle_id"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">MOT Certificate Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="mot_certificate_number" id="mot_certificate_number"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_certificate_number"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">MOT Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="mot_expiry_date" id="mot_expiry_date"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Provider <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="insurance_provider" id="insurance_provider"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Policy Number <span class="text-danger">*</span></label>
                                        <input type="text" name="insurance_policy_number" id="insurance_policy_number"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="insurance_expiry_date" id="insurance_expiry_date"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vehicle Tax Status <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="vehicle_tax_status" id="vehicle_tax_status"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tax Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tax_expiry_date" id="tax_expiry_date"
                                            class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tax Class <span class="text-danger">*</span></label>
                                        <input type="text" name="tax_class" id="tax_class" class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">V5C Logbook Reference Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="v5c_logbook_reference_number"
                                            id="v5c_logbook_reference_number" class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">LEZ/ULEZ Compliant <span
                                                class="text-danger">*</span></label>
                                        <select name="lez_ulez_compliant" id="lez_ulez_compliant" class="form-select">
                                            <option value="">-- Choose --</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tachograph Certificate Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="tachograph_certificate_number"
                                            id="tachograph_certificate_number" class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Calibration Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tachograph_calibration_expiry"
                                            id="tachograph_calibration_expiry" class="form-control">
                                        <span class="text-danger form-error" id="editerror_mot_expiry_date"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="edit_compliance_form" class="btn btn-primary"
                                id="editvehicle">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- /Edit Vehicle -->

        <!-- Add Vehicle Compliances Success -->
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
                                        <a href="{{ url('vehicle_compliances') }}" class="btn btn-dark w-100">Back to
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
                    <form action="{{ route('compliances.import') }}" method="POST" enctype="multipart/form-data">
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
            $('#add_compliance_form').on('submit', function(e) {
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
                        $('#add_compliance').modal('hide');
                        $('#success_message').html('Vehicle Compliance Added Successfully');
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

            $('#edit_compliance_form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='editerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editvehicle'); // Update button ID

                let vehicleId = $('#edit_vehicle_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatecompliance/${vehicleId}`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#edit_compliance').modal('hide');
                        $('#success_message').html('Vehicle Compliance Updated Successfully!');
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


        function editCompliance(vehicle_id) {
            $.get('/editcompliance/' + vehicle_id, function(data) {
                if (data.compliance) {
                    $('#edit_vehicle_id').val(data.compliance.id);
                    $('#vehicle_id').val(data.compliance.vehicle_id);
                    $('#mot_certificate_number').val(data.compliance.mot_certificate_number);
                    $('#mot_expiry_date').val(data.compliance.mot_expiry_date);

                    $('#insurance_provider').val(data.compliance.insurance_provider);
                    $('#insurance_policy_number').val(data.compliance.insurance_policy_number);
                    $('#insurance_expiry_date').val(data.compliance.insurance_expiry_date);

                    $('#vehicle_tax_status').val(data.compliance.vehicle_tax_status);
                    $('#tax_expiry_date').val(data.compliance.tax_expiry_date);
                    $('#tax_class').val(data.compliance.tax_class);

                    $('#v5c_logbook_reference_number').val(data.compliance.v5c_logbook_reference_number);
                    $('#lez_ulez_compliant').val(data.compliance.lez_ulez_compliant);
                    $('#tachograph_certificate_number').val(data.compliance.tachograph_certificate_number);
                    $('#tachograph_calibration_expiry').val(data.compliance.tachograph_calibration_expiry);

                    $('#edit_compliance').modal('show');
                } else {
                    alert('No compliance data found for this vehicle.');
                }
            });
        }

        let selectedId = null;

        function deleteCompliance(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deletecompliance/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');
                        $('#success_message').html('Vehicle Compliance Deleted Successfully!');
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
                url: '{{ route('compliances.bulkDelete') }}',
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
