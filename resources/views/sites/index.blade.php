@extends('layouts.app')
@section('title', 'SPL Connect - Sites')
@section('styles')
    <style>
        .select2-container--default .select2-selection--single {
            height: 40px;
            /* adjust as needed */
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
        }
        /* Ensure Select2 dropdowns appear above Bootstrap modals */
        .select2-container--open .select2-dropdown {
            z-index: 3000 !important;
        }

        /* Tame highlighted option styles in case of theme conflicts */
        .select2-container--default .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
    </style>
@endsection
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Sites</h2>

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
                                <a href="{{ route('sites.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('sites.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#import_modal">Import</button>
                <div class="me-2 mb-2 filter_area">

                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_site"
                        class=" add_btn btn btn-white d-inline-flex align-items-center"">
                        <i class="ti ti-plus me-2"></i>Site
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
    <!-- /Page Wrapper -->
    <!-- Add Client -->
    @include('sites.create')
    <!-- /Add Client -->

    <!-- Edit Client -->
    @include('sites.edit')


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
                    <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.</p>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Import Sites</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{ route('sites.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                            tabindex="0">
                            <div class="modal-body pb-0 ">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:</h6>
                                            <ul class="mb-0 small">
                                                <li>Row 1 and Column A should be left empty</li>
                                                <li>Row 2 starting from Column B should contain headers</li>
                                                <li>Data should start from Row 3, Column B onwards</li>
                                                <li><strong>Required:</strong> Site Name</li>
                                                <li><strong>Optional:</strong> Client Name, Address, Site Code, Post Code,
                                                    Guard Names, Contact Number, Contact Person, Note, Start Time, End Time,
                                                    Break Time, Guard Rate, Office Rate, Billable Rate, Payable Rate</li>
                                                <li>If Client Name is provided, it must exist in the clients database</li>
                                                <li>The system will automatically find the client and assign its ID when
                                                    Client Name is provided</li>
                                                <li>Time fields should be in HH:MM format (e.g., 08:00, 18:30)</li>
                                                <li>Rate fields should be numeric values</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="d-flex gap-2">
                                            <input type="file" name="import_file" class="form-control" required
                                                accept=".xlsx,.xls,.csv">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="{{ route('sites.export.excel', ['template' => 1]) }}"
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
    <!-- Logs Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Site Logs Detail
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
    <!-- View Site Detail Modal -->
    <div class="modal fade" id="viewSiteDetailModal" tabindex="-1" aria-labelledby="siteDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="siteDetailLabel">
                        Site <span id="site_name_heading" class="fw-bold"></span> Detail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Site Name</th>
                                <td id="site_name_detail"></td>
                            </tr>
                            <tr>
                                <th>Guard Names</th>
                                <td id="guard_names_detail"></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td id="address_detail"></td>
                            </tr>
                            <tr>
                                <th>Google Address</th>
                                <td id="plus_code_detail"></td>
                            </tr>
                            <tr>
                                <th>Post Code</th>
                                <td id="post_code_detail"></td>
                            </tr>
                            <tr>
                                <th>Radius (meters)</th>
                                <td id="radius_detail"></td>
                            </tr>
                            <tr>
                                <th>Site Code</th>
                                <td id="site_code_detail"></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td id="contact_number_detail"></td>
                            </tr>
                            <tr>
                                <th>Contact Person</th>
                                <td id="contact_person_detail"></td>
                            </tr>
                            <tr>
                                <th>Note</th>
                                <td id="note_detail"></td>
                            </tr>
                            <tr>
                                <th>Start Time</th>
                                <td id="start_time_detail"></td>
                            </tr>
                            <tr>
                                <th>End Time</th>
                                <td id="end_time_detail"></td>
                            </tr>
                            <tr>
                                <th>Break Time</th>
                                <td id="break_time_detail"></td>
                            </tr>
                            <tr>
                                <th>Guard Rate</th>
                                <td id="guard_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Office Rate</th>
                                <td id="office_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Billable Rate</th>
                                <td id="billable_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Payable Rate</th>
                                <td id="payable_rate_detail"></td>
                            </tr>
                            <tr>
                                <th>Manager 1</th>
                                <td id="manager_1_detail"></td>
                            </tr>
                            <tr>
                                <th>Manager 2</th>
                                <td id="manager_2_detail"></td>
                            </tr>
                            <tr>
                                <th>QR code status</th>
                                <td id="has_qr"></td>
                            </tr>
                            <tr>
                                <th>NFC Tags</th>
                                <td id="nfc_tags_detail"></td>
                            </tr>
                            <tr>
                                <th>QR Code</th>
                                <td id="qr_image_detail"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h6 class="fw-bold mb-2">Checkpoints</h6>
                <div id="checkpoints_detail">
                    <p class="text-muted">Loading checkpoints...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
                

@endsection
@section('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        
    document.addEventListener("DOMContentLoaded", function() {
        // Apply Flatpickr to all inputs with class .time-input
        flatpickr("input.time-input", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i", // Save as 24h format
            time_24hr: true,
            minuteIncrement: 5,
            allowInput: true
        });
        
        $('.staff-select2').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '220px',
                dropdownParent: $('#edit_site .modal-content'), // append inside modal content
                minimumResultsForSearch: 0 // force search bar for single select
            })
        $('.create-staff-select2').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '220px',
                dropdownParent: $('#add_site .modal-content'), // append inside modal content
                minimumResultsForSearch: 0 // force search bar for single select
            })
    });

        // Delegated handlers for site actions (prevents inline onclick reference errors)
        document.addEventListener('click', function(e) {
            const view = e.target.closest('.site-view');
            if (view) {
                e.preventDefault();
                const id = view.dataset.id;
                if (window.viewSiteDetail) return window.viewSiteDetail(id);
            }

            const edit = e.target.closest('.site-edit');
            if (edit) {
                e.preventDefault();
                const id = edit.dataset.id;
                if (window.editSite) return window.editSite(id);
            }

            const del = e.target.closest('.site-delete');
            if (del) {
                e.preventDefault();
                const id = del.dataset.id;
                return deleteSite(id);
            }
        });

        // Backwards-compatible wrapper: some templates call initSiteMap()
        // but our map initializer is named initEditMap(). Provide a thin
        // wrapper that delegates to initEditMap if available and avoids
        // a ReferenceError if called before initEditMap is defined.
        function initSiteMap() {
            var args = Array.prototype.slice.call(arguments);
            // Prefer create map initializer if present
            if (typeof initCreateMap === 'function') {
                return initCreateMap.apply(this, args);
            }
            if (typeof initEditMap === 'function') {
                return initEditMap.apply(this, args);
            }
            // If neither is defined yet (script ordering), try again shortly.
            setTimeout(function() {
                if (typeof initCreateMap === 'function') {
                    initCreateMap.apply(this, args);
                } else if (typeof initEditMap === 'function') {
                    initEditMap.apply(this, args);
                }
            }, 50);
        }


        $(document).ready(function() {
            $('.select2_client').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#add_site'), // make sure this matches your modal ID
                minimumResultsForSearch: 0 // force search bar for single select
            });
        });

        $(document).ready(function() {

            $(document).on("change", "#clientSelect", function() {
                var $this = $(this);
                const clientId = $(this).val();

                if (!clientId) return;

                $.ajax({
                    url: `${baseUrl}/api/client/${clientId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('.guardRate').val(data.client.guard_rate || '');
                        $('.siteRate').val(data.client.office_rate || '');
                    },
                    error: function(xhr, status, error) {
                        console.error('Fetch error:', error);
                    }
                });
            });

            // $(document).ready(function() {

            //     // Initialize map
            //     initSiteMap();

            //     $('#add_site-form').on('submit', function(e) {
            //         e.preventDefault();
            //         $("[id^='error_']").text('');

            //         const form = this;
            //         const formData = new FormData(form);
            //         const submitButton = $('#savesite');
            //         submitButton.prop('disabled', true).html('Saving...');

            //         if (typeof createSiteMarker !== 'undefined' && createSiteMarker) {
            //             const p = createSiteMarker.getLatLng();
            //             $('#latitude').val(p.lat);
            //             $('#longitude').val(p.lng);
            //         }

            //         $.ajax({
            //             url: $(form).attr('action'),
            //             method: 'POST',
            //             data: formData,
            //             processData: false,
            //             contentType: false,
            //             headers: {
            //                 'X-CSRF-TOKEN': $('input[name="_token"]').val()
            //             },
            //             success: function(response) {
            //                 closeBsModal('#add_site');
            //                 toast_success(response.message ||
            //                     'Site created successfully');
            //                 reloadDatatable('#sites-table');
            //             },
            //             error: function(xhr) {
            //                 if (xhr.status === 422) {
            //                     const errors = xhr.responseJSON.errors;
            //                     $.each(errors, function(key, value) {
            //                         $('#error_' + key).text(value[0]);
            //                     });
            //                 } else {
            //                     toast_danger('An error occurred. Please try again.');
            //                 }
            //             },
            //             complete: function() {
            //                 submitButton.prop('disabled', false).html('Save');
            //             }
            //         });
            //     });

            //     // Optional: re-render map on modal show
            //     $('#add_site').on('shown.bs.modal', function() {
            //         setTimeout(() => {
            //             if (typeof createMap !== 'undefined' && createMap) createMap
            //                 .invalidateSize();
            //         }, 300);
            //     });
            // });

            $('#edit_site-form').on('submit', function(e) {
                e.preventDefault();

                $("[id^='editerror_']").text('');
                // Ensure staff rates are added to the form as array inputs so Laravel
                // validation receives `staff_rates` as an array (staff_rates[0][user_id], ...)
                let form = $(this)[0];
                // remove any previously added dynamic inputs
                $(form).find('.dynamic-staff-rate').remove();
                if (Array.isArray(editSiteStaffRates) && editSiteStaffRates.length) {
                    editSiteStaffRates.forEach(function(r, idx) {
                        const uid = r.user_id ?? (r.user ? r.user.id : '') ?? '';
                        const rate = r.guard_rate ?? '';
                        // append hidden inputs to the form
                        $(form).append($('<input>', { type: 'hidden', name: `staff_rates[${idx}][user_id]`, value: uid }).addClass('dynamic-staff-rate'));
                        $(form).append($('<input>', { type: 'hidden', name: `staff_rates[${idx}][guard_rate]`, value: rate }).addClass('dynamic-staff-rate'));
                    });
                }

                // Ensure holiday rates are added to the form as array inputs
                $(form).find('.dynamic-holiday-rate').remove();
                if (Array.isArray(editSiteHolidayRates) && editSiteHolidayRates.length) {
                    editSiteHolidayRates.forEach(function(r, idx) {
                        const holidayName = r.holiday_name ?? '';
                        const holidayDate = r.holiday_date ?? '';
                        const siteRate = r.site_rate ?? '';
                        const guardRate = r.guard_rate ?? '';
                        $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][holiday_name]`, value: holidayName }).addClass('dynamic-holiday-rate'));
                        $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][holiday_date]`, value: holidayDate }).addClass('dynamic-holiday-rate'));
                        $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][site_rate]`, value: siteRate }).addClass('dynamic-holiday-rate'));
                        $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][guard_rate]`, value: guardRate }).addClass('dynamic-holiday-rate'));
                    });
                }

                let formData = new FormData(form);
                let submitButton = $('#editsite'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let siteId = $('#site_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updatesite/${siteId}`, // OR use Laravel Blade: `{{ url('sites') }}/` + siteId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_site');
                        toast_success('Sites Updated Successfully!');
                        reloadDatatable('#sites-table');
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
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });

        });

        window.editSite = function(record_id) {
            $.get(`${baseUrl}/editsite/` + record_id, function(data) {
                if (data.site) {
                    $('#site_id').val(data.site.id);
                    $('#client_id').val(data.site.client_id).trigger('change');
                    $('#site_name').val(data.site.site_name);
                    $('#site_group').val(data.site.site_group);
                    $('#address').val(data.site.address);
                    $('#post_code').val(data.site.post_code);
                    $('#plus_code').val(data.site.plus_code);
                    $('#site_code').val(data.site.site_code);
                    $('#contact_number').val(data.site.contact_number);
                    $('#note').val(data.site.note);
                    $('#manager_1_id').val(data.site.manager_1_id);
                    $('#manager_2_id').val(data.site.manager_2_id);
                    $('#start_time').val(data.site.start_time);
                    $('#end_time').val(data.site.end_time);
                    $('#break_time').val(data.site.break_time);
                    $('#guard_rate').val(data.site.guard_rate);
                    $('#office_rate').val(data.site.office_rate);
                    $('#billable_rate').val(data.site.billable_rate);
                    $('#payable_rate').val(data.site.payable_rate);
                    // radius
                    $('#radius').val(data.site.radius);

                    // ✅ Handle employee types
                    if (data.employee_types) {
                        data.employee_types.forEach(type => {
                            // Assuming checkbox: name="employee_types[]" value="type.id"
                            $(`input[name="employee_types[]"][value="${type.id}"]`).prop('checked',
                                true);

                            // Guard Rate field: name="employee_guard_rate[type.id]"
                            $(`input[name="employee_guard_rate[${type.id}]"]`).val(type.guard_rate);

                            // Office Rate field: name="employee_office_rate[type.id]"`
                        $(`input[name="employee_office_rate[${type.id}]"]`).val(type.office_rate);
                    });
                    if (data.site.has_qr == 1) {
                        $('#edit_has_qr').prop('checked', true);
                    } else {
                        $('#edit_has_qr').prop('checked', false);
                    }
                    // Toggle generate NFC button availability
                    if (data.site.has_qr == 1) {
                        $('#generateNfcBtn').prop('disabled', false);
                    } else {
                        $('#generateNfcBtn').prop('disabled', true);
                    }
                    let lat = data.site.latitude ?? 51.505;
                    let lng = data.site.longitude ?? -0.09;

                    // ✅ Init map with site + checkpoints
                    initEditMap(lat, lng, data.site.checkpoints || []);
                    // Populate staff select and rates list (use safe select2 re-init)
                    try {
                        const $staffSelect = $('#site_staff_select');
                        // clear existing options
                        $staffSelect.find('option').remove();
                        // add placeholder option
                        $staffSelect.append(new Option('--choose staff--', ''));

                        if (Array.isArray(data.staffs) && data.staffs.length) {
                            data.staffs.forEach(s => {
                                const name = ((s.first_name || '') + ' ' + (s.last_name || '')).trim() || (s.name || '');
                                $staffSelect.append(new Option(name, s.id));
                            });
                        }

                        // destroy existing select2 instance if any, then init so it picks up new options
                        try {
                            if ($staffSelect.hasClass('select2-hidden-accessible')) {
                                $staffSelect.select2('destroy');
                            }
                        } catch (ee) { /* ignore */ }

                        $staffSelect.select2({
                            placeholder: '--choose staff--',
                            allowClear: true,
                            width: 'style',
                            dropdownParent: $('#edit_site .modal-content'),
                            minimumResultsForSearch: 0
                        });

                        // Load existing staff rates
                        editSiteStaffRates = data.staff_rates || [];
                        renderSiteStaffRates();

                        // bind add button
                        $('#add_site_staff_rate').off('click').on('click', function() {
                            const userId = $staffSelect.val();
                            if (!userId) { toast_danger('Please choose a staff'); return; }
                            const userName = $staffSelect.find('option:selected').text();
                            const rate = $('#site_staff_rate_input').val() || null;
                            if (editSiteStaffRates.find(r => parseInt(r.user_id) === parseInt(userId))) {
                                toast_danger('A rate is already set for the selected staff');
                                return;
                            }
                            editSiteStaffRates.push({ user_id: parseInt(userId), guard_rate: rate, name: userName });
                            renderSiteStaffRates();
                            $('#site_staff_rate_input').val('');
                        });
                    } catch (e) { console.error(e); }

                    // Populate holiday select and rates list
                    // Defer select2 initialization until modal is shown to avoid rendering issues
                    $('#edit_site').on('shown.bs.modal', function () {
                        try {
                            const $holidaySelect = $('#site_holiday_select');
                            $holidaySelect.find('option').remove();
                            $holidaySelect.append(new Option('--choose holiday--', ''));

                            ukHolidays = data.uk_holidays || [];
                            const holidayOptions = ukHolidays.map(h => ({ id: `${h.title}|${h.date}`, text: `${h.title} (${h.date})` }));

                            try {
                                if ($holidaySelect.hasClass('select2-hidden-accessible')) {
                                    $holidaySelect.select2('destroy');
                                }
                            } catch (ee) { /* ignore */ }

                            $holidaySelect.select2({
                                placeholder: '--choose holiday--',
                                allowClear: true,
                                width: 'style',
                                dropdownParent: $('#edit_site .modal-content'),
                                minimumResultsForSearch: 0,
                                data: holidayOptions // Explicitly set data
                            });

                            // Load existing holiday rates
                            editSiteHolidayRates = data.site_holiday_rates || [];
                            renderSiteHolidayRates();

                            // bind add button
                            $('#add_site_holiday_rate').off('click').on('click', function() {
                                const selectedHoliday = $holidaySelect.val();
                                if (!selectedHoliday) { toast_danger('Please choose a holiday'); return; }

                                const [holidayName, holidayDate] = selectedHoliday.split('|');
                                const siteRate = $('#site_holiday_site_rate_input').val() || null;
                                const guardRate = $('#site_holiday_guard_rate_input').val() || null;

                                if (editSiteHolidayRates.find(r => r.holiday_date === holidayDate)) {
                                    toast_danger('A rate is already set for this holiday');
                                    return;
                                }

                                editSiteHolidayRates.push({ holiday_name: holidayName, holiday_date: holidayDate, site_rate: siteRate, guard_rate: guardRate });
                                renderSiteHolidayRates();
                                $('#site_holiday_site_rate_input').val('');
                                $('#site_holiday_guard_rate_input').val('');
                                $holidaySelect.val('').trigger('change');
                            });
                        } catch (e) { console.error(e); }
                    }); // End shown.bs.modal
                }

                // Render current site NFC tag in edit modal (single tag per site)
                try {
                    const nfcTag = data.site.nfc_tag || null;
                    let html = '';
                    if (!nfcTag) {
                        html = '<span class="text-muted">No NFC tag generated yet</span>';
                    } else {
                        html = `<div class="d-flex align-items-center gap-2">` +
                                   `<code class="p-2 bg-light border rounded flex-grow-1">${nfcTag}</code>` +
                                   `<button type="button" class="btn btn-sm btn-outline-secondary copy-site-nfc" data-tag="${nfcTag}">Copy</button>` +
                               `</div>`;
                    }
                    $('#edit_nfc_list').html(html);
                    // bind copy button
                    $('#edit_nfc_list .copy-site-nfc').off('click').on('click', function(){
                        const tag = $(this).data('tag');
                        navigator.clipboard?.writeText(tag).then(() => { toast_success('NFC tag copied'); }).catch(()=>{ alert('Failed to copy'); });
                    });
                } catch (e) {}

                // Generate NFC button handler (regenerates single site NFC tag)
                $('#generateNfcBtn').off('click').on('click', function(){
                    const siteId = $('#site_id').val();
                    if (!siteId) return;
                    $(this).prop('disabled', true).text('Regenerating...');
                    $.post(`${baseUrl}/sites/${siteId}/generate-nfc`, {_token: '{{ csrf_token() }}'})
                        .done(function(resp){
                            toast_success(resp.message || 'NFC tag regenerated');
                            const tag = resp.tag || '';
                            let html = '';
                            if (!tag) {
                                html = '<span class="text-muted">No NFC tag generated yet</span>';
                            } else {
                                html = `<div class="d-flex align-items-center gap-2">` +
                                           `<code class="p-2 bg-light border rounded flex-grow-1">${tag}</code>` +
                                           `<button type="button" class="btn btn-sm btn-outline-secondary copy-site-nfc" data-tag="${tag}">Copy</button>` +
                                       `</div>`;
                            }
                            $('#edit_nfc_list').html(html);
                            $('#edit_nfc_list .copy-site-nfc').off('click').on('click', function(){
                                const t = $(this).data('tag');
                                navigator.clipboard?.writeText(t).then(() => { toast_success('NFC tag copied'); }).catch(()=>{ alert('Failed to copy'); });
                            });
                        })
                        .fail(function(xhr){ toast_danger(xhr.responseJSON?.error || 'Failed to regenerate NFC'); })
                        .always(function(){ $('#generateNfcBtn').prop('disabled', false).text('Regenerate NFC tag'); });
                });

                $('#edit_site').modal('show');
                // Ensure map renders correctly after modal is visible
                setTimeout(function() {
                    if (typeof editMap !== 'undefined' && editMap) {
                        editMap.invalidateSize();
                        try {
                            editMap.setView([lat, lng], 13);
                        } catch (e) {}
                    }
                }, 300);
            }
        });
    }

    window.viewSiteDetail = function(id) {
        $.get(`${baseUrl}/sites/${id}/view`, function(data) {
            $('#site_name_heading').text(data.site_name);
            $('#site_name_detail').text(data.site_name);
            $('#guard_names_detail').text(data.guard_names);
            $('#address_detail').text(data.address);
            $('#plus_code_detail').text(data.plus_code);
            $('#post_code_detail').text(data.post_code);
            $('#site_code_detail').text(data.site_code);
            $('#contact_number_detail').text(data.contact_number);
            $('#contact_person_detail').text(data.contact_person);
            $('#note_detail').text(data.note);
            $('#start_time_detail').text(data.start_time);
            $('#end_time_detail').text(data.end_time);
            $('#break_time_detail').text(data.break_time);
            $('#guard_rate_detail').text(`$${data.guard_rate ?? 0}`);
            $('#office_rate_detail').text(`$${data.office_rate ?? 0}`);
            $('#billable_rate_detail').text(`$${data.billable_rate ?? 0}`);
            $('#payable_rate_detail').text(`$${data.payable_rate ?? 0}`);
            $('#manager_1_detail').text(data.manager_1_name ?? '');
            $('#manager_2_detail').text(data.manager_2_name ?? '');
            if (data.has_qr == 1) {
                $('#has_qr').html('<span class="badge bg-success">Yes</span>');
            } else {
                $('#has_qr').html('<span class="badge bg-secondary">No</span>');
            }
            // ✅ Render checkpoints
            let checkpointsHtml = '';
            if (data.checkpoints && data.checkpoints.length > 0) {
                checkpointsHtml = `<ul class="list-group">`;
                data.checkpoints.forEach(cp => {
                    checkpointsHtml += `
                                                                <li class="list-group-item">
                                                                    <strong>${cp.name}</strong><br>
                                                                    Lat: ${cp.latitude ?? '-'} | Lng: ${cp.longitude ?? '-'}<br>
                                                                    QR: ${cp.qr_code ?? '-'} | NFC: ${cp.nfc_tag ?? '-'}<br>
                                                                    Required: ${cp.required ? 'Yes' : 'No'}
                                                                </li>
                                                            `;
                });
                checkpointsHtml += `</ul>`;
            } else {
                checkpointsHtml = `<p class="text-muted">No checkpoints defined</p>`;
            }
                                                            // QR image
                                                            if (data.qr_image) {
                                                                const url = data.qr_image;
                                                                const imgHtml = `
                                                                    <a href="${url}" target="_blank" title="Open QR in new tab">
                                                                        <img src="${url}" alt="Site QR" style="max-width:200px;cursor:pointer;border:1px solid #ddd;padding:4px;background:#fff">
                                                                    </a>
                                                                    <div class="mt-2">
                                                                        <a href="${url}" class="btn btn-sm btn-primary" target="_blank">Open</a>
                                                                        <a href="${url}" class="btn btn-sm btn-secondary" download>Download</a>
                                                                        <button type="button" id="qrModalOpenPrint" class="btn btn-sm btn-info">Open & Print</button>
                                                                    </div>
                                                                `;
                                                                $('#qr_image_detail').html(imgHtml);
                                                                $('#qrModalOpenPrint').on('click', function(){ openAndPrint(url); });
                                                            } else {
                                                                $('#qr_image_detail').html('<span class="text-muted">No QR generated</span>');
                                                            }

                                                            // NFC tags for site (list all generated tags)
                                                            if (data.nfc_tags && data.nfc_tags.length > 0) {
                                                                let nfcHtml = '<div class="list-group">';
                                                                data.nfc_tags.forEach(item => {
                                                                    nfcHtml += `<div class="list-group-item d-flex justify-content-between align-items-center">` +
                                                                                `<div><code class="p-2 bg-light border rounded">${item.tag}</code></div>` +
                                                                                `<div><button type="button" class="btn btn-sm btn-outline-secondary copy-site-nfc" data-tag="${item.tag}">Copy</button> <a href="${item.file}" class="btn btn-sm btn-secondary" download>Download</a></div>` +
                                                                            `</div>`;
                                                                });
                                                                nfcHtml += '</div>';
                                                                $('#nfc_tags_detail').html(nfcHtml);
                                                                $('.copy-site-nfc').on('click', function(){
                                                                    const tag = $(this).data('tag');
                                                                    navigator.clipboard?.writeText(tag).then(() => { 
                                                                        toast_success('NFC tag copied to clipboard'); 
                                                                    }).catch(() => { 
                                                                        alert('Failed to copy NFC tag'); 
                                                                    });
                                                                });
                                                            } else {
                                                                $('#nfc_tags_detail').html('<span class="text-muted">No NFC tags generated</span>');
                                                            }

            $('#checkpoints_detail').html(checkpointsHtml);
            // radius
            $('#radius_detail').text(data.radius ?? '-');

            let modal = new bootstrap.Modal(document.getElementById('viewSiteDetailModal'));
            modal.show();
        }).fail(function() {
            toast_danger('Failed to fetch site detail.');
        });
    }

    if (typeof editMap === 'undefined') var editMap = null;
    if (typeof siteMarker === 'undefined') var siteMarker = null;
    if (typeof checkpointMarkers === 'undefined') var checkpointMarkers = []; // store {marker, index}
    if (typeof editSiteStaffRates === 'undefined') var editSiteStaffRates = [];
    if (typeof editSiteHolidayRates === 'undefined') var editSiteHolidayRates = [];
    if (typeof ukHolidays === 'undefined') var ukHolidays = [];

    function initEditMap(lat = 51.505, lng = -0.09, checkpoints = []) {
        if (!editMap) {
            editMap = L.map('editSiteMap').setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(editMap);

            siteMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(editMap);
            siteMarker.on('dragend', function() {
                let pos = siteMarker.getLatLng();
                $('#edit_latitude').val(pos.lat);
                $('#edit_longitude').val(pos.lng);
            });

            // Click to add new checkpoint
            editMap.on('click', function(e) {
                let cpName = prompt("Enter checkpoint name:");
                if (cpName) {
                    addCheckpoint(cpName, e.latlng.lat, e.latlng.lng);
                }
            });
        } else {
            editMap.setView([lat, lng], 13);
            siteMarker.setLatLng([lat, lng]);
        }

        $('#edit_latitude').val(lat);
        $('#edit_longitude').val(lng);

        // Reset old
        checkpointMarkers.forEach(cp => editMap.removeLayer(cp.marker));
        checkpointMarkers = [];
        $('#edit_checkpointList').empty();
        console.log(checkpoints)

        // Load checkpoints
        checkpoints.forEach(cp => {
            addCheckpoint(cp.name, cp.latitude, cp.longitude, cp.id, cp.nfc_tag ?? null);
        });

        setTimeout(() => editMap.invalidateSize(), 300);
    }

    // ─────────────────────────────────────────────────────────────
    // Live re-center of the edit map when the user edits plus_code,
    // address, or post_code. Calls the server-side /sites/geocode
    // endpoint which uses GeoService (Google) and respects plus_code
    // priority. Debounced to avoid hammering the API on every keystroke.
    // ─────────────────────────────────────────────────────────────
    if (typeof window.__siteGeocodeTimer === 'undefined') window.__siteGeocodeTimer = null;
    function recenterEditMapFromInputs() {
        if (!editMap || !siteMarker) return;
        const plus = ($('#plus_code').val() || '').trim();
        const addr = ($('#address').val() || '').trim();
        const post = ($('#post_code').val() || '').trim();
        if (!plus && !addr && !post) return;

        clearTimeout(window.__siteGeocodeTimer);
        window.__siteGeocodeTimer = setTimeout(function() {
            $.ajax({
                url: `${baseUrl}/sites/geocode`,
                method: 'POST',
                data: { plus_code: plus, address: addr, post_code: post, _token: $('input[name="_token"]').val() },
                success: function(resp) {
                    if (resp && resp.ok && resp.lat && resp.lng) {
                        editMap.setView([resp.lat, resp.lng], 17);
                        siteMarker.setLatLng([resp.lat, resp.lng]);
                        $('#edit_latitude').val(resp.lat);
                        $('#edit_longitude').val(resp.lng);
                    }
                }
            });
        }, 700);
    }

    // Bind once at document level (delegated) so it works for dynamically shown modal.
    $(document).off('change.siteGeocode', '#edit_site #plus_code, #edit_site #post_code')
               .on('change.siteGeocode', '#edit_site #plus_code, #edit_site #post_code', recenterEditMapFromInputs);
    $(document).off('blur.siteGeocode', '#edit_site #address')
               .on('blur.siteGeocode', '#edit_site #address', recenterEditMapFromInputs);

    function renderSiteStaffRates() {
        const $tbody = $('#site_staff_rates_list');
        $tbody.empty();
        editSiteStaffRates.forEach((r, idx) => {
            const name = r.name || (r.user ? (r.user.first_name + ' ' + r.user.last_name) : r.user_id);
            const rateVal = (r.guard_rate !== undefined && r.guard_rate !== null) ? r.guard_rate : '';
            $tbody.append(`<tr data-index="${idx}"><td>${name}</td><td><input type="text" class="form-control numeric-input site-staff-rate-input" data-index="${idx}" value="${rateVal}"></td><td><button type="button" class="btn btn-sm btn-danger remove-site-staff-rate" data-index="${idx}">Remove</button></td></tr>`);
        });
    }

    // delegated handlers for dynamic staff rates UI
    $(document).on('click', '.remove-site-staff-rate', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx)) {
            editSiteStaffRates.splice(idx, 1);
            renderSiteStaffRates();
        }
    });

    $(document).on('input', '.site-staff-rate-input', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx) && editSiteStaffRates[idx]) {
            editSiteStaffRates[idx].guard_rate = $(this).val();
        }
    });

    function renderSiteHolidayRates() {
        const $tbody = $('#site_holiday_rates_list');
        $tbody.empty();
        editSiteHolidayRates.forEach((r, idx) => {
            const holidayName = r.holiday_name || '';
            const holidayDate = r.holiday_date || '';
            const siteRateVal = (r.site_rate !== undefined && r.site_rate !== null) ? r.site_rate : '';
            const guardRateVal = (r.guard_rate !== undefined && r.guard_rate !== null) ? r.guard_rate : '';
            $tbody.append(`
                <tr data-index="${idx}">
                    <td>${holidayName}</td>
                    <td>${holidayDate}</td>
                    <td><input type="text" class="form-control numeric-input site-holiday-site-rate-input" data-index="${idx}" value="${siteRateVal}"></td>
                    <td><input type="text" class="form-control numeric-input site-holiday-guard-rate-input" data-index="${idx}" value="${guardRateVal}"></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-site-holiday-rate" data-index="${idx}">Remove</button></td>
                </tr>
            `);
        });
    }

    $(document).on('click', '.remove-site-holiday-rate', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx)) {
            editSiteHolidayRates.splice(idx, 1);
            renderSiteHolidayRates();
        }
    });

    $(document).on('input', '.site-holiday-site-rate-input', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx) && editSiteHolidayRates[idx]) {
            editSiteHolidayRates[idx].site_rate = $(this).val();
        }
    });

    $(document).on('input', '.site-holiday-guard-rate-input', function() {
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx) && editSiteHolidayRates[idx]) {
            editSiteHolidayRates[idx].guard_rate = $(this).val();
        }
    });

    function addCheckpoint(name, lat, lng, id = null, nfcTag = null) {
        let index = checkpointMarkers.length;

        let marker = L.marker([lat, lng], {
            draggable: true
        }).addTo(editMap);
        checkpointMarkers.push({
            marker,
            index
        });

        const nfcDisplay = nfcTag
            ? `<code class="small bg-light border rounded p-1">${nfcTag}</code>`
            : `<span class="text-muted small fst-italic">Auto-generated</span>`;

        let row = `
                    <tr id="edit_checkpoint_row_${index}">
                                    <td>
                                        <input type="hidden" name="checkpoints[${index}][id]" value="${id ?? ''}">
                                        <input type="text" class="form-control"
                                               name="checkpoints[${index}][name]" 
                                               value="${name}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" 
                                               name="checkpoints[${index}][latitude]" 
                                               value="${lat}" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" 
                                               name="checkpoints[${index}][longitude]" 
                                               value="${lng}" readonly>
                                    </td>
                                    <td>
                                        ${nfcDisplay}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="removeCheckpoint(${index})">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            `;
        $('#edit_checkpointList').append(row);

        marker.on('dragend', function() {
            let pos = marker.getLatLng();
            $(`#edit_checkpoint_row_${index} input[name="checkpoints[${index}][latitude]"]`).val(pos.lat);
            $(`#edit_checkpoint_row_${index} input[name="checkpoints[${index}][longitude]"]`).val(pos.lng);
        });
    }

    function removeCheckpoint(index) {
        let cp = checkpointMarkers.find(c => c.index === index);
        if (cp) {
            editMap.removeLayer(cp.marker);
            $(`#edit_checkpoint_row_${index}`).remove();
        }
    }
    let selectedId = null;

    function deleteSite(record_id) {
        selectedId = record_id;
        $('#delete_modal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (selectedId !== null) {
            $.ajax({
                url: `${baseUrl}/deletesite/${selectedId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    closeBsModal('#delete_modal');

                    toast_success('Site Deleted Successfully!')
                    reloadDatatable('#sites-table');
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
            toast_danger('Please select at least one site to delete.');
            return;
        }

        if (!confirm('Are you sure you want to delete the selected sites?')) return;

        $.ajax({
            url: '{{ route('sites.bulkDelete') }}',
            type: 'POST',
            data: {
                ids: selected,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toast_success('Selected sites deleted successfully!');
                reloadDatatable('#sites-table');
            },
            error: function() {
                toast_danger('Something went wrong during bulk delete.');
            }
        });
    });

    function viewLogs(siteId) {
        // Clear existing content
        const modalBody = document.querySelector('#logModal .modal-body');
        modalBody.innerHTML = '<p class="text-muted">Loading logs...</p>';

        fetch(`${baseUrl}/sites/${siteId}/logs/ajax`)
            .then(response => response.json())
            .then(data => {
                if (data.logs.length === 0) {
                    modalBody.innerHTML = '<p class="text-muted">No logs found for this site.</p>';
                } else {
                    let html = '<table class="table table-bordered table-striped">';
                    html +=
                        '<thead><tr><th>User</th><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>';
                    data.logs.forEach(log => {
                        html +=
                            `<tr>
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
    </script>
    <script>
        function openAndPrint(url) {
            var win = window.open(url, '_blank');
            if (win) {
                var timer = setInterval(function() {
                    try {
                        if (win.document.readyState === 'complete') {
                            clearInterval(timer);
                            win.focus();
                            win.print();
                        }
                    } catch (err) {
                        clearInterval(timer);
                        alert('Unable to auto-print. Use Open or Download options.');
                    }
                }, 200);
            } else {
                alert('Popup blocked. Use Open or Download options.');
            }
        }
    </script>
    <script>
        document.querySelectorAll('.toggle-rate').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const id = this.dataset.id;
                const guardInput = document.querySelector(`.guard-rate-input.rate-${id}`);
                const officeInput = document.querySelector(`.office-rate-input.rate-${id}`);

                if (this.checked) {
                    guardInput.style.display = 'block';
                    officeInput.style.display = 'block';
                } else {
                    guardInput.style.display = 'none';
                    officeInput.style.display = 'none';
                }
            });
        });
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
