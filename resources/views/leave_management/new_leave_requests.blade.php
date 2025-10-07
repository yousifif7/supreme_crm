@extends('layouts.app')
@section('title', 'Pending Leaves')

@section('contents')
    <div class="page-wrapper">
        <div class="content">
            <h2>Pending Leave Requests</h2>
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
                </div>
            </div>


            {{-- Add leave requests --}}
            @include('leave_management.add-leave')

            <table class="table table-striped" id="pendingLeavesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Reason</th>
                        <th>Staff Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Hours</th>
                        <th>Control</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
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
                    <p class="mb-3">This action cannot be undone. Are you sure you want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
<div class="modal fade" id="rejectModal" style="margin-left:50px;">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 450px;">
        <form id="rejectForm">
            @csrf
            <input type="hidden" id="leave_id" name="leave_id">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Reject Leave</h4>
                    <p class="mb-3">Please provide a reason for rejection.</p>
                    <textarea class="form-control mb-3" name="reason" id="reason" rows="3" required></textarea>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>




    <!-- Toast -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="liveToast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastBody"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>


    <!-- View Leave Detail Modal -->
    <div class="modal fade" id="viewLeaveDetailModal" tabindex="-1" aria-labelledby="leaveDetailLabel" aria-hidden="true">
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


    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"> <!-- modal-sm makes it smaller -->
            <form id="approveForm">
                @csrf
                <input type="hidden" id="approve_leave_id" name="leave_id">
                <div class="modal-content text-center"> <!-- center all content -->
                    <div class="modal-body">
                        <span class="avatar avatar-xl  text-success mb-3 d-inline-block">
                            <i class="ti ti-check fs-36"></i>
                        </span>
                        <h4 class="mb-1">Confirm Approval</h4>
                        <p class="mb-3">Are you sure you want to approve the leave?</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- approval confirmation --}}
    {{-- <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="approveForm">
            @csrf
            <input type="hidden" id="approve_leave_id" name="leave_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to approve this leave?
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Approve</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div> --}}

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#pendingLeavesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('leaves.pending') }}",
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        title: '#',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'reason'
                    },
                    {
                        data: 'staff_name'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'start_date'
                    },
                    {
                        data: 'end_date'
                    },
                    {
                        data: 'hours'
                    },
                    {
                        data: 'control',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Initialize Bootstrap 5 modals
            const rejectModalEl = document.getElementById('rejectModal');
            const rejectModal = new bootstrap.Modal(rejectModalEl);

            const approveModalEl = document.getElementById('approveModal');
            const approveModal = new bootstrap.Modal(approveModalEl);

            // Select all checkboxes
            $('#selectAll').on('click', function() {
                var checked = $(this).is(':checked');
                $('.dT-row-checkbox').prop('checked', checked);
            });

            // Bulk delete
            $('#bulkDeleteBtn').on('click', function() {
                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                if (!selected.length) return toast_danger('Please select at least one leave to delete.');

                if (!confirm('Are you sure you want to delete the selected leaves?')) return;

                $.ajax({
                    url: '{{ route('leaves.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        toast_success('Selected leaves deleted successfully!');
                        table.ajax.reload(null, false);
                    },
                    error: function() {
                        toast_danger('Something went wrong during bulk delete.');
                    }
                });
            });

            // Open Reject Modal
            $('#pendingLeavesTable').on('click', '.reject-btn', function() {
                const leaveId = $(this).data('id');
                $('#rejectModal #leave_id').val(leaveId);
                $('#rejectModal #reason').val('');
                rejectModal.show();
            });

            // Open Approve Modal
            $('#pendingLeavesTable').on('click', '.approve-btn', function() {
                const leaveId = $(this).data('id');
                $('#approveModal #approve_leave_id').val(leaveId);
                approveModal.show();
            });

            // Reject AJAX
            $('#rejectForm').on('submit', function(e) {
                e.preventDefault();
                const leaveId = $('#rejectModal #leave_id').val();
                const reason = $('#rejectModal #reason').val();

                $.ajax({
                    url: `/leaves/reject/${leaveId}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        reason
                    },
                    success: function() {
                        toast_success('Leave rejected successfully!');
                        table.ajax.reload(null, false);
                        rejectModal.hide();
                    },
                    error: function() {
                        toast_danger('Error rejecting leave.');
                    }
                });
            });

            // Approve AJAX
            $('#approveForm').on('submit', function(e) {
                e.preventDefault();
                const leaveId = $('#approveModal #approve_leave_id').val();

                $.ajax({
                    url: `/leaves/approve/${leaveId}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        toast_success('Leave approved successfully!');
                        table.ajax.reload(null, false);
                        approveModal.hide();
                    },
                    error: function() {
                        toast_danger('Error approving leave.');
                    }
                });
            });

            // Toast helper functions
            function toast_success(message) {
                const toastEl = $('#liveToast');
                toastEl.removeClass('text-bg-danger').addClass('text-bg-success');
                $('#toastBody').text(message);
                new bootstrap.Toast(toastEl[0]).show();
            }

            function toast_danger(message) {
                const toastEl = $('#liveToast');
                toastEl.removeClass('text-bg-success').addClass('text-bg-danger');
                $('#toastBody').text(message);
                new bootstrap.Toast(toastEl[0]).show();
            }
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
                error: function(xhr) {
                    $('.form-error').text(''); // Clear old errors
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            $(`#error_${key}`).text(value[0]);
                        });
                    } else {
                        toast_danger('An unexpected error occurred.');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false).html('Update');
                }
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
    </script>
@endsection
