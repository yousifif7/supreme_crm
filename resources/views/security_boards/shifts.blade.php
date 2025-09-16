@extends('layouts.app')
@section('title', 'Shifts')
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper security_board">
        <div class="content">
            <div class="alert-box-container"></div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {!! session('warning') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shifts</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary me-2" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('shifts.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('shifts.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_shift"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Shift
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

            <!-- /Breadcrumb -->
        </div>
      
                @include('security_boards.edit')
                @include('security_boards.shiftmodal')

        <!-- Edit Shift -->
    
        <!-- Import modal -->
        <div class="modal fade" id="import_modal">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Import Shifts</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('shifts.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-info">
                                                <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:</h6>
                                                <ul class="mb-0 small">
                                                    <li>Headers should be in Row 1 starting from Column A</li>
                                                    <li>Data should start from Row 2, Column A onwards</li>
                                                    <li><strong>Required:</strong> Date, Client, Site, Start, End</li>
                                                    <li><strong>Optional:</strong> #, Day, Officer, Phone, Lost Time, Hours, Comments</li>
                                                    <li><strong>Date format:</strong> 01-May-2025, 2025-05-01, 01/05/2025</li>
                                                    <li><strong>Time format:</strong> 06:00, 18:00, 6:00, 18:00</li>
                                                    <li>Client and Site names must exist in the database</li>
                                                    <li>Officer names are matched against employee records (first name, last name, or full name)</li>
                                                    <li>Hours will be calculated automatically if not provided</li>
                                                    <li>If Officer is assigned, SIA license expiry and overlapping shifts will be checked</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('shifts.export.excel', ['template' => 1]) }}" class="btn btn-outline-primary w-100">
                                                <i class="ti ti-download"></i> Download Template
                                            </a>
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

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 20%; min-width: 20%;">
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

        <!-- Add Shift Success -->
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
                                        <a href="{{ url('shifts') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assign Shift Modal -->
        @include('security_boards.assign-shift-modal')

    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initDaySelector(shiftGroup) {
                const dayBoxes = shiftGroup.querySelectorAll('.day-box');
                const hiddenInput = shiftGroup.querySelector('input[name="days[]"]');

                dayBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        box.classList.toggle('selected');
                        const selected = Array.from(shiftGroup.querySelectorAll(
                                '.day-box.selected'))
                            .map(el => el.getAttribute('data-day'));

                        hiddenInput.value = selected.join(',');
                    });
                });
            }

            function bindEvents() {
                // Add Shift Button
                document.querySelectorAll('.addShiftGroup').forEach(btn => {
                    btn.onclick = function() {
                        const wrapper = document.querySelector('.shift-wrapper');
                        const lastGroup = wrapper.querySelector('.shift-group:last-of-type');
                        const clone = lastGroup.cloneNode(true);

                        // Reset values in clone
                        clone.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        });

                        // Reset day selection
                        clone.querySelectorAll('.day-box').forEach(box => box.classList.remove(
                            'selected'));
                        clone.querySelector('input[name="days[]"]').value = '';

                        wrapper.appendChild(clone);

                        // Re-init new shift group logic
                        initDaySelector(clone);
                        bindEvents();
                    };
                });

                // Remove Shift Button
                document.querySelectorAll('.remove-shift').forEach(btn => {
                    btn.onclick = function() {
                        const shiftGroups = document.querySelectorAll('.shift-wrapper .shift-group');
                        if (shiftGroups.length > 1) {
                            btn.closest('.shift-group').remove();
                        } else {
                            toast_danger('You must have at least one shift.');
                        }
                    };
                });
            }

            // Initialize for first shift-group
            document.querySelectorAll('.shift-group').forEach(group => initDaySelector(group));

            // Initial binding
            bindEvents();
        });
    </script>
    <script>
        $(document).ready(function() {
            $(".select2_modal").select2({
                dropdownParent: $("#edit_shift")
            });
            
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = this;
                let formData = new FormData(form);
                let submitButton = $('#saveshift');

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                // Clear previous errors
                $('.form-error').text('');

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content') // Better practice
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        toast_success('Shift Added Successfully');
                        reloadDatatable('#shifts-table');

                        // Optional: Reset form after success
                        form.reset();
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
          

            // Bulk delete button
            $('#bulkDeleteBtn').on('click', function() {
                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (selected.length === 0) {
                    toast_danger('Please select at least one shift to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected shifts?')) return;

                $.ajax({
                    url: '{{ route('shifts.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected shifts deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function() {
                        toast_danger('Something went wrong during bulk delete.');
                    }
                });
            });
        });

        let selectedId = null;

        function deleteShift(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteshift/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Shift deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });
        $(document).on("change", "#clientSelect", function() {
            var $this = $(this);
            const clientId = $(this).val();
            if (!clientId) return;

            var $siteSelect = $('#siteSelect');
            $siteSelect.html('<option value="">--choose--</option>');

            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $this.parents('.shift-group').find('.siteRate').val(data.client.office_rate || '');
                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function(index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site
                                .site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

        $(document).on("change", "#StaffSelect", function() {
            var $this = $(this);
            const staffId = $(this).val();
            if (!staffId) return;

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $this.parents('.shift-group').find('.staffRate').val(data.employee.guard_rate ||
                        '');
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

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
