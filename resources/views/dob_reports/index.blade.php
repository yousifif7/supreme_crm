@extends('layouts.app')
@section('title', brand_title('DOB'))
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Daily Occurrence Book</h2>
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
                                <a href="{{ route('dobs.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('dobs.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" class="add_btn btn btn-white d-inline-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#dobCreateModal">
                        <i class="ti ti-plus me-2"></i> DOB
                    </a>

                    <!-- Search -->
                    {{-- <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                    </div> --}}
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

    </div>


    <!-- View Modal -->
    <div class="modal fade" id="dobModal" tabindex="-1" aria-labelledby="dobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dobModalLabel">DOB Entry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dobModalBody">
                    <!-- DOB details will load here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteDobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete DOB Entry</h5>
                </div>
                <div class="modal-body">Are you sure you want to delete this entry?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmDeleteDobBtn" type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->


    <!-- EDIT DOB MODAL -->
    <div class="modal fade" id="dobEditModal" tabindex="-1" aria-labelledby="dobEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="dobEditForm">
                <input type="hidden" id="dob_id" name="id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dobEditModalLabel">Edit DOB Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                            <div class="text-danger" id="error_title"></div>
                        </div>
                        <div class="mb-3">
                            <label>Type</label>
                            <select class="form-control" id="entry_type" name="entry_type" required>
                                <option value="">Select Type</option>
                                <option value="incident">Incident</option>
                                <option value="observation">Observation</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="visitor">Visitor</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="text-danger" id="error_entry_type"></div>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                            <div class="text-danger" id="error_description"></div>
                        </div>
                        <div class="mb-3">
                            <label>Location</label>
                            <input type="text" class="form-control" id="location_preview" readonly>
                        </div>
                        <div class="mb-3">
                            <label>Existing Files</label>
                            <ul id="files_preview"></ul>
                        </div>
                        <div class="mb-3">
                            <label>Add Files</label>
                            <input type="file" name="files[]" multiple class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Create DOB Modal -->
    <div class="modal fade" id="dobCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="dobCreateForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create DOB Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Type</label>
                            <select name="entry_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="incident">Incident</option>
                                <option value="observation">Observation</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="visitor">Visitor</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Location</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="text" name="location[latitude]" class="form-control"
                                        placeholder="Latitude(31.123)" required>
                                </div>
                                <div class="col">
                                    <input type="text" name="location[longitude]" class="form-control"
                                        placeholder="Longitude(31.123)" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Files</label>
                            <input type="file" name="media_files[]" multiple class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



@endsection
@section('scripts')
    <script>
        let selectedDobId = null;

        // SHOW DOB ENTRY
        function showDob(id) {
            $.ajax({
                url: '/dobs/' + id,
                type: 'GET',
                success: function(res) {
                    const coords = (res.latitude && res.longitude)
                        ? `${res.latitude}, ${res.longitude}`
                        : 'N/A';

                    const filesHtml = (res.media && res.media.length)
                        ? res.media.map(file => {
                            const url = file.file_url;
                            const name = url.split('/').pop();
                            const isImage = /\.(jpe?g|png|gif|webp|bmp)$/i.test(url);
                            if (isImage) {
                                return `<div class="mb-2">
                                            <a href="${url}" target="_blank">
                                                <img src="${url}" alt="${name}" class="img-fluid rounded border" style="max-height:220px;">
                                            </a>
                                        </div>`;
                            }
                            return `<div class="mb-2"><a href="${url}" target="_blank">${name}</a></div>`;
                        }).join('')
                        : '<p class="text-muted mb-0">No files attached.</p>';

                    let html = `
                <p><strong>Client:</strong> ${res.client_name ?? 'N/A'}</p>
                <p><strong>Site:</strong> ${res.site_name ?? 'N/A'}</p>
                <p><strong>Officer / Handler:</strong> ${res.officer ?? 'N/A'}</p>
                <p><strong>Title:</strong> ${res.title}</p>
                <p><strong>Type:</strong> ${res.entry_type}</p>
                <p><strong>Description:</strong> ${res.description}</p>
                <p><strong>Submitted By:</strong> ${res.user}</p>
                <p><strong>Address:</strong> ${res.address ?? 'N/A'}</p>
                <p><strong>Coordinates (Lat, Lng):</strong> ${coords}</p>
                <p><strong>Files:</strong></p>
                ${filesHtml}
            `;
                    $('#dobModalBody').html(html);
                    $('#dobModal').modal('show');
                },
                error: function() {
                    alert('Unable to fetch DOB details.');
                }
            });
        }

        // EDIT DOB ENTRY
        function editDob(id) {
            $.get('/dobs/' + id, function(data) {
                $('#dob_id').val(data.id);
                $('#title').val(data.title);
                $('#entry_type').val(data.entry_type);
                $('#description').val(data.description);
                const lat = data.location ? data.location.latitude : null;
                const lng = data.location ? data.location.longitude : null;
                $('#location_preview').val((lat && lng) ? `${lat}, ${lng}` : (data.address ?? ''));

                // Populate existing files
                let filesList = $('#files_preview').empty();
                if (data.media.length) {
                    data.media.forEach(file => {
                        filesList.append(
                            `<li><a href="${file.file_url}" target="_blank">${file.file_url.split('/').pop()}</a></li>`
                        );
                    });
                }

                $('#dobEditModal').modal('show');
            });
        }
        // DELETE DOB ENTRY
        function deleteDob(id) {
            selectedDobId = id;
            if (confirm('Are you sure you want to delete this entry?')) {
                $.ajax({
                    url: `/dobs/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function() {
                        toastr.success('DOB entry deleted successfully!');
                        $('#dobs-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        alert('Failed to delete entry.');
                    }
                });
            }
        }

        // CREATE / UPDATE DOB ENTRY AJAX
        $('#dobEditForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#dob_id').val();
            // Use FormData (not serialize) so attached files are actually sent.
            // Spoof PUT over POST because browsers can't upload files on a real PUT.
            let formData = new FormData(this);
            formData.append('_method', 'PUT');

            $.ajax({
                url: '/dobs/' + id,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    $('#dobEditModal').modal('hide');
                    toastr.success(res.message);
                    $('#dobs-table').DataTable().ajax.reload();
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
            });
        });

        // CREATE DOB ENTRY
        $('#dobCreateForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this); // support files
            $.ajax({
                url: '/dobs', // Laravel store route
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    var modalEl = document.getElementById('dobCreateModal');
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();
                    toastr.success(res.message);
                    $('#dobs-table').DataTable().ajax.reload();
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
            });
        });


        $(document).on('click', '#selectAll', function() {
            var checked = $(this).is(':checked');
            $('.dob-checkbox').prop('checked', checked);
        });

        $('#bulkDeleteBtn').on('click', function() {
            var selectedIds = $('.dob-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                toastr.warning('Please select at least one entry.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected entries?')) return;

            $.ajax({
                url: '/dobs/bulk-delete',
                type: 'POST',
                data: {
                    ids: selectedIds
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    toastr.success(res.message);
                    $('#dobs-table').DataTable().ajax.reload();
                },
                error: function() {
                    toastr.error('Failed to delete selected entries.');
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
