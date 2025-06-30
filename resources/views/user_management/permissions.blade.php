@extends('layouts.app')
@section('title', 'CRM - Permissions')
@section('contents')
    <div id="permissions-wrapper" class="page-wrapper">
        <div class="content">
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Permissions</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">


                    <div class="dropdown">
                        <button class="btn btn-danger" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_permission"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Permission
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

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Permission Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $permission)
                                    <tr>
                                        <td><input type="checkbox" class="dT-row-checkbox"
                                                value="{{ $permission->id }}"></td>
                                        <td>{{ $permission->name }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning"
                                                onclick="editPermission({{ $permission->id }})">Edit</button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="deletePermission({{ $permission->id }})">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $permissions->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add Permission Modal -->
        <div class="modal fade" id="add_permission">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('permissions.store') }}" id="add_permission_form">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Permission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Permission Name</label>
                                <input type="text" name="name" id="permission_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="save_permission_btn" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Permission Modal -->
        <div class="modal fade" id="edit_permission_modal">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="edit_permission_form">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Permission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Permission Name</label>
                                <input type="text" name="name" id="edit_permission_name" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="update_permission_btn" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Success Modal -->
        <div class="modal fade" id="success_modal">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <span class="avatar avatar-lg avatar-rounded bg-success mb-3">
                            <i class="ti ti-check fs-24"></i>
                        </span>
                        <h5 class="mb-2" id="success_message"></h5>
                        <a href="{{ route('permissions.index') }}" class="btn btn-dark w-100">Back to List</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
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
    </div>
@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
            $('.datatable').DataTable();
        });

        $(document).on('submit', '#add_permission_form', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#save_permission_btn');
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    $('#add_permission').modal('hide');
                    $('#success_message').text('Permission created successfully');
                    $('#success_modal').modal('show');
                    form[0].reset();
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON?.message ?? 'Something went wrong.');
                },
                complete: () => btn.prop('disabled', false).text('Save')
            });
        });

        function editPermission(id) {
            $.get(`${baseUrl}/permissions/` + id + '/edit', function(data) {
                $('#edit_permission_form').attr('action', '/permissions/' + id);
                $('#edit_permission_name').val(data.permission.name);
                $('#edit_permission_modal').modal('show');
            });
        }

        $(document).on('submit', '#edit_permission_form', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#update_permission_btn');
            btn.prop('disabled', true).text('Updating...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                },
                success: function(response) {
                    $('#edit_permission_modal').modal('hide');
                    $('#success_message').text('Permission updated successfully');
                    $('#success_modal').modal('show');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON?.message ?? 'Something went wrong.');
                },
                complete: () => btn.prop('disabled', false).text('Update')
            });
        });

        let selectedPermissionId = null;

        function deletePermission(id) {
            selectedPermissionId = id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedPermissionId !== null) {
                $.ajax({
                    url: `${baseUrl}/permissions/${selectedPermissionId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');
                        $('#success_message').html('Permission deleted successfully!');
                        $('#success_modal').modal('show');
                    },
                    error: function() {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
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
                alert('Please select at least one permission to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected permissions?')) return;

            $.ajax({
                url: '{{ route('permissions.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#success_message').text('Selected permissions deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
                }
            });
        });
    </script>
@endsection
