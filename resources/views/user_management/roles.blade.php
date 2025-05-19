@extends('layouts.app')
@section('title', 'CRM - Roles')
@section('contents')
    <!-- Page Wrapper -->
    <div id="roles-wrapper" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Roles</h2>

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
                                <a href="{{ route('roles.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('roles.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_role"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Role
                    </a>


                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">


                        <!-- /Search -->


                    </div>
                    <div class="sort-box">
                        <select name="" id="" class="form-control">
                            <option value="" hidden>Sort Role</option>
                            <option value="">All</option>
                            <option value="">Coordinators</option>
                            <option value="">Archieved</option>
                        </select>
                        <i class="ti ti-chevron-down"></i>
                    </div>

                </div>


            </div>
            <!-- Breadcrumb -->


            <!-- Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td><input type="checkbox" class="role-checkbox" value="{{ $role->id }}">
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $role->name }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="viewPermissions({{ $role->id }})">
                                                View
                                            </button>
                                        </td>
                                        <td>
                                            <a onclick="editRole({{ $role->id }})" class="me-2"><i
                                                    class="ti ti-edit"></i></a>

                                            <a onclick="deleteRole({{ $role->id }})"><i class="ti ti-trash"></i></a>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $roles->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add Role Modal -->
            <div class="modal fade" id="add_role" tabindex="-1" aria-labelledby="addRoleLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-xl">
                    <form method="POST" action="{{ route('roles.store') }}" id="add_role_form">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="role_name" class="form-label">Role Name</label>
                                    <input type="text" name="name" id="role_name" class="form-control" required>
                                </div>

                                @php
                                    $modules = ['Users', 'Clients', 'Sites', 'Shifts'];
                                    $actions = ['Read', 'Write', 'Create', 'Delete', 'Import', 'Export'];
                                @endphp

                                <div class="table-responsive permission-table border rounded">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Module</th>
                                                @foreach ($actions as $action)
                                                    <th>
                                                        <div class="form-check">
                                                            <input class="form-check-input action-select-all"
                                                                type="checkbox" data-action="{{ $action }}">
                                                            <label class="form-check-label">{{ $action }}</label>
                                                        </div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td>
                                                        <div class="form-check form-check-md form-switch me-2">
                                                            <input class="form-check-input module-select-all"
                                                                role="switch" type="checkbox"
                                                                data-module="{{ $module }}">
                                                            <label class="form-check-label">{{ $module }}</label>
                                                        </div>
                                                    </td>
                                                    @foreach ($actions as $action)
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input permission-checkbox"
                                                                    type="checkbox" name="permissions[]"
                                                                    value="{{ $action . ' ' . $module }}"
                                                                    data-module="{{ $module }}"
                                                                    data-action="{{ $action }}">
                                                                <label
                                                                    class="form-check-label">{{ $action }}</label>
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="save_role_btn" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Role Modal -->
            <div class="modal fade" id="edit_role_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-xl">
                    <form method="POST" id="edit_role_form">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="edit_role_id">
                                <div class="mb-3">
                                    <label for="edit_role_name" class="form-label">Role Name</label>
                                    <input type="text" name="name" id="edit_role_name" class="form-control"
                                        required>
                                </div>

                                @php
                                    $modules = ['Users', 'Clients', 'Sites', 'Shifts'];
                                    $actions = ['Read', 'Write', 'Create', 'Delete', 'Import', 'Export'];
                                @endphp

                                <div class="table-responsive permission-table border rounded">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Module</th>
                                                @foreach ($actions as $action)
                                                    <th>
                                                        <div class="form-check">
                                                            <input class="form-check-input action-select-all"
                                                                type="checkbox" data-action="{{ $action }}">
                                                            <label class="form-check-label">{{ $action }}</label>
                                                        </div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody id="edit_permissions_container">
                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td>
                                                        <div class="form-check form-check-md form-switch me-2">
                                                            <input class="form-check-input module-select-all"
                                                                role="switch" type="checkbox"
                                                                data-module="{{ $module }}">
                                                            <label class="form-check-label">{{ $module }}</label>
                                                        </div>
                                                    </td>
                                                    @foreach ($actions as $action)
                                                        @php $permName = $action . ' ' . $module; @endphp
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox"
                                                                    class="form-check-input edit-permission-checkbox"
                                                                    name="permissions[]" value="{{ $permName }}"
                                                                    id="edit_perm_{{ Str::slug($permName) }}">
                                                                <label
                                                                    class="form-check-label">{{ $action }}</label>
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="update_role_btn" class="btn btn-primary">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>
    <!-- View Permissions Modal -->
    <div class="modal fade" id="viewPermissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Permissions for Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="permissionsList" class="row"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add User Success -->
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
                                    <a href="{{ route('roles.index') }}" class="btn btn-dark w-100">Back to
                                        List</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End user suscces -->

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
                <form action="{{ route('roles.import') }}" method="POST" enctype="multipart/form-data">
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
@endsection
@section('scripts')
    <script>
        // Toggle all by module (row)
        $('.module-select-all').on('change', function() {
            const module = $(this).data('module');
            $('.permission-checkbox[data-module="' + module + '"]').prop('checked', $(this).prop('checked'));
        });

        // Toggle all by action (column)
        $('.action-select-all').on('change', function() {
            const action = $(this).data('action');
            $('.permission-checkbox[data-action="' + action + '"]').prop('checked', $(this).prop('checked'));
        });
    </script>

    <script>
        $(document).on('submit', '#add_role_form', function(e) {
            e.preventDefault();

            let form = $(this);
            let submitBtn = $('#save_role_btn');
            submitBtn.prop('disabled', true).html('Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: function(response) {
                    $('#add_role').modal('hide');
                    $('#success_message').html('Role created successfully!');
                    $('#success_modal').modal('show');
                    form[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            $('#error_' + field).text(messages[0]);
                        });
                    } else {
                        alert('Something went wrong.');
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save');
                }
            });
        });


        function editRole(id) {
            $.get('/roles/' + id + '/edit', function(data) {
                $('#edit_role_form').attr('action', '/roles/' + id);
                $('#edit_role_name').val(data.role.name);

                $('.edit-permission-checkbox').each(function() {
                    const permissionName = $(this).val();
                    if (data.rolePermissions.includes(permissionName)) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                });

                $('#edit_role_modal').modal('show');
            });
        }

        $(document).on('submit', '#edit_role_form', function(e) {
            e.preventDefault();

            let form = $(this);
            let submitBtn = $('#update_role_btn');
            let formData = form.serialize();

            submitBtn.prop('disabled', true).html('Updating...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-HTTP-Method-Override': 'PUT'
                },
                success: function(response) {
                    $('#edit_role_modal').modal('hide');
                    $('#success_message').html('Role updated successfully!');
                    $('#success_modal').modal('show');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            $('#error_' + field).text(messages[0]);
                        });
                    } else {
                        alert('Something went wrong.');
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Update');
                }
            });
        });


        function viewPermissions(id) {
            $.get('/roles/' + id + '/edit', function(data) {
                let container = $('#permissionsList');
                container.empty();
                data.rolePermissions.forEach(perm => {
                    container.append(`
                <div class="col-md-4">
                    <span class="badge bg-primary text-light mb-2">${perm}</span>
                </div>
            `);
                });
                $('#viewPermissionsModal').modal('show');
            });
        }
        let selectedId = null;

        function deleteRole(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `/deleterole/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');

                        $('#success_message').html('Role Deleted Successfully');
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        });
    </script>
    <script>
        // role search functionality
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
            $('.role-checkbox').prop('checked', $(this).prop('checked'));
        });
        // Sidebar Menu
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

        // Bulk delete button
        $('#bulkDeleteBtn').on('click', function() {
            const selected = $('.role-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one role to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected roles?')) return;

            $.ajax({
                url: '{{ route('roles.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#success_message').text('Selected roles deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
                }
            });
        });
    </script>
@endsection
