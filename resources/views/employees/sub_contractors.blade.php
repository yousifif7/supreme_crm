@extends('layouts.app')
@section('title', 'CRM - Subcontractors')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Sub Contractors</h2>
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
                                <a href="{{ route('subcontractors.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('subcontractors.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_subcontractor"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Subcontractor
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

        <!-- Add Subcontractor -->
      
        <!-- /Add Subcontractor -->


        <!-- Edit Subcontractor -->
     
        <!-- /Edit Subcontractor -->
        <!-- View Subcontractor Detail Modal -->
      


        <!-- Add Subcontractor Success -->
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
                                        <a href="{{ url('subcontractors') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Add Subcontractor Success -->

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
        @include('employees.import_modal')
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
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>

        $(document).ready(function() {
            // Add Subcontractor
            $('#add_subcontractor-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='error_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveSubcontractor');

                submitButton.prop('disabled', true).html('Saving...');

                $.ajax({
                    url: $(this).attr('action'), // e.g., /subcontractors/store
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#add_subcontractor');
                        toast_success('Subcontractor Added Successfully');
                        reloadDatatable('#subcontractors-table');
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

            // Edit Subcontractor
            $('#edit_subcontractor-form').on('submit', function(e) {
                e.preventDefault();
                $("[id^='editerror_']").text('');
                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#updateSubcontractor');
                let subcontractorId = $('#subcontractor_id').val();

                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatesubcontractor/${subcontractorId}`, // adjust route if needed
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_subcontractor');
                        toast_success('Subcontractor Updated Successfully!');
                        reloadDatatable('#subcontractors-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#editerror_' + key).text(value[0]);
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

        function editSubcontractor(record_id) {
            $.get(`${baseUrl}/editsubcontractor/${record_id}`, function(data) {
                if (data.subcontractor) {
                    $('#subcontractor_id').val(data.subcontractor.id);
                    $('#company_name').val(data.subcontractor.company_name);
                    $('#username').val(data.subcontractor.username);
                    $('#password').val(data.subcontractor.password);
                    $('#company_address').val(data.subcontractor.company_address);
                    $('#contact_number').val(data.subcontractor.contact_number);
                    $('#contact_person').val(data.subcontractor.contact_person);
                    $('#email').val(data.subcontractor.email);
                    $('#invoice_terms').val(data.subcontractor.invoice_terms);
                    $('#payment_terms').val(data.subcontractor.payment_terms);
                    $('#department').val(data.subcontractor.department);
                    $('#pay_rate').val(data.subcontractor.pay_rate);
                    $('#vat_number').val(data.subcontractor.vat_number);

                    // Checkbox toggles
                    $('#pmvaCheckEdit').prop('checked', data.subcontractor.pmva_trained_officer == 1);
                    $('#vatCheckEdit').prop('checked', data.subcontractor.vat_registered == 1);

                    $('#edit_subcontractor').modal('show');
                }
            }).fail(function() {
                toast_danger('Failed to load subcontractor data.');
            });
        }

        let selectedId = null;

        function deleteSubcontractor(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deletesubcontractor/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Subcontractor Deleted Successfully!');
                        reloadDatatable('#subcontractors-table');
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
                toast_danger('Please select at least one subcontractor to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected subcontractors?')) return;

            $.ajax({
                url: '{{ route('subcontractors.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected subcontractors deleted successfully!');
                    reloadDatatable('#subcontractors-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });



        function viewSubcontractorDetail(id) {
            $.get(`${baseUrl}/subcontractors/${id}/view`, function(data) {
                $('#subcontractor_name_heading').text(data.company_name);
                $('#company_name_detail').text(data.company_name);
                $('#company_address_detail').text(data.company_address);
                $('#contact_person_detail').text(data.contact_person);
                $('#contact_number_detail').text(data.contact_number);
                $('#email_detail').text(data.email);
                $('#username_detail').text(data.username);
                $('#invoice_terms_detail').text(data.invoice_terms);
                $('#payment_terms_detail').text(data.payment_terms);
                $('#department_detail').text(data.department);
                $('#pay_rate_detail').text(`$${data.pay_rate}`);
                $('#pmva_detail').text(data.pmva_trained_officer ? 'Yes' : 'No');
                $('#vat_registered_detail').text(data.vat_registered ? 'Yes' : 'No');
                $('#vat_number_detail').text(data.vat_number ?? '-');
                $('#status_detail').text(data.is_active ? 'Active' : 'Inactive');

                new bootstrap.Modal(document.getElementById('viewSubcontractorDetailModal')).show();
            });
        }


        function viewLogs(subcontractorId) {
            // Clear existing content
            const modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            fetch(`/subcontractors/${subcontractorId}/logs/ajax`)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">No logs found for this subcontractor.</p>';
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
        $('#vatCheckSub').on('change', function() {
            $('#vatInputSub').toggle(this.checked);
        });
    </script>

    {!! $dataTable->scripts() !!}
@endsection
