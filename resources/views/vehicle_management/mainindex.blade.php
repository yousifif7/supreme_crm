@extends('layouts.app')
@section('title')
    CRM | Vehicle Managment
@endsection

@section('styles')
    <style>
        /* Tabs container */
        .page-tabs-container .nav-tabs {
            margin-left: 250px;
            /* offset for sidebar */
            border-bottom: 2px solid #dee2e6;
        }

        /* Each tab link */
        .page-tabs-container .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 10px 20px;
            border: none;
            border-radius: 0.5rem 0.5rem 0 0;
            transition: all 0.2s ease-in-out;
        }

        /* Hover effect */
        .page-tabs-container .nav-tabs .nav-link:hover {
            color: #0d6efd;
            /* Bootstrap blue */
            background-color: #f1f5ff;
            /* light blue background */
        }

        /* Active tab */
        .page-tabs-container .nav-tabs .nav-link.active {
            color: #fff !important;
            background-color: #495057 !important;
            /* blue */
            border: none;
            font-weight: 600;
            box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection
@section('contents')
    <div class="container-fluid page-tabs-container" style="padding-top: 50px;">
        <h2>Vehicle Managment</h2>
        <div class="row">
            <div class="col-12">
                <!-- Vehicle Management Tabs -->
                <ul class="nav nav-tabs" id="vehicleTabs" role="tablist" style="padding-top: 70px; margin-left: 250px;">
                    <li class="nav-item">
                        <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">
                            Vehicle Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="compliances-tab" data-bs-toggle="tab" href="#compliances" role="tab">
                            Legal & Compliance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="maintenance-tab" data-bs-toggle="tab" href="#maintenance" role="tab">
                            Service & Maintenance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="roadworthiness-tab" data-bs-toggle="tab" href="#roadworthiness"
                            role="tab">
                            Roadworthiness Checks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents" role="tab">
                            Documentation Uploads
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="alerts-tab" data-bs-toggle="tab" href="#alerts" role="tab">
                            Alerts & Reminders
                        </a>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content mt-3" id="vehicleTabsContent" style="min-height: calc(100vh - 200px);">
                    <div class="tab-pane fade show active" id="details" role="tabpanel"></div>
                    <div class="tab-pane fade" id="compliances" role="tabpanel"></div>
                    <div class="tab-pane fade" id="maintenance" role="tabpanel"></div>
                    <div class="tab-pane fade" id="roadworthiness" role="tabpanel"></div>
                    <div class="tab-pane fade" id="documents" role="tabpanel"></div>
                    <div class="tab-pane fade" id="alerts" role="tabpanel"></div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let selectedId = null;
        $(document).ready(function() {
            function loadTab(tabId, url) {
                if ($(tabId).is(':empty')) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(data) {
                            $(tabId).html(data); // Yajra scripts are included in `data`
                        },
                        error: function(xhr, status, error) {
                            console.error('Failed to load tab content:', error);
                            $(tabId).html(
                                '<div class="alert alert-danger">Failed to load content.</div>');
                        }
                    });
                }
            }

            // Load first tab on page load
            loadTab('#details', "{{ route('vehicle_details') }}");

            // Load other tabs on click
            $('#vehicleTabs a').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr("href");

                switch (target) {
                    case '#details':
                        loadTab(target, "{{ route('vehicle_details') }}");
                        break;
                    case '#compliances':
                        loadTab(target, "{{ route('complainces') }}");
                        break;
                    case '#maintenance':
                        loadTab(target, "{{ route('maintenances') }}");
                        break;
                    case '#roadworthiness':
                        loadTab(target, "{{ route('checks') }}");
                        break;
                    case '#documents':
                        loadTab(target, "{{ route('documents') }}");
                        break;
                    case '#alerts':
                        loadTab(target, "{{ route('reminders') }}");
                        break;
                }
            });

            // Adjust columns when switching tabs
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            });
        });
    </script>
@endsection

@section('styles')
    <style>
        /* Make sure DataTables inside tabs stretch full width */
        .tab-pane table.dataTable {
            width: 100% !important;
        }

        /* Optional: style tabs a little nicer */
        #vehicleTabs .nav-link {
            font-weight: 500;
            padding: 10px 18px;
        }
    </style>
@endsection
