@extends('layouts.app')
@section('title', brand_title('Shifts'))
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper security_board">
        <div class="content">
            <div class="alert-box-container"></div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {!! session('warning') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Shifts</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary me-2" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn d-inline-flex align-items-center"
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
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_shift"
                        class="add_btn btn btn-white d-inline-flex align-items-center">
                        <i class="ti ti-plus me-2"></i>Shift
                    </a>


                    <!-- Search -->
                    <button type="button" class="add_btn btn btn-white" data-bs-toggle="modal"
                        data-bs-target="#filterModal">
                        Filter
                    </button>
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">
                        <!-- /Search -->
                    </div>
                    <div class="d-inline-block ms-2 d-flex align-items-center">
                        <select id="shiftStatus" class="form-select form-select-sm">
                            <option value="" {{ request('shiftStatus') === null || request('shiftStatus') === '' ? 'selected' : '' }}>
                                All Statuses
                            </option>
                            @foreach(\App\Models\ShiftDate::getStatusLabels() as $key => $label)
                                <option value="{{ $key }}" {{ (string)request('shiftStatus') === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
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

            <!-- /Breadcrumb -->
        </div>

        @include('security_boards.shiftmodal')
        @include('security_boards.partials.edit-shift-modal')
        

        <!-- Edit Shift -->

        <!-- Import modal -->
        <div class="modal fade" id="import_modal">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Import Shifts</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('shifts.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0 ">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-info">
                                                <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:</h6>
                                                <ul class="mb-0 small">
                                                    <li>Headers should be in Row 1 starting from Column A</li>
                                                    <li>Data should start from Row 2, Column A onwards</li>
                                                    <li><strong>Required:</strong> Date, Client, Site, Start, End</li>
                                                    <li><strong>Optional:</strong> #, Day, Officer, Phone, Lost Time, Hours,
                                                        Comments</li>
                                                    <li><strong>Date format:</strong> 01-May-2025, 2025-05-01, 01/05/2025
                                                    </li>
                                                    <li><strong>Time format:</strong> 06:00, 18:00, 6:00, 18:00</li>
                                                    <li>Client and Site names must exist in the database</li>
                                                    <li>Officer names are matched against employee records (first name, last
                                                        name, or full name)</li>
                                                    <li>Hours will be calculated automatically if not provided</li>
                                                    <li>If Officer is assigned, SIA license expiry and overlapping shifts
                                                        will be checked</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="d-flex gap-2">
                                                <input type="file" name="file" class="form-control" required
                                                    accept=".xlsx,.xls,.csv">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('shifts.export.excel', ['template' => 1]) }}"
                                                class="btn btn-outline-primary w-100">
                                                <i class="ti ti-download"></i> Download Template
                                            </a>
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

        <!-- Delete Modal -->
        <div class="modal fade" id="delete_modal">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 20%; min-width: 20%;">
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

        <!-- Add Shift Success -->
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
                                        <a href="{{ url('shifts') }}" class="btn btn-dark w-100">Back to List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assign Shift Modal -->
        @include('security_boards.assign-shift-modal')
        @include('security_boards.shifts.shift_filter_options')

    </div>
    <!-- /Page Wrapper -->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            const FILTER_KEYS = ['staff', 'client_id', 'site', 'subcontractor', 'status', 'from_shift', 'to_shift'];

            function getFilterValues() {
                const values = {};
                FILTER_KEYS.forEach(function(key) {
                    values[key] = $('#shiftFilterForm [name="' + key + '"]').val() || '';
                });
                return values;
            }

            function restoreFromUrl() {
                const params = new URLSearchParams(window.location.search);

                FILTER_KEYS.forEach(function(key) {
                    const value = params.get(key);
                    if (value !== null) {
                        const $field = $('#shiftFilterForm [name="' + key + '"]');
                        $field.val(value);
                    }
                });

                const statusFromUrl = params.get('shiftStatus');
                if (statusFromUrl !== null) {
                    $('#shiftStatus').val(statusFromUrl);
                }

                const searchFromUrl = params.get('search');
                if (searchFromUrl !== null) {
                    $('.search_box').val(searchFromUrl);
                }

                // Refresh Select2 UIs after value restore
                $('#shiftFilterForm .staff-select-filter, #shiftFilterForm .client-select-filter, #shiftFilterForm .site-select-filter, #shiftFilterForm .subcontractor-select-filter').trigger('change.select2');
            }

            function syncUrlWithCurrentFilters() {
                const url = new URL(window.location.href);
                const params = url.searchParams;

                const shiftStatus = $('#shiftStatus').val();
                const search = $('.search_box').val();
                const filterValues = getFilterValues();

                if (shiftStatus) params.set('shiftStatus', shiftStatus);
                else params.delete('shiftStatus');

                if (search) params.set('search', search);
                else params.delete('search');

                FILTER_KEYS.forEach(function(key) {
                    if (filterValues[key]) params.set(key, filterValues[key]);
                    else params.delete(key);
                });

                window.history.replaceState({}, '', url.toString());
            }

            function drawShiftsTable() {
                try {
                    const table = $('#shifts-table').DataTable();
                    table.draw(false);
                } catch (err) {
                    console.error('Failed to redraw shifts table', err);
                }
            }

            function buildShiftExportUrl(baseHref) {
                const exportUrl = new URL(baseHref, window.location.origin);
                const currentParams = new URLSearchParams(window.location.search);

                // Keep URL/query-state filters in export request.
                currentParams.forEach(function(value, key) {
                    if (value !== null && value !== '') {
                        exportUrl.searchParams.set(key, value);
                    }
                });

                // Ensure latest in-page controls are reflected even before next table draw.
                const shiftStatus = $('#shiftStatus').val();
                const search = $('.search_box').val();
                const filterValues = getFilterValues();

                if (shiftStatus) exportUrl.searchParams.set('shiftStatus', shiftStatus);
                else exportUrl.searchParams.delete('shiftStatus');

                if (search) exportUrl.searchParams.set('search', search);
                else exportUrl.searchParams.delete('search');

                FILTER_KEYS.forEach(function(key) {
                    if (filterValues[key]) exportUrl.searchParams.set(key, filterValues[key]);
                    else exportUrl.searchParams.delete(key);
                });

                // If user selected rows, prioritize selected IDs for export.
                exportUrl.searchParams.delete('ids');
                exportUrl.searchParams.delete('ids[]');

                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                // Avoid oversized query strings when too many rows are selected.
                if (selected.length > 0 && selected.length <= 300) {
                    selected.forEach(function(id) {
                        exportUrl.searchParams.append('ids[]', id);
                    });
                }

                return exportUrl.toString();
            }

            restoreFromUrl();

            $('.staff-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            $('.client-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            $('.site-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })
            $('.subcontractor-select-filter').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterModal'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            })


            // Auto-apply when the select changes
            $('#shiftStatus').on('change', function() {
                syncUrlWithCurrentFilters();
                drawShiftsTable();
            });

            let searchDebounce = null;
            $('.search_box').on('input', function() {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function() {
                    syncUrlWithCurrentFilters();
                    drawShiftsTable();
                }, 300);
            });

            $(document).on('preXhr.dt', '#shifts-table', function(e, settings, data) {
                try {
                    data.shift_status = $('#shiftStatus').val();

                    if (data.search && typeof data.search === 'object') {
                        data.search.value = $('.search_box').val() || '';
                    }

                    const filterData = $('#shiftFilterForm').serializeArray();
                    filterData.forEach(function(item) {
                        data[item.name] = item.value;
                    });
                } catch (err) {
                    // ignore
                }
            });

            $('#shiftFilterForm').on('submit', function(e) {
                e.preventDefault();
                syncUrlWithCurrentFilters();
                drawShiftsTable();
                closeBsModal('#filterModal');
            });

            $(document).off('click.shiftExport', 'a.export-pdf, a.export-excel').on('click.shiftExport', 'a.export-pdf, a.export-excel', function(e) {
                e.preventDefault();

                try {
                    const destination = buildShiftExportUrl($(this).attr('href'));
                    window.location = destination;
                } catch (err) {
                    console.error('Unable to build shift export URL', err);
                    window.location = $(this).attr('href');
                }
            });

            $('#resetShiftFilters').on('click', function() {
                FILTER_KEYS.forEach(function(key) {
                    const $field = $('#shiftFilterForm [name="' + key + '"]');
                    $field.val('');
                });

                $('#shiftFilterForm .staff-select-filter, #shiftFilterForm .client-select-filter, #shiftFilterForm .site-select-filter, #shiftFilterForm .subcontractor-select-filter').trigger('change.select2');
                $('#shiftStatus').val('');
                $('.search_box').val('');

                syncUrlWithCurrentFilters();

                try {
                    const table = $('#shifts-table').DataTable();
                    table.search('');
                } catch (err) {
                    console.error('Failed to clear table search during reset', err);
                }

                drawShiftsTable();
            });

            // Apply restored search term once DataTable is initialized
            $(document).on('init.dt', '#shifts-table', function() {
                const term = $('.search_box').val() || '';
                if (term) {
                    try {
                        $('#shifts-table').DataTable().search(term).draw(false);
                    } catch (err) {
                        console.error('Failed to apply initial search term', err);
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            function initDaySelector(shiftGroup) {
                const dayBoxes = shiftGroup.querySelectorAll('.day-box');
                const hiddenInput = shiftGroup.querySelector('input[name="days[]"]');

                dayBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        box.classList.toggle('selected');
                        const selected = Array.from(shiftGroup.querySelectorAll(
                                '.day-box.selected'))
                            .map(el => el.getAttribute('data-day'));

                        hiddenInput.value = selected.join(',');
                    });
                });
            }

            function bindEvents() {
                // Add Shift Button
                document.querySelectorAll('.addShiftGroup').forEach(btn => {
                    btn.onclick = function() {
                        const wrapper = document.querySelector('.shift-wrapper');
                        const lastGroup = wrapper.querySelector('.shift-group:last-of-type');
                        const clone = lastGroup.cloneNode(true);

                        // Reset values in clone
                        clone.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') {
                                const checkboxName = el.getAttribute('name') || '';
                                const defaultCheckedNames = [
                                    'restrict_start_time[]',
                                    'enforce_picture_check[]',
                                    'restrict_location_check[]',
                                    'auto_checkcall_enabled[]',
                                    'auto_patrol_enabled[]',
                                    'require_media_upload[]'
                                ];
                                el.checked = defaultCheckedNames.includes(checkboxName);
                            } else {
                                el.value = '';
                            }
                        });

                        // Reset day selection
                        clone.querySelectorAll('.day-box').forEach(box => box.classList.remove(
                            'selected'));
                        clone.querySelector('input[name="days[]"]').value = '';

                        wrapper.appendChild(clone);

                        // Re-init new shift group logic
                        initDaySelector(clone);
                        bindEvents();
                    };
                });

                // Remove Shift Button
                document.querySelectorAll('.remove-shift').forEach(btn => {
                    btn.onclick = function() {
                        const shiftGroups = document.querySelectorAll('.shift-wrapper .shift-group');
                        if (shiftGroups.length > 1) {
                            btn.closest('.shift-group').remove();
                        } else {
                            toast_danger('You must have at least one shift.');
                        }
                    };
                });
            }

            // Initialize for first shift-group
            document.querySelectorAll('.shift-group').forEach(group => initDaySelector(group));

            // Initial binding
            bindEvents();
        });
    </script>
    <script>
        $(document).ready(function() {
            $(".select2_modal").select2({
                dropdownParent: $("#edit_shift")
            });

                window.editShift = function(record_id) {
            console.debug('editShift called', record_id);
            $.get(`${baseUrl}/editshift/` + record_id)
                .done(function(data) {
                    console.debug('editShift response', data);
                    const shiftObj = data.shift || data.shiftD || null;
                    if (data && shiftObj) {
                        const $modal = $('#edit_shift');
                        $modal.find('#shift_id').val(record_id);

                        // Populate selects lists if provided
                        if (data.clients) {
                            const $client = $modal.find('#clientSelect');
                            $client.empty().append('<option value="">--choose--</option>');
                            data.clients.forEach(function(c) {
                                $client.append(`<option value="${c.id}">${c.first_name} ${c.last_name}</option>`);
                            });
                            const clientId = (shiftObj.shift && shiftObj.shift.client_id) || shiftObj.client_id || (shiftObj.client && shiftObj.client.id) || null;
                            if (clientId) $client.val(clientId).trigger('change');
                        }

                        if (data.sites) {
                            const $site = $modal.find('#siteSelect');
                            $site.empty().append('<option value="">--choose--</option>');
                            data.sites.forEach(function(s) {
                                $site.append(`<option value="${s.id}">${s.site_name}</option>`);
                            });
                            const siteId = (shiftObj.shift && shiftObj.shift.site_id) || shiftObj.site_id || (shiftObj.site && shiftObj.site.id) || null;
                            if (siteId) $site.val(siteId).trigger('change');
                        }

                        if (data.subcontractors) {
                            const $sub = $modal.find('#subcontractor_id');
                            $sub.empty().append('<option value="">--choose--</option>');
                            data.subcontractors.forEach(function(s) {
                                $sub.append(`<option value="${s.id}">${s.first_name} ${s.last_name}</option>`);
                            });
                            const subId = (shiftObj.shift && shiftObj.shift.subcontractor_id) || shiftObj.subcontractor_id || (shiftObj.subcontractor && shiftObj.subcontractor.id) || null;
                            if (subId) $sub.val(subId).trigger('change');
                        }

                        // Staff
                        const staffVal = shiftObj.staff_id || (shiftObj.staff && shiftObj.staff.id) || null;
                        if (staffVal) {
                            $modal.find('#staff_id').val(staffVal).trigger('change');
                        } else {
                            $modal.find('#staff_id').val('');
                        }

                        // Basic fields
                        // HTML5 <input type="time"> only accepts HH:MM — strip any seconds
                        // coming back from the API (e.g. "07:05:04" → "07:05") so the field
                        // accepts the value and the form can be submitted.
                        const toHHMM = function (t) {
                            if (!t) return '';
                            const m = String(t).match(/^(\d{2}):(\d{2})/);
                            return m ? `${m[1]}:${m[2]}` : '';
                        };

                        $modal.find('#shift_date').val(shiftObj.shift_date || shiftObj.shift_date || '');
                        $modal.find('#start_shift').val(toHHMM(shiftObj.start_time));
                        $modal.find('#end_shift').val(toHHMM(shiftObj.end_time));

                        $modal.find('#guard_rate').val(data.parent_shift.employee_rate);

                        $modal.find('#site_rate').val(data.parent_shift.site_rate);

                        if (typeof shiftObj.absentee_start_time != 'undefined') $modal.find('#book_on').val(toHHMM(shiftObj.absentee_start_time));
                        if (typeof shiftObj.absentee_end_time != 'undefined') $modal.find('#book_off').val(toHHMM(shiftObj.absentee_end_time));
                        $modal.find('#status_id').val(shiftObj.is_assign || shiftObj.status || '');

                        // Initialize select2 for selects inside modal (if not already)
                        try {
                            $modal.find('.select2_client, .select2_site, .StaffSelect').each(function() {
                                if (!$(this).hasClass('select2-hidden-accessible')) {
                                    $(this).select2({ dropdownParent: $modal });
                                }
                            });
                        } catch (e) {
                            // ignore
                        }

                        // Re-apply parent site/employee rates after any async select handlers
                        try {
                            const finalSiteRate = (data && data.parent_shift && typeof data.parent_shift.site_rate !== 'undefined') ? data.parent_shift.site_rate : '';
                            const finalEmployeeRate = (data && data.parent_shift && typeof data.parent_shift.employee_rate !== 'undefined') ? data.parent_shift.employee_rate : '';
                            $modal.find('#site_rate').val(finalSiteRate);
                            $modal.find('.siteRate').val(finalSiteRate);
                            // Only set guard rate from parent when no staff selected
                            const staffSelectedNow = $modal.find('#staff_id').val();
                            if (!staffSelectedNow) {
                                $modal.find('#guard_rate').val(finalEmployeeRate);
                                $modal.find('.staffRate').val(finalEmployeeRate);
                            }
                        } catch (e) {
                            // ignore
                        }

                        // Show modal and re-apply rates on shown to override any async handlers
                        try {
                            const modalEl = document.getElementById('edit_shift');
                            const applyFinalRates = function() {
                                try {
                                    const finalSiteRate = (data && data.parent_shift && typeof data.parent_shift.site_rate !== 'undefined') ? data.parent_shift.site_rate : '';
                                    const finalEmployeeRate = (data && data.parent_shift && typeof data.parent_shift.employee_rate !== 'undefined') ? data.parent_shift.employee_rate : '';
                                    $modal.find('#site_rate').val(finalSiteRate);
                                    $modal.find('.siteRate').val(finalSiteRate);
                                    const staffSelectedNow = $modal.find('#staff_id').val();
                                    if (!staffSelectedNow) {
                                        $modal.find('#guard_rate').val(finalEmployeeRate);
                                        $modal.find('.staffRate').val(finalEmployeeRate);
                                    }
                                } catch (e) { /* ignore */ }
                            };

                            // Snapshot checkbox values here so the closure captures them
                            const _parentSh = data.parent_shift || {};
                            const _shiftObj = shiftObj;
                            const _data     = data;
                            const applyCheckboxes = function() {
                                $modal.find('input[name="restrict_start_time[]"]').prop('checked',    !!(+_parentSh.restrict_start_time    || +_shiftObj.restrict_start_time));
                                $modal.find('input[name="enforce_picture_check[]"]').prop('checked',  !!(+_parentSh.enforce_picture_check  || +_shiftObj.enforce_picture_check));
                                $modal.find('input[name="restrict_location_check[]"]').prop('checked',!!(+_parentSh.restrict_location_check || +_shiftObj.restrict_location_check));
                                $modal.find('input[name="require_media_upload[]"]').prop('checked',   !!(+_shiftObj.require_media));
                                $modal.find('input[name="auto_checkcall_enabled[]"]').prop('checked', !!(_data.check_calls_count > 0)).trigger('change');
                                $modal.find('input[name="auto_patrol_enabled[]"]').prop('checked',    !!(_data.patrols_count > 0)).trigger('change');
                            };

                            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                // re-apply once when modal is shown
                                modalEl.addEventListener('shown.bs.modal', function handler() {
                                    applyFinalRates();
                                    applyCheckboxes();
                                    modalEl.removeEventListener('shown.bs.modal', handler);
                                });
                                modalInstance.show();
                            } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                                $modal.on('shown.bs.modal.once', function() {
                                    applyFinalRates();
                                    $modal.off('shown.bs.modal.once');
                                });
                                $modal.modal('show');
                            } else {
                                console.warn('No modal API available to show #edit_shift');
                            }

                            // Extra safety: re-apply after short delays in case other async handlers run later
                            setTimeout(function() { applyFinalRates(); applyCheckboxes(); }, 150);
                            setTimeout(function() { applyFinalRates(); applyCheckboxes(); }, 500);
                        } catch (err) {
                            console.error('Error showing edit modal', err);
                        }
                    } else {
                        console.warn('editShift: no shift returned for', record_id, data);
                    }
                })
                .fail(function(xhr) {
                    console.error('editShift GET failed', xhr);
                });
        }
    $('#edit_shift-form').on('submit', function(e) {
        e.preventDefault();

        let form = $(this)[0];
        let formData = new FormData(form);

        // Ensure all fields are properly collected
        try {
            // Manually add Select2 values if FormData missed them
            const clientVal = $('#edit_shift #clientSelect').val();
            const siteVal = $('#edit_shift #siteSelect').val();
            const staffVal = $('#edit_shift #staff_id').val();
            
            if (clientVal && !formData.get('client_id')) {
                formData.set('client_id', clientVal);
            }
            if (siteVal && !formData.get('site_id')) {
                formData.set('site_id', siteVal);
            }
            if (staffVal && !formData.get('staff_id')) {
                formData.set('staff_id', staffVal);
            }
            
            // Debug logging
            console.log('Form data being submitted:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        } catch (err) {
            console.error('Error collecting form data', err);
        }
        
        let submitButton = $('#editshift');
        let shiftId = $(this).find('#shift_id').val();

        submitButton.prop('disabled', true).html('Updating...');

        $.ajax({
            url: `${baseUrl}/updateshift/simple/${shiftId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(response) {
                closeBsModal('#edit_shift');
                showToast('Shift updated successfully!', 'success', 5000);
                reloadDatatable('#shifts-table');
                location.reload();
            },
            error: function(xhr) {
                console.error('Update shift error:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    let errors = xhr.responseJSON.errors;
                    console.error('Validation errors:', errors);
                    
                    // Clear previous errors
                    $('.form-error').text('');
                    
                    // Display errors on form
                    $.each(errors, function(field, messages) {
                        const errorEl = $('.error_' + field);
                        if (errorEl.length) {
                            errorEl.text(messages[0]);
                        }
                    });
                    
                    let messages = Object.values(errors).flat();
                    if (messages.length) {
                        showToast(messages[0], 'error', 7000);
                    } else {
                        showToast('Validation failed. Please check the form.', 'error', 5000);
                    }
                } else {
                    showToast('An error occurred. Please try again.', 'error', 5000);
                }
            },
            complete: function() {
                submitButton.prop('disabled', false).html('Update');
            }
        });
    });
    
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = this;
                let formData = new FormData(form);
                let submitButton = $('#saveshift');

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                // Clear previous errors
                $('.form-error').text('');

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content') // Better practice
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        toast_success('Shift Added Successfully');
                        reloadDatatable('#shifts-table');

                        // Optional: Reset form after success
                        form.reset();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });


            // Bulk delete button
            $('#bulkDeleteBtn').on('click', function() {
                const selected = $('.dT-row-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (selected.length === 0) {
                    toast_danger('Please select at least one shift to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected shifts?')) return;

                $.ajax({
                    url: '{{ route('shifts.bulkDelete') }}',
                    type: 'POST',
                    data: {
                        ids: selected,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toast_success('Selected shifts deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function() {
                        toast_danger('Something went wrong during bulk delete.');
                    }
                });
            });
        });

        let selectedId = null;

        function deleteShift(record_id) {
            selectedId = record_id;
            $('#delete_modal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (selectedId !== null) {
                $.ajax({
                    url: `${baseUrl}/deleteshift/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        closeBsModal('#delete_modal');
                        toast_success('Shift deleted successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function(xhr) {
                        closeBsModal('#delete_modal');
                        toast_danger('Something went wrong. Please try again.');
                    }
                });
            }
        });
        // Handle client -> site population for both native selects and Select2-enhanced selects
        $(document).on("change select2:select", "#clientSelect, .select2_client", function(e) {
            // Prefer the event target when it's the original <select>, otherwise use this
            var $target = $(e.target && e.target.nodeName === 'SELECT' ? e.target : this);
            const clientId = $target.val();

            // Determine the shift-group context (may be multiple groups on the page)
            var $shiftGroup = $target.closest('.shift-group');
            if (!$shiftGroup.length) $shiftGroup = $target.parents('.shift-group').first();

            // Find the site select within the same group, falling back to global
            var $siteSelect = $shiftGroup.length ? $shiftGroup.find('#siteSelect') : $target.closest('form').find('#siteSelect');
            if (!$siteSelect || !$siteSelect.length) $siteSelect = $('#siteSelect');

            // Reset options
            $siteSelect.html('<option value="">--choose--</option>');

            if (!clientId) {
                try {
                    $shiftGroup.find('.siteRate').val('');
                } catch (err) {}
                try {
                    $siteSelect.trigger('change');
                } catch (err) {}
                return;
            }

            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    try {
                        $shiftGroup.find('.siteRate').val(data.client.office_rate || '');
                    } catch (err) {}
                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function(index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site.site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }

                    // Notify enhancers (Select2) to refresh their UI if needed
                    try {
                        if ($siteSelect.hasClass('select2')) {
                            $siteSelect.trigger('change.select2');
                        } else {
                            $siteSelect.trigger('change');
                        }
                    } catch (err) { /* ignore */ }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

        // Update staff guard rate when staff selection changes (handles both native and Select2)
        $(document).on("change select2:select", "#StaffSelect, .StaffSelect", function(e) {
            var $target = $(e.target && e.target.nodeName === 'SELECT' ? e.target : this);
            const staffId = $target.val();
            if (!staffId) return;

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    try {
                        $target.parents('.shift-group').find('.staffRate').val(data.employee.guard_rate || '');
                    } catch (err) {}
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', error);
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
