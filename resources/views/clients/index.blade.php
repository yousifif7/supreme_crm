@extends('layouts.app')
@section('title', 'CRM - Client')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Clients</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_client"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Client
                    </a>


                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">


                        <!-- /Search -->


                    </div>
                    <div class="sort-box">
                        <select name="" id="" class="form-control">
                            <option value="" hidden>Sort Clients</option>
                            <option value="">All</option>
                            <option value="">Coordinators</option>
                            <option value="">Archieved</option>
                        </select>
                        <i class="ti ti-chevron-down"></i>
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

                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Contact Number</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>

                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($clients->currentPage() - 1) * $clients->perPage() + 1; @endphp
                                @foreach ($clients as $client)
                                    <tr>

                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <div class="d-flex align-items-center file-name-icon">

                                                <div class="ms-2">
                                                    <h6 class="fw-medium"><a
                                                            href="https://smarthr.co.in/demo/html/template/client-details.html">{{ $client->client_name }}</a>
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->address }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->contact_number }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->fax }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->email }}
                                        </td>

                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <button class="sites_action-btn">Managers</button>
                                                <button class="sites_action-btn">Logs</button>



                                                <a href="#" class="me-2"
                                                    onclick="editClient({{ $client->id }})"><i class="ti ti-edit"></i></a>
                                                <a href="javascript:void(0);" onclick="deleteClient({{ $client->id }})"><i
                                                        class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $clients->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Add Client -->
        <div class="modal fade" id="add_client">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Client</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="add_client-form">
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
                                                        <label class="form-label">Client Name</label>
                                                        <input type="text" name="client_name" class="form-control"
                                                            placeholder="Enter Client Name">
                                                        <span class="text-danger form-error" id="error_client_name"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address</label>
                                                        <textarea class="form-control" name="address" rows="2"></textarea>
                                                        <span class="text-danger form-error" id="error_address"></span>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number</label>
                                                                <input type="text" name="contact_number"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Fax</label>
                                                                <input type="text" name="fax" class="form-control"
                                                                    placeholder="Enter Contact Fax"><span
                                                                    class="text-danger form-error" id="error_fax"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email</label>
                                                                <input type="email" name="email" class="form-control"
                                                                    placeholder="Enter Contact Email">
                                                                <span class="text-danger form-error"
                                                                    id="error_email"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 1:</label>
                                                                <input type="file" name="doc_1"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_1"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 2:</label>
                                                                <input type="file" name="doc_2"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_2"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 3:</label>
                                                                <input type="file" name="doc_3"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_3"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Company</label>
                                                        <select class="form-select" name="company_id">
                                                            <option value="">-- choose --</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <input type="text" class="form-control"
                                                                    placeholder="example">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <input type="password" class="form-control"
                                                                    placeholder="••••••">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <button type="button" class="btn btn-primary w-100">Open
                                                                    Client system</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Terms</label>
                                                        <select class="form-select" name="invoice_terms">
                                                            <option value="">Fortnightly Invoice</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms</label>
                                                        <textarea class="form-control" name="payment_terms" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="error_payment_terms"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract Start:</label>
                                                                <input type="date" name="contract_start"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_contract_start"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract End:</label>
                                                                <input type="date" name="contract_end"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_contract_end"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Guard rate:</label>
                                                                <input type="text" name="guard_rate"
                                                                    class="form-control" placeholder="Enter Guard rate">
                                                                <span class="text-danger form-error"
                                                                    id="error_guard_rate"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Office rate:</label>
                                                                <input type="text" name="office_rate"
                                                                    class="form-control" placeholder="Enter Office rate">
                                                                <span class="text-danger form-error"
                                                                    id="error_office_rate"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center justify-content-between">
                                                        <div class="col-md-3">
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" name="vat_registered"
                                                                    class="form-check-input" id="vatCheck">
                                                                <label class="form-check-label" for="vatCheck">VAT
                                                                    Registered?</label>

                                                            </div>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="mb-3">
                                                                <input type="text" name="vat" class="form-control"
                                                                    placeholder="Enter VAT Registration Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_vat"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_client-form" id="saveclient"
                                        class="btn btn-primary">Save </button>
                                </div>
                            </div>
                        </div>


                    </form>

                </div>
            </div>
        </div>
        <!-- /Add Client -->

        <!-- Edit Client -->
        <div class="modal fade" id="edit_client">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Client</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_client-form">
                        @csrf
                        <input type="hidden" name="client_id" id="client_id">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Client Name</label>
                                                        <input type="text" name="client_name" id="client_name"
                                                            class="form-control" placeholder="Enter Client Name">
                                                        <span class="text-danger form-error"
                                                            id="error_client_name"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address</label>
                                                        <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                                                        <span class="text-danger form-error" id="error_address"></span>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number</label>
                                                                <input type="text" name="contact_number"
                                                                    id="contact_number" class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Fax</label>
                                                                <input type="text" name="fax" id="fax"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Fax"><span
                                                                    class="text-danger form-error" id="error_fax"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email</label>
                                                                <input type="email" name="email" id="email"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Email">
                                                                <span class="text-danger form-error"
                                                                    id="error_email"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Document Previews -->
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 1:</label>
                                                                <input type="file" name="doc_1" id="doc_1"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_1"></span>
                                                                <div id="doc_1_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 2:</label>
                                                                <input type="file" name="doc_2" id="doc_2"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_2"></span>
                                                                <div id="doc_2_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 3:</label>
                                                                <input type="file" name="doc_3" id="doc_3"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_doc_3"></span>
                                                                <div id="doc_3_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Other Fields -->
                                                    <div class="mb-3">
                                                        <label class="form-label">Company</label>
                                                        <select class="form-select" name="company_id" id="company_id">
                                                            <option value="">-- choose --</option>
                                                        </select>
                                                        <span class="text-danger form-error" id="error_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Terms</label>
                                                        <select class="form-select" name="invoice_terms"
                                                            id="invoice_terms">
                                                            <option value="">Fortnightly Invoice</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms</label>
                                                        <textarea class="form-control" name="payment_terms" id="payment_terms" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="error_payment_terms"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract Start:</label>
                                                                <input type="date" name="contract_start"
                                                                    id="contract_start" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_contract_start"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract End:</label>
                                                                <input type="date" name="contract_end"
                                                                    id="contract_end" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_contract_end"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Guard rate:</label>
                                                                <input type="text" name="guard_rate" id="guard_rate"
                                                                    class="form-control" placeholder="Enter Guard rate">
                                                                <span class="text-danger form-error"
                                                                    id="error_guard_rate"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Office rate:</label>
                                                                <input type="text" name="office_rate" id="office_rate"
                                                                    class="form-control" placeholder="Enter Office rate">
                                                                <span class="text-danger form-error"
                                                                    id="error_office_rate"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center justify-content-between">
                                                        <div class="col-md-3">
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" name="vat_registered"
                                                                    class="form-check-input" id="vatCheck">
                                                                <label class="form-check-label" for="vatCheck">VAT
                                                                    Registered?</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="mb-3">
                                                                <input type="text" name="vat" id="vat"
                                                                    class="form-control"
                                                                    placeholder="Enter VAT Registration Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_vat"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_client-form" id="editclient"
                                        class="btn btn-primary">Update </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Edit Client -->

        <!-- Add Client Success -->
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
                                        <a href="{{ url('clients') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add Client Success -->

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

    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#add_client-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveclient'); // Add an ID to your submit button

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
                        $('#add_client').modal('hide');
                        $('#success_message').html('Client Added Successfully')
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
            $('#edit_client-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editclient'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let clientId = $('#client_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `/updateclient/${clientId}`, // OR use Laravel Blade: `{{ url('clients') }}/` + clientId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#edit_client').modal('hide');
                        $('#success_message').html('Client Updated Successfully!')
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
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });

        });

        function editClient(record_id) {
            $.get('/editclient/' + record_id, function(data) {
                if (data.client) {
                    $('#client_id').val(data.client.id);
                    $('#client_name').val(data.client.client_name);
                    $('#address').val(data.client.address);
                    $('#contact_number').val(data.client.contact_number);
                    $('#fax').val(data.client.fax);
                    $('#email').val(data.client.email);
                    $('#invoice_terms').val(data.client.invoice_terms);
                    $('#payment_terms').val(data.client.payment_terms);

                    // If you're using file inputs, note that .val() cannot set a file. You might just show the filename.
                    if (data.client.doc_1) {
                        $('#doc_1_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_1 +
                            '" target="_blank">View Doc 1</a>');
                    }
                    if (data.client.doc_2) {
                        $('#doc_2_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_2 +
                            '" target="_blank">View Doc 2</a>');
                    }
                    if (data.client.doc_3) {
                        $('#doc_3_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_3 +
                            '" target="_blank">View Doc 3</a>');
                    }

                    $('#contract_start').val(data.client.contract_start);
                    $('#contract_end').val(data.client.contract_end);
                    $('#company_id').val(data.client.company_id);
                    $('#guard_rate').val(data.client.guard_rate);
                    $('#office_rate').val(data.client.office_rate);
                    $('#vat').val(data.client.vat);
                    $('#edit_client').modal('show');
                }
            });
        }
        let selectedId = null;

        function deleteClient(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `/deleteclient/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');

                        $('#success_message').html('Client Deleted Successfully!')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {

            $('.submenu > a').click(function(e) {
                e.preventDefault();

                var $this = $(this);
                var $submenu = $this.next('ul');

                if (!$this.hasClass('subdrop')) {
                    $('.submenu > a').removeClass('subdrop');
                    $('.submenu ul').slideUp(200);

                    $this.addClass('subdrop');
                    $submenu.slideDown(200);
                } else {
                    $this.removeClass('subdrop');
                    $submenu.slideUp(200);
                }
            });


            var currentPage = window.location.pathname.split("/").pop();

            $('#sidebar-menu a').each(function() {
                var linkPage = $(this).attr('href');
                if (linkPage === currentPage) {
                    $(this).addClass('active');

                    var $submenu = $(this).closest('.submenu');
                    if ($submenu.length) {
                        $submenu.find('> a').addClass('subdrop');
                        $submenu.find('ul').slideDown(0).css('display', 'block');
                    }
                }
            });
        });
    </script>
@endsection
