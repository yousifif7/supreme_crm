@extends('layouts.app')
@section('title', brand_title('Leaves'))

@section('styles')
<style>
    .dt-column-title{
    font-weight: bolder;
    color:black;
}
</style>
@endsection
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Leaves</h2>

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
                                <a href="{{ route('leaves.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('leaves.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_leave"
                        class=" add_btn btn btn-white d-inline-flex align-items-center">
                        <i class="ti ti-plus me-2"></i>Leave
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

        {{-- Add leave requests --}}
        @include('leave_management.add-leave')


        <!-- Edit Client -->
        <div class="modal fade" id="edit_leave">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Worker</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data" id="edit_leave_form">

                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <input type="hidden" id="edit_leave_id" name="leave_id">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Details <span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="reason" id="edit_leave_entitlement"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_leave_entitlement"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date From <span class="text-danger">
                                                        *</span></label>
                                                <input type="date" name="start_date" id="edit_from_date"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_from_date"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date To <span class="text-danger">
                                                        *</span></label>
                                                <input type="date" name="end_date" id="edit_to_date"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_to_date"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Employee</label>
                                                <select name="employee_id" class="form-control select2_edit_modal"
                                                    id="edit_employee_id">
                                                    <option value="">Select Employee</option>
                                                    @foreach ($employees as $key => $employee)
                                                        <option value="{{ $key }}"
                                                            {{ $key == 'applied' ? 'selected' : '' }}>{{ $employee }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" id="edit_status" class="form-control">
                                                    <option value="">Select Status</option>
                                                    @foreach ($status as $key => $st)
                                                        <option value="{{ $key }}">{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Type</label>
                                                <input name="type" id="edit_type" class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-6" id="reject_reason_wrapper" style="display:none;">
                                            <div class="mb-3">
                                                <label class="form-label">Reject Reason <span
                                                        class="text-danger">*</span></label>
                                                <textarea name="reject_reason" id="reject_reason" class="form-control"></textarea>
                                                <span class="text-danger form-error" id="error_reject_reason"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_leave_form" class="btn btn-primary">Update
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Edit Client -->

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                            <i class="ti ti-trash-x fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Delete</h4>
                        <p class="mb-3">This action cannot be undone. Are you sure you want to delete?</p>
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
    <!-- Logs Modal -->
    {{-- <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Leave Logs Detail
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
    </div> --}}
    <!-- View Leave Detail Modal -->
    <div class="modal fade" id="viewLeaveDetailModal" tabindex="-1" aria-labelledby="leaveDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-gray text-white">
                    <h5 class="modal-title" id="leaveDetailLabel">
                        Leave <span id="leave_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Staff</th>
                                <td id="person"></td>
                            </tr>
                            <tr>
                                <th>Staff ID</th>
                                <td id="employee_id"></td>
                            </tr>
                            <tr>
                                <th>Reason</th>
                                <td id="reason"></td>
                            </tr>
                            <tr>
                                <th>Start Date</th>
                                <td id="start_date"></td>
                            </tr>
                            <tr>
                                <th>End Date</th>
                                <td id="end_date"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="status"></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td id="type"></td>
                            </tr>
                            <tr>
                                <th>Paid</th>
                                <td id="paid"></td>
                            </tr>
                            <tr>
                                <th>Hours</th>
                                <td id="hours"></td>
                            </tr>
                            <tr>
                                <th>Approved Hours</th>
                                <td id="approved_hours"></td>
                            </tr>
                            <tr>
                                <th>Auto Split</th>
                                <td id="auto_split"></td>
                            </tr>
                            <tr>
                                <th>SSP Paid Days</th>
                                <td id="ssp_paid_days"></td>
                            </tr>
                            <tr>
                                <th>Unpaid Days</th>
                                <td id="unpaid_days"></td>
                            </tr>
                            <tr>
                                <th>Amount Paid</th>
                                <td id="amount_paid"></td>
                            </tr>
                            <tr>
                                <th>Amount Paid</th>
                                <td id="amount_paid"></td>
                            </tr>

                        </tbody>
                    </table>
                    <div class="row">

                        <div class="col-md-12" id="reject_reason_wrapper_view" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Reject Reason</label>
                                <p id="reject_reason_view" class="form-control-plaintext text-danger"></p>
                            </div>
                        </div>
                    </div>
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
            $(".select2_modal").select2({
                dropdownParent: $("#add_leave")
            });

            $(".select2_edit_modal").select2({
                dropdownParent: $("#edit_leave")
            });

            $('#add_leave_form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $(this).find('button[type="submit"]');

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
                        $('#add_leave_form')[0].reset();
                        closeBsModal('#add_leave');
                        toast_success('Leave Added Successfully');
                        reloadDatatable('#leave_requests-table');
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
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
            $('#edit_leave_form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let hoydayId = $('#edit_leave_id').val(); // Make sure to store leave ID here

                let submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true).html('Updating...');
                $.ajax({
                    url: `${baseUrl}/leaves/` + hoydayId,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'X-HTTP-Method-Override': 'PUT' // simulate PUT for Laravel
                    },
                    success: function(response) {
                        closeBsModal('#edit_leave');
                        toast_success('Leave updated successfully!');
                        reloadDatatable('#leave_requests-table');
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

        function editLeave(record_id) {
            $.get(`${baseUrl}/editleave/` + record_id, function(data) {
                if (data.leave) {
                    $('#edit_leave_id').val(record_id);

                    $('#edit_leave_entitlement').val(data.leave.reason);
                    $('#edit_from_date').val(data.leave.start_date);
                    $('#edit_to_date').val(data.leave.end_date);
                    $('#edit_status').val(data.leave.status);

                    // Format the type for display
                    let typeLabels = {
                        'annual_leave': 'Annual Leave',
                        'sick_leave': 'Sick Leave',
                        'emergency': 'Emergency',
                        'other': 'Other'
                    };
                    $('#edit_type').val(typeLabels[data.leave.type] || 'Other');

                    $('#edit_employee_id').val(data.leave.employee_id).trigger('change');

                    $('#edit_leave').modal('show');
                }
            });
        }

        let selectedId = null;

        function deleteLeave(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteleave/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Leave deleted successfully!');
                        reloadDatatable('#leave_requests-table');
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
                toast_danger('Please select at least one leave to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected leaves?')) return;

            $.ajax({
                url: '{{ route('leaves.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected leaves deleted successfully!');
                    reloadDatatable('#employee_leaves-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });
    </script>

    <script>
        function viewLogs(hoydayId) {
            // Clear existing content
            const modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            fetch(`${baseUrl}/leaves/${hoydayId}/logs/ajax`)
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

        function viewLeaveDetail(id) {
            $.get(`${baseUrl}/leaves/${id}/view`, function(data) {
                $('#person').text(data.user);
                $('#employee_id').text(data.employee_id);
                $('#reason').text(data.reason);
                $('#start_date').text(data.start_date);
                $('#end_date').text(data.end_date);
                $('#status').text(data.status);
                $('#type').text(data.type);
                $('#paid').text(data.paid);
                $('#hours').text(data.hours);
                $('#approved_hours').text(data.approved_hours);
                $('#auto_split').text(data.auto_split);
                $('#ssp_paid_days').text(data.ssp_paid_days);
                $('#unpaid_days').text(data.unpaid_days);
                $('#amount_paid').text(data.amount_paid);

                if (data.status.toLowerCase() === 'rejected' || data.status.toLowerCase() === 'denied') {
                    $('#reject_reason_wrapper_view').show();
                    $('#reject_reason_view').text(data.reject_reason ?? 'N/A');
                } else {
                    $('#reject_reason_wrapper_view').hide();
                }

                new bootstrap.Modal(document.getElementById('viewLeaveDetailModal')).show();
            }).fail(function() {
                toast_danger('Failed to fetch leave details.');
            });
        }

        $(document).on('change', '#edit_status', function() {
            let status = $(this).val();

            if (status === 'denied' || status === 'rejected') {
                $('#reject_reason_wrapper').show();
                $('#reject_reason').attr('required', true);
            } else {
                $('#reject_reason_wrapper').hide();
                $('#reject_reason').removeAttr('required').val('');
            }
        });
    </script>
    {!! $dataTable->scripts() !!}
@endsection
