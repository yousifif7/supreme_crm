<!-- Filter Header -->
<div class="filters">
    <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-1">
        <div class="left">
            <button onclick="window.location='{{ url('scheduling') }}'"
                class="{{ request()->is('scheduling*') ? 'active' : '' }}">Complete
                Rota</button>
            <button onclick="window.location='{{ url('worker_calendar') }}'"
                class="{{ request()->is('worker_calendar*') ? 'active' : '' }}">Security Staff Calendar</button>
            <button onclick="window.location='{{ url('site_calendar') }}'"
                class="{{ request()->is('site_calendar*') ? 'active' : '' }}">Site Calendar</button>
            <button onclick="window.location='{{ url('today_rota') }}'"
                class="{{ request()->is('today_rota*') ? 'active' : '' }}">Today's Rota</button>

        </div>

        <div class="right">
            <div class="status-summary">
                <div onclick="window.location='{{ url('sites') }}'" class="active-sites">&#9679; Active Sites
                    ({{ $sites->count() }})</div>
                <div onclick="window.location='{{ url('employees') }}'" class="active-workers">&#9679; Active
                    Security Staff ({{ $staffs->count() }})</div>

            </div>
        </div>
    </div>
    <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-1">
        <div class="left mt-4">
            <button class="refresh_btn" onclick="window.location.reload()">
                <i class="ti ti-reload"></i>Refresh
            </button>
        </div>

        <div class="right mt-4">
            @yield('filter')
            @if (Request::is('scheduling*'))
                <button id="editSelectedBtn" class="btn add-btn btn-success" hidden style="background: gray;">Multiple
                    Edit</button>
                <button id="deleteSelectedBtn" class="btn btn-danger" style="background:red;" hidden>Delete Selected</button>
                <button id="enableSelectBtn" class="btn add-btn btn-success" name="Multi_Select"
                    style="background: gray;">Multi Select</button>
                <button id="toggle-subcontractors-all" class="btn" style="background-color:gray; color:white;">
                    Show Subcontractors
                </button>
                <button type="button" class="add_btn btn btn-white" data-bs-toggle="modal"
                    data-bs-target="#filterModal">
                    Filter
                </button>

                <a href="#" data-bs-toggle="modal" data-bs-target="#add_shift" class="add_btn btn btn-white">
                    <i class="ti ti-plus me-0"></i> Add Shift
                </a>
            @endif
            @if (!Request::is('scheduling*'))
                <div class="input-group input-group-flat d-inline-flex me-1">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" id="calendarSearch" class="search_box" placeholder="Search...">
                    <!-- /Search -->
                </div>
            @endif

            <div class="dropdown">
                <a href="javascript:void(0);"
                    class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                    data-bs-toggle="dropdown">
                    <i class="ti ti-file-export me-1"></i>Export
                </a>
                <ul class="dropdown-menu  dropdown-menu-start p-3">
                    <li>
                        <a href="{{ route('shifts.export.pdf') }}" class="dropdown-item rounded-1 export-pdf"><i
                                class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                    </li>
                    <li>
                        <a href="{{ route('shifts.export.excel') }}" class="dropdown-item rounded-1 export-excel"><i
                                class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                    </li>
                </ul>
                <script>
                    (function() {
                        function buildExportUrl(baseHref) {
                            var exportUrl = new URL(baseHref, window.location.origin);
                            var currentParams = new URLSearchParams(window.location.search);

                            // Keep current URL filters/state in export request.
                            currentParams.forEach(function(value, key) {
                                if (value !== null && value !== '') {
                                    exportUrl.searchParams.set(key, value);
                                }
                            });

                            // On scheduling pages, gantt search is stored as ganttSearch; map to backend search key.
                            var ganttSearch = $('#ganttSearch').val();
                            if (ganttSearch && String(ganttSearch).trim() !== '') {
                                exportUrl.searchParams.set('ganttSearch', String(ganttSearch).trim());
                                exportUrl.searchParams.set('search', String(ganttSearch).trim());
                            }

                            // Keep export URL compact to avoid web-server URI length limits.
                            // Backend will export using the active filters/search params.
                            exportUrl.searchParams.delete('ids');
                            exportUrl.searchParams.delete('ids[]');

                            return exportUrl.toString();
                        }

                        $(document).off('click.shiftFilterExport', 'a.export-pdf, a.export-excel').on('click.shiftFilterExport', 'a.export-pdf, a.export-excel', function(e) {
                            try {
                                // On shifts index page, let the page-specific handler build the URL.
                                if ($('#shifts-table').length > 0) {
                                    return;
                                }

                                e.preventDefault();
                                window.location = buildExportUrl($(this).attr('href'));
                            } catch (err) {
                                // On any error, just follow the original link
                                return;
                            }
                        });
                    })();
                </script>
            </div>
        </div>
    </div>

</div>
