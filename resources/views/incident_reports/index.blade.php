@extends('layouts.app')
@section('title', 'CRM - Incident Reports')
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
      <div class="modal-header"><h5 class="modal-title">Delete Incident</h5></div>
      <div class="modal-body">Are you sure you want to delete this incident?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="confirmDeleteIncidentBtn" type="button" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>

let selectedIncidentId = null;

// SHOW INCIDENT
function showIncident(id) {
    $.ajax({
        url: '/incidents/' + id, // make sure your route exists
        type: 'GET',
        success: function(res) {
            let html = `
                <p><strong>Title:</strong> ${res.title}</p>
                <p><strong>Category:</strong> ${res.category}</p>
                <p><strong>Severity:</strong> ${res.severity}</p>
                <p><strong>Description:</strong> ${res.description}</p>
                <p><strong>Location:</strong> ${res.location.address}</p>
                <p><strong>Police Notified:</strong> ${res.police_notified ? 'Yes' : 'No'}</p>
                <p><strong>Files:</strong></p>
                <ul>
                    ${res.media.map(file => `<li><a href="/${file.file_url}" target="_blank">${file.file_url.split('/').pop()}</a></li>`).join('')}
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
    // Example: open edit page in new tab or same window
    window.location.href = `/incidents/${id}/edit`; // Replace with your edit route
}

// DELETE INCIDENT
function deleteIncident(id) {
    selectedIncidentId = id;
    if(confirm('Are you sure you want to delete this incident?')) {
        $.ajax({
            url: `/incidents/${id}`, // Replace with your delete route
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}' // CSRF token
            },
            success: function(response) {
                alert('Incident deleted successfully!');
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
                toast_danger('Please select at least one client to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected clients?')) return;

            $.ajax({
                url: '{{ route('clients.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toast_success('Selected clients deleted successfully!');
                    reloadDatatable('#clients-table');
                },
                error: function() {
                    toast_danger('Something went wrong during bulk delete.');
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
