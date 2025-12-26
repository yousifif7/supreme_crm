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
                    class=" add_btn btn btn-white d-inline-flex align-items-center">
                    <i class="ti ti-plus me-2"></i>Vehicle
                </a>

            </div>


        </div>
        <!-- /Breadcrumb -->

        <div class="card">
            <div class="card-body p-0">
                <div class="custom-datatable-filter table-responsive">
                    <table id="vehicles-table"
                        class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold datatable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>#</th>
                                <th>Registration No.</th>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Assigned To</th>
                                <th>Category</th>
                                <th>Registration Date</th>
                                <th>Odometer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <!-- Add Vehicle -->
    <div class="modal fade" id="add_vehicle">
        <div class="modal-dialog modal-dialog-centered modal-xl">
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

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="vehicleTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="basic-tab" data-bs-toggle="tab" href="#basic-info"
                                role="tab">Basic Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="compliance-tab" data-bs-toggle="tab" href="#compliance"
                                role="tab">Compliance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="maintenance-tab" data-bs-toggle="tab" href="#maintenance"
                                role="tab">Maintenance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="roadworthiness-tab" data-bs-toggle="tab" href="#roadworthiness"
                                role="tab">Roadworthiness</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="alerts-tab" data-bs-toggle="tab" href="#alerts"
                                role="tab">Alerts</a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">

                        <!-- BASIC INFO -->
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Registration Number *</label>
                                        <input type="text" name="registration_number" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Make *</label>
                                        <input type="text" name="make" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Model *</label>
                                        <input type="text" name="model" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Year of Manufacture *</label>
                                        <input type="text" name="year_of_manufacture" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Colour *</label>
                                        <input type="text" name="colour" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Body Type *</label>
                                        <input type="text" name="body_type" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fuel Type *</label>
                                        <input type="text" name="fuel_type" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Engine Size (L) *</label>
                                        <input type="text" name="engine_size" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">VIN *</label>
                                        <input type="text" name="vin" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Odometer Reading *</label>
                                        <input type="number" name="odometer_reading" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date of First Registration *</label>
                                        <input type="date" name="first_registration_date" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Vehicle Category *</label>
                                        <input type="text" name="vehicle_category" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Assigned Driver/Department *</label>
                                        <input type="text" name="assigned_to" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COMPLIANCE -->
                        <div class="tab-pane fade" id="compliance" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">MOT Certificate Number</label>
                                    <input type="text" name="mot_certificate_number" class="form-control mb-3">

                                    <label class="form-label">MOT Expiry Date</label>
                                    <input type="date" name="mot_expiry_date" class="form-control mb-3">

                                    <label class="form-label">Insurance Provider</label>
                                    <input type="text" name="insurance_provider" class="form-control mb-3">

                                    <label class="form-label">Insurance Policy Number</label>
                                    <input type="text" name="insurance_policy_number" class="form-control mb-3">

                                    <label class="form-label">Insurance Expiry Date</label>
                                    <input type="date" name="insurance_expiry_date" class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vehicle Tax Status</label>
                                    <input type="text" name="vehicle_tax_status" class="form-control mb-3">

                                    <label class="form-label">Tax Expiry Date</label>
                                    <input type="date" name="tax_expiry_date" class="form-control mb-3">

                                    <label class="form-label">Tax Class</label>
                                    <input type="text" name="tax_class" class="form-control mb-3">

                                    <label class="form-label">V5C Logbook Reference Number</label>
                                    <input type="text" name="v5c_logbook_reference_number"
                                        class="form-control mb-3">

                                    <label class="form-label">LEZ/ULEZ Compliant</label>
                                    <select name="lez_ulez_compliant" class="form-control mb-3">
                                        <option value="">-- Select --</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>

                                    <label class="form-label">Tachograph Certificate Number</label>
                                    <input type="text" name="tachograph_certificate_number"
                                        class="form-control mb-3">

                                    <label class="form-label">Tachograph Calibration Expiry</label>
                                    <input type="date" name="tachograph_calibration_expiry"
                                        class="form-control mb-3">
                                </div>
                            </div>
                        </div>

                        <!-- MAINTENANCE -->
                        <div class="tab-pane fade" id="maintenance" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Last Service Date</label>
                                    <input type="date" name="last_service_date" class="form-control mb-3">

                                    <label class="form-label">Next Service Due</label>
                                    <input type="date" name="next_service_due_date" class="form-control mb-3">

                                    <label class="form-label">Work Type</label>
                                    <input type="text" name="work_type" class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Maintenance Date</label>
                                    <input type="date" name="maintenance_date" class="form-control mb-3">

                                    <label class="form-label">Garage Provider</label>
                                    <input type="text" name="garage_provider" class="form-control mb-3">

                                    <label class="form-label">Reported By</label>
                                    <input type="text" name="reported_by" class="form-control mb-3">

                                    <label class="form-label">Date Reported</label>
                                    <input type="date" name="date_reported" class="form-control mb-3">

                                    <label class="form-label">Resolution Status</label>
                                    <select name="resolution_status" class="form-control mb-3">
                                        <option value="">-- Select --</option>
                                        <option value="pending">Pending</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="in_progress">In Progress</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ROADWORTHINESS -->
                        <div class="tab-pane fade" id="roadworthiness" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Date Completed</label>
                                    <input type="date" name="date_completed" class="form-control mb-3">

                                    <label class="form-label">Checked By</label>
                                    <input type="text" name="checked_by" class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Defects Found</label>
                                    <textarea name="defects_found" class="form-control mb-3"></textarea>

                                    <label class="form-label">Corrective Action Taken</label>
                                    <textarea name="corrective_action_taken" class="form-control mb-3"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ALERTS -->
                        <div class="tab-pane fade" id="alerts" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">MOT Due Date</label>
                                    <input type="date" name="mot_due_date" class="form-control mb-3">

                                    <label class="form-label">Insurance Renewal Date</label>
                                    <input type="date" name="insurance_renewal_date" class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tax Renewal Date</label>
                                    <input type="date" name="tax_renewal_date" class="form-control mb-3">

                                    <label class="form-label">Service Due Date</label>
                                    <input type="date" name="service_due_date" class="form-control mb-3">

                                    <label class="form-label">Tachograph Calibration Date</label>
                                    <input type="date" name="tachograph_calibration_date"
                                        class="form-control mb-3">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="add_vehicle-form" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Vehicle -->
    <div class="modal fade" id="edit_vehicle">
        <div class="modal-dialog modal-dialog-centered modal-xl">
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

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="editVehicleTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#edit-basic-info">Basic Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#edit-compliance">Compliance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#edit-maintenance">Maintenance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#edit-roadworthiness">Roadworthiness</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#edit-alerts">Alerts</a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">
                        <!-- BASIC INFO -->
                        <div class="tab-pane fade show active" id="edit-basic-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Registration Number *</label>
                                    <input type="text" name="registration_number" id="registration_number"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_registration_number"></span>

                                    <label class="form-label">Make *</label>
                                    <input type="text" name="make" id="make" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_make"></span>

                                    <label class="form-label">Model *</label>
                                    <input type="text" name="model" id="model" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_model"></span>

                                    <label class="form-label">Year of Manufacture *</label>
                                    <input type="text" name="year_of_manufacture" id="year_of_manufacture"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_year_of_manufacture"></span>

                                    <label class="form-label">Colour *</label>
                                    <input type="text" name="colour" id="colour" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_colour"></span>

                                    <label class="form-label">Body Type *</label>
                                    <input type="text" name="body_type" id="body_type" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_body_type"></span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fuel Type *</label>
                                    <input type="text" name="fuel_type" id="fuel_type" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_fuel_type"></span>

                                    <label class="form-label">Engine Size (L) *</label>
                                    <input type="text" name="engine_size" id="engine_size"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_engine_size"></span>

                                    <label class="form-label">VIN *</label>
                                    <input type="text" name="vin" id="vin" class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_vin"></span>

                                    <label class="form-label">Odometer Reading *</label>
                                    <input type="number" name="odometer_reading" id="odometer_reading"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_odometer_reading"></span>

                                    <label class="form-label">First Registration Date *</label>
                                    <input type="date" name="first_registration_date" id="first_registration_date"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error"
                                        id="editerror_first_registration_date"></span>

                                    <label class="form-label">Vehicle Category *</label>
                                    <input type="text" name="vehicle_category" id="vehicle_category"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_vehicle_category"></span>

                                    <label class="form-label">Assigned To *</label>
                                    <input type="text" name="assigned_to" id="assigned_to"
                                        class="form-control mb-3">
                                    <span class="text-danger form-error" id="editerror_assigned_to"></span>
                                </div>
                            </div>
                        </div>

                        <!-- COMPLIANCE -->
                        <div class="tab-pane fade" id="edit-compliance">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">MOT Certificate Number</label>
                                    <input type="text" name="mot_certificate_number" id="mot_certificate_number"
                                        class="form-control mb-3">

                                    <label class="form-label">MOT Expiry Date</label>
                                    <input type="date" name="mot_expiry_date" id="mot_expiry_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Insurance Provider</label>
                                    <input type="text" name="insurance_provider" id="insurance_provider"
                                        class="form-control mb-3">

                                    <label class="form-label">Insurance Policy Number</label>
                                    <input type="text" name="insurance_policy_number" id="insurance_policy_number"
                                        class="form-control mb-3">

                                    <label class="form-label">Insurance Expiry Date</label>
                                    <input type="date" name="insurance_expiry_date" id="insurance_expiry_date"
                                        class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vehicle Tax Status</label>
                                    <input type="text" name="vehicle_tax_status" id="vehicle_tax_status"
                                        class="form-control mb-3">

                                    <label class="form-label">Tax Expiry Date</label>
                                    <input type="date" name="tax_expiry_date" id="tax_expiry_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Tax Class</label>
                                    <input type="text" name="tax_class" id="tax_class" class="form-control mb-3">

                                    <label class="form-label">V5C Logbook Reference Number</label>
                                    <input type="text" name="v5c_logbook_reference_number"
                                        id="v5c_logbook_reference_number" class="form-control mb-3">

                                    <label class="form-label">LEZ/ULEZ Compliant</label>
                                    <select name="lez_ulez_compliant" id="lez_ulez_compliant"
                                        class="form-control mb-3">
                                        <option value="">-- Select --</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>

                                    <label class="form-label">Tachograph Certificate Number</label>
                                    <input type="text" name="tachograph_certificate_number"
                                        id="tachograph_certificate_number" class="form-control mb-3">

                                    <label class="form-label">Tachograph Calibration Expiry</label>
                                    <input type="date" name="tachograph_calibration_expiry"
                                        id="tachograph_calibration_expiry" class="form-control mb-3">
                                </div>
                            </div>
                        </div>

                        <!-- MAINTENANCE -->
                        <div class="tab-pane fade" id="edit-maintenance">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Last Service Date</label>
                                    <input type="date" name="last_service_date" id="last_service_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Next Service Due</label>
                                    <input type="date" name="next_service_due_date" id="next_service_due_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Work Type</label>
                                    <input type="text" name="work_type" id="work_type" class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Maintenance Date</label>
                                    <input type="date" name="maintenance_date" id="maintenance_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Garage Provider</label>
                                    <input type="text" name="garage_provider" id="garage_provider"
                                        class="form-control mb-3">

                                    <label class="form-label">Reported By</label>
                                    <input type="text" name="reported_by" id="reported_by"
                                        class="form-control mb-3">

                                    <label class="form-label">Date Reported</label>
                                    <input type="date" name="date_reported" id="date_reported"
                                        class="form-control mb-3">

                                    <label class="form-label">Resolution Status</label>
                                    <select name="resolution_status" id="resolution_status"
                                        class="form-control mb-3">
                                        <option value="">-- Select --</option>
                                        <option value="pending">Pending</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="in_progress">In Progress</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ROADWORTHINESS -->
                        <div class="tab-pane fade" id="edit-roadworthiness">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Date Completed</label>
                                    <input type="date" name="date_completed" id="date_completed"
                                        class="form-control mb-3">

                                    <label class="form-label">Checked By</label>
                                    <input type="text" name="checked_by" id="checked_by"
                                        class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Defects Found</label>
                                    <textarea name="defects_found" id="defects_found" class="form-control mb-3"></textarea>

                                    <label class="form-label">Corrective Action Taken</label>
                                    <textarea name="corrective_action_taken" id="corrective_action_taken" class="form-control mb-3"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ALERTS -->
                        <div class="tab-pane fade" id="edit-alerts">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">MOT Due Date</label>
                                    <input type="date" name="mot_due_date" id="mot_due_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Insurance Renewal Date</label>
                                    <input type="date" name="insurance_renewal_date" id="insurance_renewal_date"
                                        class="form-control mb-3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tax Renewal Date</label>
                                    <input type="date" name="tax_renewal_date" id="tax_renewal_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Service Due Date</label>
                                    <input type="date" name="service_due_date" id="service_due_date"
                                        class="form-control mb-3">

                                    <label class="form-label">Tachograph Calibration Date</label>
                                    <input type="date" name="tachograph_calibration_date"
                                        id="tachograph_calibration_date" class="form-control mb-3">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="edit_vehicle-form" id="editvehicle"
                            class="btn btn-primary">Update</button>
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
                    closeBsModal('#add_vehicle');
                    toast_success('Vehicle Added Successfully');
                    reloadDatatable('#vehicles-table');
                    form.reset();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
    
                        Object.values(errors).forEach(messages => {
                            messages.forEach(message => {
                                toast_danger(message);
                            });
                        });
                    } else {
                        toast_danger('Something went wrong.');
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
                    closeBsModal('#edit_vehicle');
                    toast_success('Vehicle Updated Successfully!');
                    reloadDatatable('#vehicles-table');
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
    
                        Object.values(errors).forEach(messages => {
                            messages.forEach(message => {
                                toast_danger(message);
                            });
                        });
                    } else {
                        toast_danger('Something went wrong.');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false).html('Update');
                }
            });
        });
    });


    function editVehicle(record_id) {
        $.get(`/editvehicle/${record_id}`, function(data) {
            if (data.vehicle) {
                // Basic vehicle fields
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

                // Compliance
                if (data.compliance) {
                    $('#mot_certificate_number').val(data.compliance.mot_certificate_number);
                    $('#mot_expiry_date').val(data.compliance.mot_expiry_date);
                    $('#insurance_provider').val(data.compliance.insurance_provider);
                    $('#insurance_policy_number').val(data.compliance.insurance_policy_number);
                    $('#insurance_expiry_date').val(data.compliance.insurance_expiry_date);
                    $('#vehicle_tax_status').val(data.compliance.vehicle_tax_status);
                    $('#tax_expiry_date').val(data.compliance.tax_expiry_date);
                    $('#tax_class').val(data.compliance.tax_class);
                    $('#v5c_logbook_reference_number').val(data.compliance.v5c_logbook_reference_number);
                    $('#lez_ulez_compliant').prop('checked', data.compliance.lez_ulez_compliant);
                    $('#tachograph_certificate_number').val(data.compliance.tachograph_certificate_number);
                    $('#tachograph_calibration_expiry').val(data.compliance.tachograph_calibration_expiry);
                }

                // Maintenance
                if (data.maintenance) {
                    $('#last_service_date').val(data.maintenance.last_service_date);
                    $('#next_service_due_date').val(data.maintenance.next_service_due_date);
                    $('#work_type').val(data.maintenance.work_type);
                    $('#maintenance_date').val(data.maintenance.maintenance_date);
                    $('#garage_provider').val(data.maintenance.garage_provider);
                    $('#reported_by').val(data.maintenance.reported_by);
                    $('#date_reported').val(data.maintenance.date_reported);
                    $('#resolution_status').val(data.maintenance.resolution_status);
                }

                // Roadworthiness Check
                if (data.roadworthiness) {
                    $('#date_completed').val(data.roadworthiness.date_completed);
                    $('#checked_by').val(data.roadworthiness.checked_by);
                    $('#defects_found').val(data.roadworthiness.defects_found);
                    $('#corrective_action_taken').val(data.roadworthiness.corrective_action_taken);
                }

                // Alerts
                if (data.alerts) {
                    $('#mot_due_date').val(data.alerts.mot_due_date);
                    $('#insurance_renewal_date').val(data.alerts.insurance_renewal_date);
                    $('#tax_renewal_date').val(data.alerts.tax_renewal_date);
                    $('#service_due_date').val(data.alerts.service_due_date);
                    $('#tachograph_calibration_date').val(data.alerts.tachograph_calibration_date);
                }

                $('#edit_vehicle').modal('show');
            }
        });
    }


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
                    closeBsModal('#delete_modal');
                    toast_success('Vehicle Deleted Successfully!');
                    reloadDatatable('#vehicles-table');
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
            toast_danger('Please select at least one vehicle to delete.');
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
                toast_success('Selected vehicles deleted successfully!');
                reloadDatatable('#vehicles-table');
            },
            error: function() {
                toast_danger('Something went wrong during bulk delete.');
            }
        });
    });


    $(function() {
        var table = $('#vehicles-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('vehicle.data') }}",
            scrollX: true,
            pageLength: 15,
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'number',
                    name: 'number',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'registration_number',
                    name: 'registration_number'
                },
                {
                    data: 'make',
                    name: 'make'
                },
                {
                    data: 'model',
                    name: 'model'
                },
                {
                    data: 'assigned_to',
                    name: 'assigned_to'
                },
                {
                    data: 'vehicle_category',
                    name: 'vehicle_category'
                },
                {
                    data: 'first_registration_date',
                    name: 'first_registration_date'
                },
                {
                    data: 'odometer_reading',
                    name: 'odometer_reading',
                    orderable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            drawCallback: function(settings) {
                feather.replace(); // your icons
                var api = this.api();
                var start = api.page.info().start;
                api.column(1, {
                    page: 'current'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = start + i + 1; // numbering
                });
            },
            headerCallback: function(thead, data, start, end, display) {
                $(thead).addClass('thead-light');
            }
        });

        // select all checkboxes
        $('#selectAll').on('click', function() {
            var checked = this.checked;
            $('.dT-row-checkbox').prop('checked', checked);
        });
    });
</script>
