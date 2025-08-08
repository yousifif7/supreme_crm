@extends('layouts.app')
@section('title', 'CRM - Client')
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

    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>

        let selectedId = null;

        function deleteClient(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteclient/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');

                        toast_success('Client Deleted Successfully!');
                        reloadDatatable('#clients-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
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
