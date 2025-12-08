@extends('layouts.app')
@section('title', 'CRM - Payrolls')
@section('contents')
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Payrolls</h2>
                </div>
            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap">
                <div class="me-2">
                    <div class="dropdown">
                        <button id="bulkDeletePayrollsBtn" class="btn btn-primary">Delete Selected</button>
                        <a href="javascript:void(0);"
                            id="exportDropdownToggle"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu dropdown-menu-start p-3">
                            <li><a id="exportPdfBtn" href="{{ route('invoices.export.pdf') }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a></li>
                            <li><a id="exportExcelBtn" href="{{ route('invoices.export.excel') }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a></li>
                        </ul>
                    </div>
                </div>

                    <div class="me-2 mb-2 filter_area">
                    <button type="button" id="generatePayrollBtn" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#generate_payroll">Generate</button>
                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                    </div>
                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="card">
                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-tab-pane" type="button" role="tab" aria-controls="staff-tab-pane" aria-selected="true">Employee Payrolls</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="subcontractor-tab" data-bs-toggle="tab" data-bs-target="#subcontractor-tab-pane" type="button" role="tab" aria-controls="subcontractor-tab-pane" aria-selected="false">Subcontractor Payrolls</button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="staff-tab-pane" role="tabpanel" aria-labelledby="staff-tab">
                                {!! $dataTable->table(['class' => 'table datatable']) !!}
                            </div>
                            <div class="tab-pane fade" id="subcontractor-tab-pane" role="tabpanel" aria-labelledby="subcontractor-tab">
                                <div class="p-3">
                                    <table id="subcontractor-payrolls-dt" class="table datatable table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="subcontractor-select-all"></th>
                                                <th>#</th>
                                                <th>Payroll No</th>
                                                <th>Subcontractor</th>
                                                <th>Site</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Total Hours</th>
                                                <th>Net Amount</th>
                                                <th>Total Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generate payroll --}}
        <div class="modal fade" id="generate_payroll">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Generate Employee Payroll</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    @php
                        $staffs = App\Models\User::role('security_staff')->get();
                        // Do not load all sites here — site select should be populated
                        // only with sites the selected staff has worked on (via AJAX).
                        $sites = [];
                    @endphp

                    <form method="POST" id="generate_payroll-form">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="security_staff_id" class="form-label">Select Staff</label>
                                <select name="security_staff_id" id="security_staff_id"
                                    class="form-select selec2_assign_modal" required>
                                    <option value="">-- Choose Staff --</option>
                                    @foreach ($staffs as $staff)
                                        <option value="{{ $staff->id }}"
                                            data-first="{{ strtolower($staff->first_name) }}"
                                            data-last="{{ strtolower($staff->last_name) }}">
                                            {{ $staff->first_name }} {{ $staff->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="text-danger form-error" id="payrollerror_security_staff_id"></span>
                            </div>

                            <div class="mb-3">
                                <label for="site_id" class="form-label">Employee Site</label>
                                <select name="site_id" id="payroll_site_id" class="form-select">
                                    <option value="">-- Choose Site --</option>
                                    {{-- populated dynamically when a staff is selected --}}
                                </select>
                                <span class="text-danger form-error" id="payrollerror_site_id"></span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Frequency <span class="text-danger">*</span></label>
                                <select name="frequency" id="payroll_frequency" class="form-select">
                                    <option value="">-- Select Frequency --</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="fortnightly">Fortnightly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                <span class="text-danger form-error" id="payrollerror_frequency"></span>
                            </div>
                            <div class="mb-3">
                                <label for="date_from" class="form-label">Start Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" required>
                                <span class="text-danger form-error" id="payrollerror_date_from"></span>
                            </div>

                            <div class="mb-3">
                                <label for="date_to" class="form-label">End Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" required>
                                <span class="text-danger form-error" id="payrollerror_date_to"></span>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" required></textarea>
                                <span class="text-danger form-error" id="payrollerror_notes"></span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="btn-generate">Generate</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Generate Employee Payroll -->

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                            <i class="ti ti-trash-x fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Delete</h4>
                        <p class="mb-3">You want to delete all the marked items, this can't be undone once you delete.
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
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
                $('#btn-generate').on('click', function(e) {
                e.preventDefault();

                // Clear errors
                $('.form-error').text('');

                // Get frequency
                let frequency = $('#payroll_frequency').val();

                if(frequency){                    
                    // Calculate dates
                    const today = new Date();
                    let fromDate, toDate;
    
                    if (frequency === 'weekly') {
                        let lastMonday = new Date(today);
                        lastMonday.setDate(today.getDate() - today.getDay() - 6); // last week's Monday
                        let lastSunday = new Date(lastMonday);
                        lastSunday.setDate(lastMonday.getDate() + 6);
                        fromDate = lastMonday;
                        toDate = lastSunday;
                    } else if (frequency === 'fortnightly') {
                        toDate = today;
                        fromDate = new Date();
                        fromDate.setDate(today.getDate() - 13);
                    } else if (frequency === 'monthly') {
                        toDate = today;
                        fromDate = new Date();
                        fromDate.setDate(today.getDate() - 29);
                    }
    
                    // Inject dates into form before serializing
                    $('#date_from').val(fromDate.toISOString().split('T')[0]);
                    $('#date_to').val(toDate.toISOString().split('T')[0]);
                }

                let formData = $('#generate_payroll-form').serialize();

                $.ajax({
                    url: `/generatepayroll`,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        toast_success(response.message ?? "Payroll generated successfully");

                        // Close modal
                        let modalEl = document.getElementById('generate_payroll');
                        let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(
                            modalEl);
                        modal.hide();

                        // Reload table
                        reloadDatatable('#payrolls-table');
                    },
                    error: function(xhr) {
                        $('.form-error').text('');
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (const key in errors) {
                                $(`#payrollerror_${key}`).text(errors[key][0]);
                            }
                        } else {
                            toast_danger('Something went wrong!');
                        }
                    }
                });
            });
        });

        function generatePayroll(record_id) {
            // Fetch sites for this staff and pre-select the staff in the modal
            $.get(`${baseUrl}/generatepayroll/` + record_id, function(data) {
                if (data.employee) {
                    // set staff select and trigger change so sites load consistently
                    $('#security_staff_id').val(record_id).trigger('change');

                    // populate sites from response; if none, show a disabled 'No assigned sites' entry
                    $('#payroll_site_id').empty().append('<option value="">-- Choose Site --</option>');
                    if (data.sites && data.sites.length) {
                        $.each(data.sites, function(index, item) {
                            $('#payroll_site_id').append(
                                $('<option>', {
                                    value: item.site.id,
                                    text: item.site.site_name
                                })
                            );
                        });
                    } else {
                        $('#payroll_site_id').append('<option value="" disabled>No assigned sites</option>');
                    }

                    $('#generate_payroll').modal('show');
                }
            });
        }

        // When a staff is selected in the modal, fetch the sites they have worked on
        $(document).on('change', '#security_staff_id', function() {
            const staffId = $(this).val();
            $('#payroll_site_id').empty().append('<option value="">-- Choose Site --</option>');
            if (!staffId) return;

            $.get(`${baseUrl}/generatepayroll/` + staffId, function(data) {
                if (data.sites && data.sites.length) {
                    $.each(data.sites, function(index, item) {
                        $('#payroll_site_id').append(
                            $('<option>', {
                                value: item.site.id,
                                text: item.site.site_name
                            })
                        );
                    });
                } else {
                    $('#payroll_site_id').append('<option value="" disabled>No assigned sites</option>');
                }
            });
        });

        let selectedId = null;
        let selectedTable = null;

        function deleteRecord(record_id, table) {
            selectedId = record_id;
            selectedTable = table; // 'invoices' or 'payrolls'
            $('#delete_modal').modal('show');
        }

        $('#confirmDeleteBtn').on('click', function() {
            if (selectedId !== null && selectedTable !== null) {
                let deleteUrl = '';
                if (selectedTable === 'payrolls') {
                    deleteUrl = `${baseUrl}/deletepayroll/${selectedId}`;
                } else if (selectedTable === 'invoices' || selectedTable === 'subcontractor') {
                    // subcontractor invoices use the invoice delete endpoint
                    deleteUrl = `${baseUrl}/deleteinvoice/${selectedId}`;
                } else {
                    // fallback to invoice delete
                    deleteUrl = `${baseUrl}/deleteinvoice/${selectedId}`;
                }

                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Record Deleted Successfully!');
                        // Reload relevant table
                        if (selectedTable === 'payrolls') {
                            reloadDatatable('#payrolls-table');
                        } else if (selectedTable === 'subcontractor') {
                            if (window.subcontractorTable) window.subcontractorTable.ajax.reload(null, false);
                        } else {
                            reloadDatatable(`#${selectedTable}-table`);
                        }

                        selectedId = null;
                        selectedTable = null;
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });

        // Bulk delete button
        function bulkDeleteActive() {
            // Determine active tab
            const activeTab = $('#staff-tab').hasClass('active') ? 'staff' : ($('#subcontractor-tab').hasClass('active') ? 'subcontractor' : 'staff');
            let selector = '#payrolls-table';
            if (activeTab === 'subcontractor') selector = '#subcontractor-payrolls-dt';

            const selected = $(`${selector} .dT-row-checkbox:checked`).map(function() { return this.value; }).get();
            if (selected.length === 0) {
                toast_danger('Please select at least one record to delete.');
                return;
            }
            if (!confirm('Are you sure you want to delete the selected records?')) return;

            $.ajax({
                url: '{{ route('payrolls.bulkDelete') }}',
                type: 'POST',
                data: { ids: selected, _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toast_success('Selected records deleted successfully!');
                    // reload appropriate table
                    if (activeTab === 'subcontractor') {
                        if (window.subcontractorTable) window.subcontractorTable.ajax.reload(null, false);
                    } else {
                        reloadDatatable('#payrolls-table');
                    }
                },
                error: function() { toast_danger('Something went wrong during bulk delete.'); }
            });
        }

        function customMatcher(params, data) {
            // Default behavior: if empty term, show all
            if (!params || !params.term || $.trim(params.term) === '') {
                return data;
            }

            // Coerce term to string
            const term = String(params.term).toLowerCase();

            // data may not always have an element (select2 internals) — guard against it
            let first = '';
            let last = '';

            if (data && data.element) {
                // Use dataset/read attribute safely
                try {
                    first = $(data.element).data('first') || '';
                    last = $(data.element).data('last') || '';
                } catch (e) {
                    first = '';
                    last = '';
                }
            }

            first = String(first).toLowerCase();
            last = String(last).toLowerCase();
            const full = (first + ' ' + last).trim();

            if (first.includes(term) || last.includes(term) || full.includes(term)) {
                return data;
            }

            // Fall back to checking the option text as well
            if (data && data.text && String(data.text).toLowerCase().includes(term)) {
                return data;
            }

            return null;
        }

        $(document).ready(function() {
            $('.selec2_assign_modal').select2({
                dropdownParent: $('#generate_payroll'),
                matcher: customMatcher,
                width: '100%'
            });
        });

        $(function() {
            $(document).on('change', '#payrolls-select-all', function() {
                let isChecked = $(this).is(':checked');
                $('#payrolls-table .dT-row-checkbox').prop('checked', isChecked);
            });

            $(document).on('change', '#payrolls-table .dT-row-checkbox', function() {
                if (!$(this).is(':checked')) {
                    $('#payrolls-select-all').prop('checked', false);
                } else if ($('#payrolls-table .dT-row-checkbox:checked').length === $(
                        '#payrolls-table .dT-row-checkbox').length) {
                    $('#payrolls-select-all').prop('checked', true);
                }
            });
        });
    </script>

    {!! $dataTable->scripts() !!}

    <script>
        // Initialize subcontractor DataTable via client-side AJAX to avoid server-side Yajra call
        $(function() {
            const subcontractorTable = $('#subcontractor-payrolls-dt').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('payrolls.subcontractor.data') }}',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id', orderable: false, render: function(data){ return '<input type="checkbox" class="dT-row-checkbox" value="'+data+'">'; } },
                    { data: null, render: function(data, type, row, meta) { return meta.row + 1; }, orderable: false },
                    { data: 'invoice_number', render: function(data, type, row) { return '<a href="/payrolls/' + row.id + '">' + data + '</a>'; } },
                    { data: 'subcontractor_name' },
                    { data: 'site_name' },
                    { data: 'issue_date' },
                    { data: 'due_date' },
                    { data: 'total_shift_hours' },
                    { data: 'net_amount', className: 'text-end' },
                    { data: 'total_amount', className: 'text-end' },
                    { data: 'status', render: function(data) { if (data === 'Paid') return '<span class="badge bg-success">Paid</span>'; return '<span class="badge bg-warning text-dark">Unpaid</span>'; } },
                    { data: 'id', orderable: false, render: function(data) { return '<a href="javascript:void(0)" class="btn btn-sm btn-danger" onclick="deleteRecord(' + data + ', \"subcontractor\")">Delete</a>'; } }
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                responsive: true
            });

            // Optionally reload subcontractor table when its tab is shown
            $('button[data-bs-target="#subcontractor-tab-pane"]').on('shown.bs.tab', function () {
                subcontractorTable.ajax.reload(null, false);
            });
            // handle select-all for subcontractor table
            $(document).on('change', '#subcontractor-select-all', function() {
                const checked = $(this).is(':checked');
                $('#subcontractor-payrolls-dt .dT-row-checkbox').prop('checked', checked);
            });

            // ensure header checkbox sync when individual checkboxes change
            $(document).on('change', '#subcontractor-payrolls-dt .dT-row-checkbox', function() {
                const all = $('#subcontractor-payrolls-dt .dT-row-checkbox').length;
                const checked = $('#subcontractor-payrolls-dt .dT-row-checkbox:checked').length;
                $('#subcontractor-select-all').prop('checked', all > 0 && all === checked);
            });
            // expose for global reload from other handlers
            window.subcontractorTable = subcontractorTable;

            // Toolbar tab-change behavior: update export links and delete button handler
            function setToolbarForTab(tab) {
                let pdfHref = '{{ route('invoices.export.pdf') }}';
                let excelHref = '{{ route('invoices.export.excel') }}';

                if (tab === 'subcontractor') {
                    // append query param so server-side export can filter by type if supported
                    pdfHref += '?type=subcontractor';
                    excelHref += '?type=subcontractor';
                    // hide generate for subcontractor (generated elsewhere)
                    $('#generatePayrollBtn').hide();
                    $('#bulkDeletePayrollsBtn').off('click').on('click', bulkDeleteActive);
                } else {
                    $('#generatePayrollBtn').show().text('Generate');
                    $('#bulkDeletePayrollsBtn').off('click').on('click', function() { bulkDeleteActive(); });
                }

                $('#exportPdfBtn').attr('href', pdfHref);
                $('#exportExcelBtn').attr('href', excelHref);
            }

            // initialize toolbar state based on default active tab
            setToolbarForTab('staff');

            // hook tab change
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const target = $(e.target).data('bs-target');
                if (target === '#subcontractor-tab-pane') setToolbarForTab('subcontractor');
                else setToolbarForTab('staff');
            });
        });
    </script>
@endsection
