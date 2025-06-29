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
                                <a href="{{ route('clients.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('clients.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

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
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Contact Person</th>
                                    <th>Contact Number</th>
                                    <th>Contact Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($clients->currentPage() - 1) * $clients->perPage() + 1; @endphp
                                @foreach ($clients as $client)
                                    <tr>

                                        <td><input type="checkbox" class="client-checkbox" value="{{ $client->id }}">
                                        </td>
                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <div class="d-flex align-items-center file-name-icon">

                                                <div class="ms-2">
                                                    <h6 class="fw-medium"><a
                                                            onclick="viewClientDetail({{ $client->id }})">{{ $client->client_name }}</a>
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->address }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->contact_person }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->contact_number }}
                                        </td>
                                        <td style="text-align: left">
                                            {{ $client->email }}
                                        </td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <button class="sites_action-btn"
                                                    onclick="assignManager({{ $client->id }})">Managers</button>
                                                <button class="sites_action-btn"
                                                    onclick="viewLogs({{ $client->id }})">Logs</button>
                                                <a href="#" class="me-2"
                                                    onclick="viewClientDetail({{ $client->id }})"><i
                                                        class="ti ti-eye"></i></a>
                                                <a href="#" class="me-2"
                                                    onclick="editClient({{ $client->id }})"><i
                                                        class="ti ti-edit"></i></a>

                                                <a href="#" class="me-2"
                                                    onclick="generateInvoice({{ $client->id }})"><i
                                                        class="ti ti-receipt"></i></a>

                                                {{--<a href="{{ route('invoices.show', $client->id) }}"><i
                                                        class="ti ti-printer"></i></a>--}}

                                                <a href="javascript:void(0);"
                                                    onclick="deleteClient({{ $client->id }})"><i
                                                        class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>
            <div class="card-footer d-flex justify-content-center">
                {{ $clients->links('vendor.pagination.bootstrap-5') }}
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
                                                        <label class="form-label">Client Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="client_name" class="form-control"
                                                            placeholder="Enter Client Name">
                                                        <span class="text-danger form-error"
                                                            id="error_client_name"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="address" rows="2"></textarea>
                                                        <span class="text-danger form-error" id="error_address"></span>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="contact_number"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Person <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="contact_person"
                                                                    class="form-control"
                                                                    placeholder="Enter Client Person">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_person"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email <span
                                                                        class="text-danger">*</span></label>
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
                                                            @foreach ($companys as $company)
                                                                <option value="{{ $company->id }}">
                                                                    {{ $company->company_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <div class="form-label">Username <span
                                                                        class="text-danger">*</span></div>
                                                                <input type="email" name="username"
                                                                    class="form-control" placeholder="example">
                                                                <span class="text-danger form-error"
                                                                    id="error_username"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <div class="form-label">Password <span
                                                                        class="text-danger">*</span></div>
                                                                <input type="password" name="password"
                                                                    class="form-control" placeholder="••••••">
                                                                <span class="text-danger form-error"
                                                                    id="error_password"></span>
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
                                                        <label class="form-label">Invoice Terms <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="invoice_terms">
                                                            <option value="">--choose--</option>
                                                            <option value="Fortnightly Invoice">Fortnightly Invoice
                                                            </option>
                                                            <option value="Weekly Invoice">Weekly Invoice
                                                            </option>
                                                            <option value="Monthly Invoice">Monthly Invoice
                                                            </option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="error_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="payment_terms" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="error_payment_terms"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract Start: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="contract_start"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="error_contract_start"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract End: <span
                                                                        class="text-danger">*</span></label>
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
                                                                <label class="form-label">Guard Rate: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="guard_rate"
                                                                    class="form-control numeric-input"
                                                                    placeholder="Enter Guard rate">
                                                                <span class="text-danger form-error"
                                                                    id="error_guard_rate"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Office Rate: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="office_rate"
                                                                    class="form-control numeric-input"
                                                                    placeholder="Enter Office rate">
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
                                                            <div class="mb-3" id="vatInput" style="display: none;">
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

        <!-- Generate Client Invoice -->
        <div class="modal fade" id="generate_invoice">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Generate Client Invoice</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="generate_invoice-form">
                        @csrf
                        <input type="hidden" name="client_id" id="invoice_client_id">
                        <div class="tab-content" id="myTabContentInvoice">
                            <div class="tab-pane fade show active" id="invoice-basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Client Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="client_name" id="invoice_client_name" readonly style="background: #eee;" 
                                                            class="form-control" placeholder="Enter Client Name">
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_client_name"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Client Site <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="site_id" id="invoice_site_id">
                                                            <option value="">-- choose --</option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_site_id"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Due Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="due_date" id="invoice_due_date"  
                                                            class="form-control" placeholder="">
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_due_date"></span>
                                                    </div>

                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date From: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_from"
                                                                    id="invoice_date_from" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="invoiceerror_date_from"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date To: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_to"
                                                                    id="invoice_date_to" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="invoiceerror_date_to"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Notes <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="notes" id="invoice_notes" rows="4"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_notes"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="generate_invoice-form" id="generateinvoice"
                                        class="btn btn-primary">Generate </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Generate Client Invoice-->

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
                                                        <label class="form-label">Client Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="client_name" id="client_name"
                                                            class="form-control" placeholder="Enter Client Name">
                                                        <span class="text-danger form-error"
                                                            id="editerror_client_name"></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="editerror_address"></span>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="contact_number"
                                                                    id="contact_number" class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">contact person <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="contact_person"
                                                                    id="contact_person" class="form-control"
                                                                    placeholder="Enter Client Person">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contact_person"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="email" name="email" id="email"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Email">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_email"></span>
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
                                                                    id="editerror_doc_1"></span>
                                                                <div id="doc_1_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 2:</label>
                                                                <input type="file" name="doc_2" id="doc_2"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_doc_2"></span>
                                                                <div id="doc_2_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Doc 3:</label>
                                                                <input type="file" name="doc_3" id="doc_3"
                                                                    class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_doc_3"></span>
                                                                <div id="doc_3_preview"></div> <!-- Document Preview -->
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Other Fields -->
                                                    <div class="mb-3">
                                                        <label class="form-label">Company</label>
                                                        <select class="form-select" name="company_id" id="company_id">
                                                            <option value="">-- choose --</option>
                                                            @foreach ($companys as $company)
                                                                <option value="{{ $company->id }}">
                                                                    {{ $company->company_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="editerror_company_id"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">invoice terms <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" name="invoice_terms"
                                                            id="invoice_terms">
                                                            <option value="">--choose--</option>
                                                            <option value="Fortnightly Invoice">Fortnightly Invoice
                                                            </option>
                                                            <option value="Weekly Invoice">Weekly Invoice
                                                            </option>
                                                            <option value="Monthly Invoice">Monthly Invoice
                                                            </option>
                                                        </select>
                                                        <span class="text-danger form-error"
                                                            id="editerror_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="payment_terms" id="payment_terms" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="editerror_payment_terms"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract Start: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="contract_start"
                                                                    id="contract_start" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contract_start"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contract End: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="contract_end"
                                                                    id="contract_end" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contract_end"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Guard rate: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="guard_rate" id="guard_rate"
                                                                    class="form-control numeric-input"
                                                                    placeholder="Enter Guard rate">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_guard_rate"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Office rate: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="office_rate" id="office_rate"
                                                                    class="form-control numeric-input"
                                                                    placeholder="Enter Office rate">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_office_rate"></span>
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
                                                                    id="editerror_vat"></span>
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

        <!-- View Detail Modal -->
        <div class="modal fade" id="viewDetailModal" tabindex="-1" aria-labelledby="clientDetailLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content shadow rounded-3">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="clientDetailLabel">
                            Client <span id="hname" class="fw-bold"></span> Security Detail
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>Contact Name & Person</th>
                                    <td id="name_detail"></td>
                                </tr>
                                <tr>
                                    <th class="w-50">Address</th>
                                    <td id="address_detail"></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td id="contact_number_detail"></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td id="email_detail"></td>
                                </tr>
                                <tr>
                                    <th>Invoice Terms</th>
                                    <td id="invoice_terms_detail"></td>
                                </tr>
                                <tr>
                                    <th>Payment Terms</th>
                                    <td id="payment_terms_detail"></td>
                                </tr>
                                <tr>
                                    <th>VAT Registered?</th>
                                    <td id="vat_detail"></td>
                                </tr>
                                <tr>
                                    <th>Charge Rate (Guardings | Supervisors)</th>
                                    <td id="rate_detail"></td>
                                </tr>
                                <tr>
                                    <th>Contract Period (Start | End)</th>
                                    <td id="period_detail"></td>
                                </tr>
                                <tr>
                                    <th>Document</th>
                                    <td id="document_detail"></td>
                                </tr>
                                <tr>
                                    <th>Company</th>
                                    <td id="company_detail"></td>
                                </tr>
                                <tr>
                                    <th>Manager</th>
                                    <td id="manager_detail"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

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
                    <form action="{{ route('clients.import') }}" method="POST" enctype="multipart/form-data">
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
    <!-- Logs Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Client Logs Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Assign Manager Modal -->
    <div class="modal fade" id="assignManagerModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Assign Manager
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form method="POST" id="assign_manager">
                    @csrf
                    <div class="modal-body p-4">
                        <select name="manager_id" id="manager_id" class="form-control select-manager">
                            <option value="">--choose--</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->fore_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="assign_manager" id="editclient" class="btn btn-primary">Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select-manager').select2({
                width: '100%',
                dropdownParent: $('#assignManagerModal')
            });
        });
        // Client search functionality
        $('.search_box').on('keyup', function() {
            let searchText = $(this).val().toLowerCase();

            $('.datatable tbody tr').each(function() {
                let rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(searchText) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Select All toggle
        $('#selectAll').on('change', function() {
            $('.client-checkbox').prop('checked', $(this).prop('checked'));
        });
        document.getElementById('vatCheck').addEventListener('change', function() {
            const vatInput = document.getElementById('vatInput');
            vatInput.style.display = this.checked ? 'block' : 'none';
        });
        $(document).ready(function() {
            $('#add_client-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
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

                $("[id^='editerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editclient'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let clientId = $('#client_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updateclient/${clientId}`, // OR use Laravel Blade: `{{ url('clients') }}/` + clientId
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
                                $('#editerror_' + key).text(value[0]);
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
            $('#generate_invoice-form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='invoiceerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#generateinvoice'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let clientId = $('#invoice_client_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/generateinvoice/${clientId}`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#generate_invoice').modal('hide');
                        $('#success_message').html('Invoice Created Successfully!')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#invoiceerror_' + key).text(value[0]);
                            });
                        } else {
                            alert('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Generate');
                    }
                });
            });
        });

        function generateInvoice(record_id) {
            $.get(`${baseUrl}/generateinvoice/` + record_id, function(data) {
                if (data.client) {
                    $('#invoice_client_id').val(data.client.id);
                    console.log(data.sites);
                    $.each(data.sites, function(index, item) {
                        $('#invoice_site_id').append(
                            $('<option>', {
                                value: item.id,
                                text: item.site_name
                            })
                        );
                    });
                    $('#invoice_client_name').val(data.client.client_name);
                    $('#generate_invoice').modal('show');
                }
            });
        }

        function editClient(record_id) {
            $.get(`${baseUrl}/editclient/` + record_id, function(data) {
                if (data.client) {
                    $('#client_id').val(data.client.id);
                    $('#client_name').val(data.client.client_name);
                    $('#username').val(data.client.username);
                    $('#password').val(data.client.password);
                    $('#address').val(data.client.address);
                    $('#contact_number').val(data.client.contact_number);
                    $('#contact_person').val(data.client.contact_person);
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
                    url: `${baseUrl}/deleteclient/${selectedId}`,
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

        // Bulk delete button
        $('#bulkDeleteBtn').on('click', function() {
            const selected = $('.client-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one client to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected clients?')) return;

            $.ajax({
                url: '{{ route('clients.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#success_message').text('Selected clients deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
                }
            });
        });

        function assignManager(clientId) {
            $('#assign_manager').attr('action', `/clients/${clientId}/assign-manager`);
            $('#assignManagerModal').modal('show');
        }

        function viewClientDetail(id) {
            $.get(`${baseUrl}/clients/${id}/view`, function(data) {
                $('#hname').text(data.client_name);
                $('#name_detail').text(data.contact_person);
                $('#address_detail').text(data.address);
                $('#contact_number_detail').text(data.contact_number);
                $('#email_detail').text(data.email);
                $('#invoice_terms_detail').text(data.invoice_terms);
                $('#payment_terms_detail').text(data.payment_terms);
                $('#vat_detail').text(data.vat_registered);
                $('#rate_detail').text(`$${data.guard_rate} | $${data.supervisor_rate}`);
                $('#period_detail').text(data.contract_period);
                $('#document_detail').text(data.documents);
                $('#company_detail').text(data.company);
                $('#manager_detail').text(data.manager);

                let modal = new bootstrap.Modal(document.getElementById('viewDetailModal'));
                modal.show();
            }).fail(function() {
                alert('Failed to fetch client detail.');
            });
        }

        function viewLogs(clientId) {
            // Clear existing content
            const modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            fetch(`${baseUrl}/clients/${clientId}/logs/ajax`)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">No logs found for this client.</p>';
                    } else {
                        let html = '<table class="table table-bordered table-striped">';
                        html +=
                            '<thead><tr><th>User</th><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>';
                        data.logs.forEach(log => {
                            html += `<tr>
                                    <td>${log.user_name}</td>
                                    <td>${log.action}</td>
                                    <td>${log.description}</td>
                                    <td>${log.time}</td>
                                </tr>`;
                        });
                        html += '</tbody></table>';
                        modalBody.innerHTML = html;
                    }

                    // Show the modal
                    $('#logModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                    modalBody.innerHTML = '<p class="text-danger">Error loading logs.</p>';
                });
        }

        document.getElementById('client_filter').addEventListener('change', function() {
            const selected = this.value;
            window.location.href = `?filter=${selected}`;
        });
        $('#client_filter').val({{ $filter }});
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

        document.querySelectorAll('.numeric-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');

                // Optional: Only allow one decimal point
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts[1];
                }
            });
        });
    </script>
@endsection
