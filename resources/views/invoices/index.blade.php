@extends('layouts.app')
@section('title', 'CRM - Invoices')
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Invoices</h2>

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
                                <a href="{{ route('invoices.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('invoices.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">
                    <div class="me-2 mb-2 filter_area">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#generate_invoice">Generate</button>
                    </div>

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

        </div>



    </div>
    <!-- /Page Wrapper -->

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

    <!-- Generate Client Invoice -->
    <div class="modal fade" id="generate_invoice">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Generate Client Invoice</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
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
                                                    <select type="text" name="client_id" id="invoice_client_name" class="form-select"
                                                        placeholder="Enter Client Name">
                                                        <option>--- Select Client --- </option>
                                                        @foreach ($clients as $client )
                                                            <option value="{{ $client->id }}">{{ $client->first_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger form-error"
                                                        id="invoiceerror_client_name"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Client Site <span
                                                            class="text-danger">*</span></label>
                                                <select name="site_id" class="form-select" id="invoice_site_id">
                                                    <option value="">--choose--</option>
                                                </select>
                                                    <span class="text-danger form-error" id="invoiceerror_site_id"></span>
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
                                                            <input type="date" name="date_from" id="invoice_date_from"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="invoiceerror_date_from"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Date To: <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="date" name="date_to" id="invoice_date_to"
                                                                class="form-control">
                                                            <span class="text-danger form-error"
                                                                id="invoiceerror_date_to"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Notes <span
                                                            class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="notes" id="invoice_notes" rows="4"></textarea>
                                                    <span class="text-danger form-error" id="invoiceerror_notes"></span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
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
@endsection
@section('scripts')
    <script>
        let selectedId = null;

        function deleteInvoice(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteinvoice/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');

                        toast_success('Invoice Deleted Successfully!')
                        reloadDatatable('#invoices-table');
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
                toast_danger('Please select at least one invoice to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected invoice?')) return;

            $.ajax({
                url: '{{ route('invoices.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected invoice deleted successfully!');
                    reloadDatatable('#invoices-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
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
                    closeBsModal('#generate_invoice');
                    toast_success('Invoice Created Successfully!');
                    reloadDatatable('#clients-table');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;

                        $.each(errors, function(key, value) {
                            $('#invoiceerror_' + key).text(value[0]);
                        });
                    } else {
                        toast_danger('An error occurred. Please try again.');
                    }
                },
                complete: function() {
                    // Re-enable button after response
                    submitButton.prop('disabled', false).html('Generate');
                }
            });
        });

$(document).on("change", "#invoice_client_name", function() {
    var $this = $(this);
    const clientId = $(this).val();
    if (!clientId) return;

    var $siteSelect = $('#invoice_site_id');
    $siteSelect.html('<option value="">--choose--</option>');

    $.ajax({
        url: `${baseUrl}/api/client/${clientId}`, // your existing API
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            // If you have office_rate in form, you can fill it like before
            $this.parents('.shift-group').find('.siteRate').val(data.client?.office_rate || '');

            if (data.sites && data.sites.length > 0) {
                $.each(data.sites, function(index, site) {
                    $siteSelect.append(
                        '<option value="' + site.id + '">' + site.site_name + '</option>'
                    );
                });
            } else {
                $siteSelect.append('<option value="">No sites found</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Fetch error:', error);
        }
    });

    // also update hidden client_id field
    $('#invoice_client_id').val(clientId);
});

    $(function () {
        let table = $('#invoices-table').DataTable();

        // Select/Deselect all rows
        $(document).on('change', '#select-all-checkbox', function () {
            let isChecked = $(this).is(':checked');
            $('.dT-row-checkbox').prop('checked', isChecked);
        });

        $(document).on('change', '.dT-row-checkbox', function () {
            if (!$(this).is(':checked')) {
                $('#select-all-checkbox').prop('checked', false);
            } else if ($('.dT-row-checkbox:checked').length === $('.dT-row-checkbox').length) {
                $('#select-all-checkbox').prop('checked', true);
            }
        });
    });
    </script>
    {!! $dataTable->scripts() !!}
@endsection
