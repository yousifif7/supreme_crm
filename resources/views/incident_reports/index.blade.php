@extends('layouts.app')
@section('title', 'SPL Connect - Incident Reports')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Incident Reports</h2>
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
                                <a href="{{ route('incident_report.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('incident_report.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>


                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">

                    <a href="#" class="add_btn btn btn-white d-inline-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#incidentCreateModal">
                        <i class="ti ti-plus me-2"></i> Incident
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

    </div>


    <!-- View Modal -->
    <div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="incidentModalLabel">Incident Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="incidentModalBody">
                    <!-- Incident details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteIncidentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Incident</h5>
                </div>
                <div class="modal-body">Are you sure you want to delete this incident?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmDeleteIncidentBtn" type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->


    <!-- Edit Incident Modal -->
    <div class="modal fade" id="incidentEditModal" tabindex="-1" aria-labelledby="incidentEditModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="incidentEditForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="incidentEditModalLabel">Edit Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="incident_id" name="incident_id">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title">
                            <div class="text-danger" id="error_title"></div>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="theft">Theft</option>
                                <option value="assault">Assault</option>
                                <option value="fire">Fire</option>
                                <option value="medical">Medical</option>
                                <option value="property_damage">Property Damage</option>
                                <option value="suspicious_activity">Suspicious Activity</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="text-danger" id="error_category"></div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="under_review">Under Review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <span class="text-danger" id="error_status"></span>
                        </div>

                        <div class="mb-3">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity">
                                <option value="">Select Severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                            <div class="text-danger" id="error_severity"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            <div class="text-danger" id="error_description"></div>
                        </div>

                        <input type="hidden" name="police_notified" value="0">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="police_notified" name="police_notified"
                                value="1">
                            <label class="form-check-label" for="police_notified">Police Notified</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <div id="location_preview" class="border p-2">N/A</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Files</label>
                            <ul id="files_preview" class="list-group"></ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Incident Modal -->
    <div class="modal fade" id="incidentCreateModal" tabindex="-1" aria-labelledby="incidentCreateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="incidentCreateModalLabel">Create New Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="createIncidentForm">
                    <div class="modal-body">

                        {{-- <div class="mb-3">
                            <label for="shift_id" class="form-label">Shift</label>
                            <select class="form-select" id="shift_id" name="shift_id" required>
                                <option value="">-- Select Shift --</option>
                                <!-- dynamically fill with shifts -->
                            </select>
                        </div> --}}

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="theft">Theft</option>
                                <option value="assault">Assault</option>
                                <option value="fire">Fire</option>
                                <option value="medical">Medical</option>
                                <option value="property_damage">Property Damage</option>
                                <option value="suspicious_activity">Suspicious Activity</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity" required>
                                <option value="">Select Severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="location[latitude]">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="location[longitude]">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="location[address]">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="police_notified" name="police_notified">
                            <label class="form-check-label" for="police_notified">Police Notified</label>
                        </div>

                        <div class="mb-3">
                            <label for="police_reference" class="form-label">Police Reference</label>
                            <input type="text" class="form-control" id="police_reference" name="police_reference">
                        </div>

                        <div class="mb-3">
                            <label for="immediate_action_taken" class="form-label">Immediate Action Taken</label>
                            <textarea class="form-control" id="immediate_action_taken" name="immediate_action_taken" rows="2"></textarea>
                        </div>

                    </div><!-- modal-body -->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Incident</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Confirm Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to approve this incident report?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="approveConfirmBtn">Yes, Approve</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Confirm Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to reject this incident report?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="rejectConfirmBtn">Yes, Reject</button>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('scripts')
    <script>
        let selectedIncidentId = null;

        function renderStatusBadge(status) {
            let colorClass = 'secondary'; // default gray
            switch (status) {
                case 'draft':
                    colorClass = 'warning';
                    break;
                case 'under_review':
                    colorClass = 'info';
                    break;
                case 'approved':
                    colorClass = 'success';
                    break;
                case 'rejected':
                    colorClass = 'danger';
                    break;
            }
            return `<span class="badge bg-${colorClass}">${status.replace('_', ' ').toUpperCase()}</span>`;
        }

        // SHOW INCIDENT
        function showIncident(id) {
            $.ajax({
                url: '/incidents/' + id,
                type: 'GET',
                success: function(res) {
                    let html = `
        <p><strong>Title:</strong> ${res.title}</p>
        <p><strong>Category:</strong> ${res.category}</p>
        <p><strong>Severity:</strong> ${res.severity}</p>
        <p><strong>Description:</strong> ${res.description}</p>
        <p><strong>Location:</strong> ${res.formatted_address ?? 'N/A'}</p>
        <p><strong>Police Notified:</strong> ${res.police_notified ? 'Yes' : 'No'}</p>
        <p><strong>Status:</strong> ${renderStatusBadge(res.status)}</p>
        <p><strong>Files:</strong></p>
        <ul>
            ${res.media.map(file => `<li><a href="${file.file_url}" target="_blank">${file.file_url.split('/').pop()}</a></li>`).join('')}
        </ul>
    `;
                    $('#incidentModalBody').html(html);
                    $('#incidentModal').modal('show');
                },
                error: function(err) {
                    alert('Unable to fetch incident details.');
                }
            });
        }
        // EDIT INCIDENT
        function editIncident(id) {
            $.ajax({
                url: '/incidents/' + id + '/edit',
                type: 'GET',
                success: function(res) {
                    $('#incident_id').val(res.id);
                    $('#title').val(res.title);
                    $('#category').val(res.category);
                    $('#severity').val(res.severity);
                    $('#description').val(res.description);
                    $('#police_notified').prop('checked', res.police_notified);
                    $('#status').val(res.status); // populate status select

                    $('#location_preview').text(res.formatted_address ?? 'N/A');
                    let filesHtml = res.media.map(file =>
                        `<li><a href="/${file.file_url}" target="_blank">${file.file_url.split('/').pop()}</a></li>`
                    ).join('');
                    $('#files_preview').html(filesHtml);

                    $('#incidentEditModal').modal('show');
                },
                error: function() {
                    alert('Unable to load incident for editing.');
                }
            });
        }

        $('#incidentEditForm').on('submit', function(e) {
            e.preventDefault();

            if (!$('#police_notified').is(':checked')) {
                $('#police_notified').val(0);
            } else {
                $('#police_notified').val(1);
            }

            let id = $('#incident_id').val();
            let formData = $(this).serialize();

            $.ajax({
                url: '/incidents/' + id,
                type: 'PUT',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    $('#incidentEditModal').modal('hide');
                    toast_success(res.message);
                    // Optionally refresh incident list
                    $('#incident_reports-table').DataTable().ajax.reload(); // reload DataTable

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

        // DELETE INCIDENT
        function deleteIncident(id) {
            selectedIncidentId = id;
            if (confirm('Are you sure you want to delete this incident?')) {
                $.ajax({
                    url: `/incidents/${id}`, // Replace with your delete route
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // CSRF token
                    },
                    success: function(response) {
                        toast_success('Incident deleted successfully!');
                        $('#incident_reports-table').DataTable().ajax.reload(); // reload DataTable
                    },
                    error: function(xhr) {
                        alert('Failed to delete incident.');
                    }
                });
            }
        }

        // OPTIONAL: Close modal programmatically
        function closeIncidentModal() {
            $('#incidentModal').modal('hide');
        }
        // Bulk delete button
        $('#bulkDeleteBtn').on('click', function() {
            const selected = $('.dT-row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                toast_danger('Please select at least one incident to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected incidents?')) return;

            $.ajax({
                url: '{{ route('incidents.bulkdelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected incidents deleted successfully!');
                    $('#incident_reports-table').DataTable().ajax.reload(); // reload DataTable

                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
                }
            });
        });

        $('#createIncidentForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            // Ensure checkbox always sends 0 or 1
            let policeNotified = $('#police_notified').is(':checked') ? 1 : 0;
            formData.set('police_notified', policeNotified); // <-- add this

            $.ajax({
                url: '{{ route('incidents.store') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    let modalEl = document.getElementById('incidentCreateModal');
                    let modal = bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        modal = new bootstrap.Modal(modalEl);
                    }
                    modal.hide();

                    $('#createIncidentForm')[0].reset();
                    toastr.success(response.message);
                    $('#incident_reports-table').DataTable().ajax.reload();
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

        $(function() {
            let selectedIncidentId = null;

            // Delegated handlers for dynamic content
            $(document).on('click', '.btn-approve', function() {
                selectedIncidentId = $(this).data('id');
                new bootstrap.Modal(document.getElementById('approveModal')).show();
            });

            $(document).on('click', '.btn-reject', function() {
                selectedIncidentId = $(this).data('id');
                new bootstrap.Modal(document.getElementById('rejectModal')).show();
            });

            $('#approveConfirmBtn').on('click', function() {
                if (!selectedIncidentId) {
                    toastr.error('No incident selected');
                    return;
                }
                updateIncidentStatus(selectedIncidentId, 'approved');
            });

            $('#rejectConfirmBtn').on('click', function() {
                if (!selectedIncidentId) {
                    toastr.error('No incident selected');
                    return;
                }
                updateIncidentStatus(selectedIncidentId, 'under_review'); // or 'rejected'
            });

            function updateIncidentStatus(id, status) {
                $.ajax({
                    url: `/incidents/${id}/status`,
                    type: 'POST',
                    data: {
                        status: status,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        toastr.success(response.message);
                        $('#incident_reports-table').DataTable().ajax.reload();
                        bootstrap.Modal.getInstance(document.getElementById('approveModal'))?.hide();
                        bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
                        selectedIncidentId = null;
                    },
                    error: function(xhr) {
                        toastr.error('Failed to update status');
                        console.error(xhr.responseText);
                    }
                });
            }
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
