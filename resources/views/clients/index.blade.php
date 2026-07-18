@extends('layouts.app')
@section('title', brand_title('Client'))
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

                    {{-- show validation errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
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
                        {{ $dataTable->setTableHeadClass('thead-light')->table(['class' => 'table datatable']) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Client -->

        <!-- /Add Client -->

        <!-- Generate Client Invoice -->

        <!-- /Generate Client Invoice-->
        @include('clients.invoice_model')
        <!-- Edit Client -->
        @include('clients.create')
        <!-- /Add Employee -->
        @include('clients.edit')

        <!-- /Edit Client -->

        <!-- View Detail Modal -->
        <div class="modal fade" id="viewDetailModal" tabindex="-1" aria-labelledby="clientDetailLabel" aria-hidden="true">
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
                                    <th>Guard Rate</th>
                                    <td id="guard_rate_detail"></td>
                                </tr>
                                <tr>
                                    <th>Office Rate</th>
                                    <td id="client_rate_detail"></td>
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
        @include('clients.import_modal')
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
                                <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}
                                </option>
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
    <script>
        $(document).ready(function() {
            $('.select-manager').select2({
                width: '100%',
                dropdownParent: $('#assignManagerModal')
            });
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
                        closeBsModal('#add_client');
                        toast_success('Client Added Successfully');
                        reloadDatatable('#clients-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                            
                            // Get the first error message for toast
                            let firstError = Object.values(errors)[0][0];
                            toast_danger(firstError);
                        } else {
                            // Handle other error responses
                            let errorMessage = 'An error occurred. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Sometimes errors come as a flat object
                                let firstError = Object.values(xhr.responseJSON.errors)[0];
                                errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.error || response.message || errorMessage;
                                } catch (e) {
                                    // If not JSON, use responseText if it's reasonable length
                                    if (xhr.responseText.length < 200) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                            }
                            
                            toast_danger(errorMessage);
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
                        closeBsModal('#edit_client');
                        toast_success('Client Updated Successfully!')
                        reloadDatatable('#clients-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            // Validation errors
                            let errors = xhr.responseJSON.errors;
                            
                            // Display inline errors
                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]);
                            });
                            
                            // Get the first error message for toast
                            let firstError = Object.values(errors)[0][0];
                            toast_danger(firstError);
                        } else {
                            // Handle other error responses
                            let errorMessage = 'An error occurred. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Sometimes errors come as a flat object
                                let firstError = Object.values(xhr.responseJSON.errors)[0];
                                errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.error || response.message || errorMessage;
                                } catch (e) {
                                    // If not JSON, use responseText if it's reasonable length
                                    if (xhr.responseText.length < 200) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                            }
                            
                            toast_danger(errorMessage);
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
                        closeBsModal('#generate_invoice');
                        toast_success('Invoice Created Successfully!');
                        reloadDatatable('#clients-table');
                    },
                    error: function(xhr) {

                            // Handle other error responses
                            let errorMessage = 'An error occurred. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.error || response.message || errorMessage;
                                } catch (e) {
                                    // If not JSON, use responseText if it's reasonable length
                                    if (xhr.responseText.length < 200) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                            }
                            
                            toast_danger(errorMessage);
                        
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Generate');
                    }
                });
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
                                '<option value="' + site.id + '">' + site.site_name +
                                '</option>'
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
                    $('#password').val(''); // Leave blank for security
                    $('#address').val(data.client.address);
                    $('#contact_number').val(data.client.contact_number);
                    $('#contact_person').val(data.client.contact_person);
                    $('#email').val(data.client.email);
                    $('#invoice_terms').val(data.client.invoice_terms);
                    $('#payment_terms').val(data.client.payment_terms);

                    // File previews
                    if (data.client.doc_1) {
                        $('#doc_1_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_1 +
                            '" target="_blank">View Doc 1</a>');
                    } else {
                        $('#doc_1_preview').html('');
                    }
                    if (data.client.doc_2) {
                        $('#doc_2_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_2 +
                            '" target="_blank">View Doc 2</a>');
                    } else {
                        $('#doc_2_preview').html('');
                    }
                    if (data.client.doc_3) {
                        $('#doc_3_preview').html('<a href="{{ asset('uploads/docs') }}/' + data.client.doc_3 +
                            '" target="_blank">View Doc 3</a>');
                    } else {
                        $('#doc_3_preview').html('');
                    }

                    $('#contract_start').val(data.client.contract_start);
                    $('#contract_end').val(data.client.contract_end);
                    $('#company_id').val(data.client.company_id);
                    $('#guard_rate').val(data.client.guard_rate);
                    $('#office_rate').val(data.client.office_rate);
                    $('#vat').val(data.client.vat);

                    $('#edit_client').modal('show');
                } else {
                    toast_danger('Failed to fetch client details.');
                }
            }).fail(function() {
                toast_danger('An error occurred while fetching client data.');
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
                        closeBsModal('#delete_modal');

                        toast_success('Client Deleted Successfully!');
                        reloadDatatable('#clients-table');
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
                toast_danger('Please select at least one client to delete.');
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
                    toast_success('Selected clients deleted successfully!');
                    reloadDatatable('#clients-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
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
                $('#guard_rate_detail').text(`£${data.guard_rate ?? 0}`);
                $('#client_rate_detail').text(`£${data.supervisor_rate ?? 0}`);
                $('#period_detail').text(data.contract_period ?? '');
                $('#document_detail').text(data.documents ?? '');
                $('#company_detail').text(data.company ?? '');
                $('#manager_detail').text(data.manager ?? '');

                let modal = new bootstrap.Modal(document.getElementById('viewDetailModal'));
                modal.show();
            }).fail(function() {
                toast_danger('Failed to fetch client detail.');
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
    </script>
    <script>
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

    {!! $dataTable->scripts() !!}
@endsection
