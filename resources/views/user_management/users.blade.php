@extends('layouts.app')
@section('title', 'SPL Connect - Users')
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Dashboard / Users</h2>

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
                                <a href="{{ route('users.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('users.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_user"
                        class="add_btn btn btn-white d-inline-flex align-items-center">
                        <i class="ti ti-plus me-2"></i>User
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

            @hasanyrole('superadmin')
            <!-- Tabs Navigation -->
            <div class="card mb-4">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs nav-tabs-bordered" id="usersTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab" aria-controls="all-users" aria-selected="true">
                                All Users
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-users-tab" data-bs-toggle="tab" data-bs-target="#admin-users" type="button" role="tab" aria-controls="admin-users" aria-selected="false">
                                SaaS
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            @endhasanyrole
            <!-- /Tabs Navigation -->

            <!-- Tabs Content -->
            <div class="tab-content" id="usersTabContent">
                <!-- All Users Tab -->
                <div class="tab-pane fade show active" id="all-users" role="tabpanel" aria-labelledby="all-users-tab">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="custom-datatable-filter table-responsive">
                                <table class="table datatable" id="all-users-table"></table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SaaS Users Tab -->
                <div class="tab-pane fade" id="admin-users" role="tabpanel" aria-labelledby="admin-users-tab">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="custom-datatable-filter table-responsive">
                                <table class="table datatable" id="saas-users-table"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Tabs Content -->
        </div>

        <!-- Add Client -->
        <div class="modal fade" id="add_user">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New User</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data"
                        id="add_user_form">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div
                                                class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">
                                                <div
                                                    class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
                                                    <img id="profile_preview" src="#" alt="Preview"
                                                        style="display: none; width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                                                </div>
                                                <div class="profile-upload">
                                                    <div class="mb-2">
                                                        <h6 class="mb-1">Upload Profile Image</h6>
                                                        <p class="fs-12">Image should be below 4 mb</p>
                                                    </div>
                                                    <div class="profile-uploader d-flex align-items-center">
                                                        <div class="drag-upload-btn btn btn-sm btn-primary me-2">
                                                            Upload
                                                            <input type="file" name="profile_picture"
                                                                class="form-control image-sign" id="profile_picture_input"
                                                                accept="image/*">
                                                        </div>
                                                        <a href="javascript:void(0);"
                                                            class="btn btn-light btn-sm">Cancel</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- User Fields -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">First Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control">
                                                <span class="text-danger form-error" id="error_first_name"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control">
                                                <span class="text-danger form-error" id="error_last_name"></span>
                                            </div>
                                        </div>
                                        {{--<div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Username <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="username" class="form-control">
                                                <span class="text-danger form-error" id="error_username"></span>
                                            </div>
                                        </div>--}}
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email<span class="text-danger"> *</span></label>
                                                <input type="text" name="email" class="form-control">
                                                <span class="text-danger form-error" id="error_email"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Password <span
                                                        class="text-danger">*</span></label>
                                                <div class="pass-group">
                                                    <input type="password" name="password"
                                                        class="pass-input form-control">
                                                    <span class="ti toggle-password ti-eye-off"></span>
                                                </div>
                                                <span class="text-danger form-error" id="error_password"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Confirm Password <span
                                                        class="text-danger">*</span></label>
                                                <div class="pass-group">
                                                    <input type="password" name="password_confirmation"
                                                        class="pass-inputs form-control">
                                                    <span class="ti toggle-passwords ti-eye-off"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="phone_number" class="form-control">
                                                <span class="text-danger form-error" id="error_phone_number"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="Active">Active</option>
                                                    <option value="Inactive">Inactive</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_status"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Roles</label>
                                                <select name="roles[]" class="form-control">
                                                    <option value="">Select Role</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role }}">{{ $role }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_user_form" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Add Client -->

        <!-- Edit Client -->
        <div class="modal fade" id="edit_user">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Worker</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data" id="edit_user_form">

                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <input type="hidden" id="edit_user_id" name="user_id">

                                            <div
                                                class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">
                                                <div
                                                    class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
                                                    <i class="ti ti-photo"></i>
                                                </div>
                                                <div class="profile-upload">
                                                    <div class="mb-2">
                                                        <h6 class="mb-1">Upload Profile Image</h6>
                                                        <p class="fs-12">Image should be below 4 mb</p>
                                                    </div>
                                                    <div class="profile-uploader d-flex align-items-center">
                                                        <div class="drag-upload-btn btn btn-sm btn-primary me-2">
                                                            Upload
                                                            <input type="file" name="profile_picture"
                                                                class="form-control image-sign" multiple="">
                                                        </div>
                                                        <a href="javascript:void(0);"
                                                            class="btn btn-light btn-sm">Cancel</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">First Name <span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="first_name" id="first_name"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_first_name"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" id="last_name"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_last_name"></span>
                                            </div>
                                        </div>
                                        {{--<div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Username <span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="username" id="username"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_username"></span>
                                            </div>
                                        </div>--}}
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email<span class="text-danger"> *</span></label>
                                                <input type="text" name="email" id="email"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_email"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3 ">
                                                <label class="form-label">Password <span class="text-danger">
                                                        *</span></label>
                                                <div class="pass-group">
                                                    <input type="password" name="password"
                                                        class="pass-input form-control">
                                                    <span class="ti toggle-password ti-eye-off"></span>
                                                </div>
                                                <span class="text-danger form-error" id="error_password"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3 ">
                                                <label class="form-label">Confirm Password <span class="text-danger">
                                                        *</span></label>
                                                <div class="pass-group">
                                                    <input type="password" name="password_confirmation"
                                                        class="pass-inputs form-control">
                                                    <span class="ti toggle-passwords ti-eye-off"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number <span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="phone_number" id="phone_number"
                                                    class="form-control">
                                                <span class="text-danger form-error" id="error_phone_number"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="Active">Active</option>
                                                    <option value="InActive">Inactive</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_status"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Roles</label>
                                                <select name="roles[]" id="roles" class="form-control">
                                                    <option value="">Select Role</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role }}">{{ $role }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_user_form" class="btn btn-primary">Update </button>
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
                    <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data">
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
    <!-- View User Detail Modal -->
    <div class="modal fade" id="viewUserDetailModal" tabindex="-1" aria-labelledby="userDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="userDetailLabel">
                        User <span id="user_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Full Name</th>
                                <td id="user_full_name"></td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td id="user_username"></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td id="user_email"></td>
                            </tr>
                            <tr>
                                <th>Phone Number</th>
                                <td id="user_phone"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="user_status"></td>
                            </tr>
                            <tr>
                                <th>Profile Picture</th>
                                <td id="user_picture"></td>
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

    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <!-- ✅ Image Preview Script -->
    <script>
        document.getElementById('profile_picture_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('profile_preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = "#";
                preview.style.display = "none";
            }
        });
    </script>
    <script>
        // Global variables for DataTables
        let allUsersTable = null;
        let saasUsersTable = null;

        $(document).ready(function() {
            // Initialize All Users DataTable
            allUsersTable = $('#all-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("users.index") }}',
                    data: function(d) {
                        d.filter = 'all';
                    }
                },
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center px-2' },
                    { data: 'number', name: 'number', orderable: false, searchable: false, className: 'px-2' },
                    { data: 'name', name: 'name', className: 'ps-0' },
                    { data: 'email', name: 'email' },
                    { data: 'roles', name: 'roles', orderable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' }
                ],
                order: [[6, 'desc']],
                pageLength: 25,
                scrollX: true,
                drawCallback: function(settings) {
                    feather.replace();
                    var api = this.api();
                    var start = api.page.info().start;
                    api.column(1, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                },
                dom: 't<"d-flex justify-content-between mt-2"<"col-sm-12 col-md-5 align-self-center ps-3"i><"d-flex justify-content-between" p>>'
            });

            // Initialize SaaS Users DataTable (admin users only)
            saasUsersTable = $('#saas-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("users.index") }}',
                    data: function(d) {
                        d.filter = 'saas';
                    }
                },
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center px-2' },
                    { data: 'number', name: 'number', orderable: false, searchable: false, className: 'px-2' },
                    { data: 'name', name: 'name', className: 'ps-0' },
                    { data: 'email', name: 'email' },
                    { data: 'roles', name: 'roles', orderable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' }
                ],
                order: [[6, 'desc']],
                pageLength: 25,
                scrollX: true,
                drawCallback: function(settings) {
                    feather.replace();
                    var api = this.api();
                    var start = api.page.info().start;
                    api.column(1, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                },
                dom: 't<"d-flex justify-content-between mt-2"<"col-sm-12 col-md-5 align-self-center ps-3"i><"d-flex justify-content-between" p>>'
            });

            // Tab switching - redraw DataTable when tab becomes visible
            $('#usersTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr("data-bs-target");
                if (target === '#all-users') {
                    if (allUsersTable) {
                        allUsersTable.columns.adjust().responsive.recalc();
                    }
                } else if (target === '#admin-users') {
                    if (saasUsersTable) {
                        saasUsersTable.columns.adjust().responsive.recalc();
                    }
                }
            });

            $('#add_user_form').on('submit', function(e) {
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
                        $('#add_user_form')[0].reset();
                        closeBsModal('#add_user');
                        toast_success('User Added Successfully');
                        // Reload both tables
                        if (allUsersTable) allUsersTable.ajax.reload();
                        if (saasUsersTable) saasUsersTable.ajax.reload();
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
            $('#edit_user_form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let userId = $('#edit_user_id').val(); // Make sure to store user ID here

                let submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true).html('Updating...');
                $.ajax({
                    url: `${baseUrl}/users/` + userId,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'X-HTTP-Method-Override': 'PUT' // simulate PUT for Laravel
                    },
                    success: function(response) {
                        closeBsModal('#edit_user');
                        toast_success('User updated successfully!');
                        // Reload both tables
                        if (allUsersTable) allUsersTable.ajax.reload();
                        if (saasUsersTable) saasUsersTable.ajax.reload();
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

        function editUser(record_id) {
            $.get(`${baseUrl}/edituser/` + record_id, function(data) {
                if (data.user) {
                    $('#edit_user_id').val(record_id); // store user ID
                    $('#first_name').val(data.user.first_name);
                    $('#last_name').val(data.user.last_name);
                    $('#email').val(data.user.email);
                    // $('#username').val(data.user.username);
                    $('#phone_number').val(data.user.phone_number);
                    $('#status').val(data.user.status);
                    $('#roles').val(Object.values(data.userRoles)).trigger('change');
                    $('#edit_user').modal('show');
                }
            });
        }

        let selectedId = null;

        function deleteUser(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteuser/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('User deleted successfully!');
                        // Reload both tables
                        if (allUsersTable) allUsersTable.ajax.reload();
                        if (saasUsersTable) saasUsersTable.ajax.reload();
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });

        // Bulk delete button - works with current active table
        $('#bulkDeleteBtn').on('click', function() {
            // Determine which table is active
            let activeTable = null;
            let tableId = null;
            
            if ($('#all-users').hasClass('active')) {
                activeTable = allUsersTable;
                tableId = '#all-users-table';
            } else if ($('#admin-users').hasClass('active')) {
                activeTable = saasUsersTable;
                tableId = '#saas-users-table';
            }
            
            if (!activeTable) return;
            
            const selected = $(tableId + ' .dT-row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                toast_danger('Please select at least one user to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected users?')) return;

            $.ajax({
                url: '{{ route('users.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected users deleted successfully!');
                    // Reload both tables
                    if (allUsersTable) allUsersTable.ajax.reload();
                    if (saasUsersTable) saasUsersTable.ajax.reload();
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });

        // Helper function to reload the appropriate datatable
        function reloadDatatable(tableId) {
            if (tableId === '#all-users-table' && allUsersTable) {
                allUsersTable.ajax.reload();
            } else if (tableId === '#saas-users-table' && saasUsersTable) {
                saasUsersTable.ajax.reload();
            } else {
                // Reload both if unsure
                if (allUsersTable) allUsersTable.ajax.reload();
                if (saasUsersTable) saasUsersTable.ajax.reload();
            }
        }
    </script>

    <script>
        function viewLogs(userId) {
            // Clear existing content
            const modalBody = document.querySelector('#logModal .modal-body');
            modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

            fetch(`${baseUrl}/users/${userId}/logs/ajax`)
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

        function viewUserDetail(id) {
            $.get(`${baseUrl}/users/${id}/view`, function(data) {
                $('#user_name_heading').text(data.name);
                $('#user_full_name').text(data.first_name + ' ' + data.last_name);
                $('#user_username').text(data.username);
                $('#user_email').text(data.email);
                $('#user_phone').text(data.phone_number);
                $('#user_status').text(data.status);

                if (data.profile_picture) {
                    $('#user_picture').html(
                        `<img src="${data.profile_picture}" alt="Profile" width="100" class="rounded">`);
                } else {
                    $('#user_picture').text('No profile picture');
                }

                new bootstrap.Modal(document.getElementById('viewUserDetailModal')).show();
            }).fail(function() {
                toast_danger('Failed to fetch user details.');
            });
        }
    </script>
@endsection
