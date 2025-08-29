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
                    <button class="btn btn-primary" id="bulkDeleteBtn">Delete Selected</button>
                    <a href="javascript:void(0);" class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                        data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i>Export
                    </a>
                    <ul class="dropdown-menu dropdown-menu-start p-3">
                        <li><a href="{{ route('invoices.export.pdf') }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a></li>
                        <li><a href="{{ route('invoices.export.excel') }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a></li>
                    </ul>
                </div>
            </div>

            <div class="me-2 mb-2 filter_area">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generate_payroll">Generate</button>
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
                    {!! $dataTable->table(['class' => 'table datatable']) !!}
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
                    $sites = App\Models\Site::all();
                @endphp

                <form method="POST" id="generate_payroll-form">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Select Staff</label>
                            <select name="security_staff_id" id="employee_id" class="form-select select2_assign_modal" required>
                                <option value="">-- Choose Staff --</option>
                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger form-error" id="payrollerror_security_staff_id"></span>
                        </div>

                        <div class="mb-3">
                            <label for="site_id" class="form-label">Employee Site</label>
                            <select name="site_id" id="payroll_site_id" class="form-select" required>
                                <option value="">-- Choose Site --</option>
                                {{-- @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach --}}
                            </select>
                            <span class="text-danger form-error" id="payrollerror_site_id"></span>
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
                    <p class="mb-3">You want to delete all the marked items, this can't be undone once you delete.</p>
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

        let formData = $('#generate_payroll-form').serialize();

        $.ajax({
            url: `/generatepayroll`,
            type: 'POST',
            data: formData,
            success: function(response) {
                toast_success(response.message ?? "Payroll generated successfully");
                $('#generate_payroll').modal('hide');
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
            $.get(`${baseUrl}/generatepayroll/` + record_id, function(data) {
                if (data.employee) {
                    $('#employee_id').val(record_id);
                    $.each(data.sites, function(index, item) {
                        $('#payroll_site_id').append(
                            $('<option>', {
                                value: item.site.id,
                                text: item.site.site_name
                            })
                        );
                    });
                    $('#payroll_employee_name').val(`${data.employee.first_name} ${data.employee.last_name}`);
                    $('#generate_payroll').modal('show');
                }
            });
        }

    $(document).ready(function() {
        $('.select2_assign_modal').select2({
            placeholder: "-- Choose Staff --",
            allowClear: true,
            width: '100%' // makes it match Bootstrap form width
        });
    });
</script>

</script>

{!! $dataTable->scripts() !!}
@endsection