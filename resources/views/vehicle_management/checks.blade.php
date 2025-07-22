@extends('layouts.app')
@section('title', 'CRM - Roadworthiness Check ')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Roadworthiness Check </h2>
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
                                <a href="{{ route('checks.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('checks.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_check"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Check
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
                                    <th>Date Completed</th>
                                    <th>Checked By</th>
                                    <th>Defects Found</th>
                                    <th>Corrective Action Taken</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($checks as $check)
                                    <tr>
                                        <td><input type="checkbox" class="check-checkbox" value="{{ $check->id }}"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $check->date_completed }}</td>
                                        <td>{{ $check->checked_by }}</td>
                                        <td>{{ $check->defects_found }}</td>
                                        <td>{{ $check->corrective_action_taken }}</td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <a href="#" class="me-2" onclick="editCheck({{ $check->id }})">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="deleteCheck({{ $check->id }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="card-footer d-flex justify-content-center">
                            {{ $checks->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>


            </div>
            <!-- Add Roadworthiness Check Modal -->
            <div class="modal fade" id="add_check">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Roadworthiness Check</h5>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="add_check_form" action="{{ route('checks.store') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                            <select name="vehicle_id" class="form-control" id="vehicle_id">
                                                <option value="">Select vehicle</option>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">
                                                        {{ $vehicle->registration_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="error_vehicle_id"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Completed <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="date_completed" class="form-control"
                                                id="date_completed">
                                            <span class="text-danger form-error" id="error_date_completed"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Checked By <span class="text-danger">*</span></label>
                                            <input type="text" name="checked_by" class="form-control" id="checked_by">
                                            <span class="text-danger form-error" id="error_checked_by"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Defects Found <span
                                                    class="text-danger">*</span></label>
                                            <textarea name="defects_found" class="form-control" id="defects_found" rows="3"></textarea>
                                            <span class="text-danger form-error" id="error_defects_found"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Corrective Action Taken <span
                                                    class="text-danger">*</span></label>
                                            <textarea name="corrective_action_taken" class="form-control" id="corrective_action_taken" rows="3"></textarea>
                                            <span class="text-danger form-error"
                                                id="error_corrective_action_taken"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="add_check_form" id="savecheck" class="btn btn-primary">
                                    Save Check
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Roadworthiness Check Modal -->
            <div class="modal fade" id="edit_check">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Roadworthiness Check</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form method="POST" id="edit_check_form" action="#">
                            @csrf
                            <input type="hidden" name="check_id" id="check_id">
                            <div class="modal-body pb-0">
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                            <select name="vehicle_id" id="edit_vehicle_id" class="form-control">
                                                <option value="">Select vehicle</option>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">
                                                        {{ $vehicle->registration_number }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger form-error" id="edit_error_vehicle_id"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Completed <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="date_completed" id="edit_date_completed"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_date_completed"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Checked By <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="checked_by" id="edit_checked_by"
                                                class="form-control">
                                            <span class="text-danger form-error" id="edit_error_checked_by"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Defects Found <span
                                                    class="text-danger">*</span></label>
                                            <textarea name="defects_found" id="edit_defects_found" class="form-control" rows="3"></textarea>
                                            <span class="text-danger form-error" id="edit_error_defects_found"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Corrective Action Taken <span
                                                    class="text-danger">*</span></label>
                                            <textarea name="corrective_action_taken" id="edit_corrective_action_taken" class="form-control" rows="3"></textarea>
                                            <span class="text-danger form-error"
                                                id="edit_error_corrective_action_taken"></span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="edit_check_form" class="btn btn-primary"
                                    id="edit_check_btn">
                                    Update Check
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Add Vehicle Compliances Success -->
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
                                            <a href="{{ url('roadworthiness_check') }}" class="btn btn-dark w-100">Back
                                                to
                                                List</a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Add Vehicle Success -->

            <!-- Delete Modal -->
            <div class="modal fade" id="delete_modal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                                <i class="ti ti-trash-x fs-36"></i>
                            </span>
                            <h4 class="mb-1">Confirm Delete</h4>
                            <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.
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
                        <form action="{{ route('checks.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                    aria-labelledby="info-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex gap-2">
                                                    <input type="file" name="import_file" class="form-control"
                                                        required>
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
        <!-- /Page Wrapper -->
    @endsection
    @section('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
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
                $('.check-checkbox').prop('checked', $(this).prop('checked'));
            });

            $(document).ready(function() {
                // Add Check
                $('#add_check_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#savecheck');

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
                            $('#add_check').modal('hide');
                            $('#success_message').html('Roadworthiness check added successfully.');
                            $('#success_modal').modal('show');
                            $('#add_check_form')[0].reset();
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
                            submitButton.prop('disabled', false).html('Save Check');
                        }
                    });
                });

                // Edit Check
                $('#edit_check_form').on('submit', function(e) {
                    e.preventDefault();
                    $("[id^='edit_error_']").text('');
                    let form = $(this)[0];
                    let formData = new FormData(form);
                    let submitButton = $('#edit_check_btn');
                    let checkId = $('#check_id').val();

                    submitButton.prop('disabled', true).html('Updating...');

                    $.ajax({
                        url: `${baseUrl}/updatecheck/${checkId}`, // Your route to update the check
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            $('#edit_check').modal('hide');
                            $('#success_message').html(
                                'Roadworthiness check updated successfully.');
                            $('#success_modal').modal('show');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, value) {
                                    $('#edit_error_' + key).text(value[0]);
                                });
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).html('Update Check');
                        }
                    });
                });
            });

            function editCheck(check_id) {
                $.get('/editcheck/' + check_id, function(data) {
                    if (data.check) {
                        $('#check_id').val(data.check.id); // Hidden input
                        $('#edit_vehicle_id').val(data.check.vehicle_id);
                        $('#edit_date_completed').val(data.check.date_completed);
                        $('#edit_checked_by').val(data.check.checked_by);
                        $('#edit_defects_found').val(data.check.defects_found);
                        $('#edit_corrective_action_taken').val(data.check.corrective_action_taken);

                        $('#edit_check').modal('show');
                    } else {
                        alert('No roadworthiness check data found for this record.');
                    }
                });
            }

            let selectedId = null;

            function deleteCheck(record_id) {
                selectedId = record_id;
                $('#delete_modal').modal('show');
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (selectedId !== null) {
                    $.ajax({
                        url: `${baseUrl}/deletecheck/${selectedId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#delete_modal').modal('hide');
                            $('#success_message').html('Check Deleted Successfully!');
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
                const selected = $('.check-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (selected.length === 0) {
                    alert('Please select at least one check to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected check?')) return;
                $.ajax({
                    url: '{{ route('checks.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#success_message').text('Selected check deleted successfully!');
                        $('#success_modal').modal('show');
                    },
                    error: function() {
                        alert('Something went wrong during bulk delete.');
                    }
                });
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
