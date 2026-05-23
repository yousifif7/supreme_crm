 <div class="modal fade" id="add_site">
     <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
             <div class="modal-header">
                 <h4 class="modal-title">Add New Site</h4>
                 <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                     <i class="ti ti-x"></i>
                 </button>
             </div>
             <form method="POST" id="add_site-form">
                 @csrf
                 <div class="tab-content" id="myTabContent">
                     <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                         tabindex="0">
                         <div class="modal-body pb-0 ">
                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="row">
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Client Name <span
                                                     class="text-danger">*</span></label>
                                             <select class="form-select select2 select2_client" id="clientSelect"
                                                 name="client_id" style="height: 100px !important;">
                                                 <option value="">--choose--</option>
                                                 @foreach ($clients as $client)
                                                     <option value="{{ $client->id }}">{{ $client->name }}
                                                         {{ $client->last_name }}
                                                     </option>
                                                 @endforeach
                                             </select>
                                             <span class="text-danger form-error" id="error_client_id"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Site Name <span
                                                     class="text-danger">*</span></label>
                                             <input type="text" name="site_name" class="form-control"
                                                 placeholder="Enter Site Name">
                                             <span class="text-danger form-error" id="error_site_name"></span>
                                         </div>
                                         <div class="col-md-12 mb-3">
                                             <label class="form-label">Address</label>
                                             <textarea class="form-control" name="address" cols="30" rows="4"></textarea>
                                             <span class="text-danger form-error" id="error_address"></span>
                                         </div>
                                         <div class="col-md-12 mb-3">
                                             <label class="form-label">Google Address </label>
                                             <input type="text" name="plus_code" class="form-control"
                                                 placeholder="Enter Google Plus Code">
                                             <span class="text-danger form-error" id="error_plus_code"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Post Code </label>
                                             <input type="text" name="post_code" class="form-control"
                                                 placeholder="Enter Post Code">
                                             <span class="text-danger form-error" id="error_post_code"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Site Code </label>
                                             <input type="text" name="site_code" class="form-control"
                                                 placeholder="Enter Site Code">
                                             <span class="text-danger form-error" id="error_site_code"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Radius (meters)</label>
                                             <input type="number" name="radius" class="form-control numeric-input"
                                                 id="radius" min="0" step="any"
                                                 placeholder="Enter radius in meters">
                                             <span class="text-danger form-error" id="error_radius"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Contact Person </label>
                                             <input type="text" name="contact_person" class="form-control"
                                                 placeholder="Enter Contact Person">
                                             <span class="text-danger form-error" id="error_contact_person"></span>


                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Contact Number </label>
                                             <input type="text" name="contact_number" class="form-control"
                                                 placeholder="Enter Contact Number">
                                             <span class="text-danger form-error" id="error_contact_number"></span>


                                         </div>
                                         <div class="col-md-12 mb-3">
                                             <label class="form-label">Site Note </label>
                                             <textarea class="form-control" name="note" cols="30" rows="4"></textarea>
                                             <span class="text-danger form-error" id="error_note"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Manager</label>
                                             <select class="form-select" name="manager_id_1">
                                                 <option value="">--choose--</option>
                                             </select>
                                             <span class="text-danger form-error" id="error_manager_1_id"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Manager (2)</label>
                                             <select class="form-select" name="manager_id_2">
                                                 <option value="">--choose--</option>
                                             </select>
                                             <span class="text-danger form-error" id="error_manager_2_id"></span>
                                         </div>

                                     </div>
                                     <div class="col-md-6 mb-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="has_qr"
                                                 id="has_qr" value="1">
                                             <label class="form-label text-danger" for="has_qr">
                                                 Enable QR Checkpoints
                                             </label>
                                         </div>
                                     </div>
                                     <div class="col-md-12 mb-3">
                                         <label class="form-label">Site & Checkpoints</label>
                                         <div id="createSiteMap" style="height: 350px; border-radius: 8px;"></div>

                                         <!-- Hidden inputs for site coordinates (create modal) -->
                                         <input type="hidden" name="latitude" id="latitude">
                                         <input type="hidden" name="longitude" id="longitude">

                                         <!-- Checkpoints list -->
                                         <div class="mt-3">
                                             <h6>Checkpoints</h6>
                                             <table class="table table-sm table-bordered" id="checkpointTable">
                                                 <thead>
                                                     <tr>
                                                         <th>Name</th>
                                                         <th>Latitude</th>
                                                         <th>Longitude</th>
                                                         <th>NFC Tag</th>
                                                         <th>Action</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody id="create_checkpointList">
                                                     <!-- dynamically filled -->
                                                 </tbody>
                                             </table>
                                         </div>
                                     </div>
                                 </div> <!--part-1 -->
                                 <div class="col-md-6">
                                     <div class="row">
                                         <div class="col-md-4 mb-3">
                                             <label class="form-label">Start Time</label>
                                             <input type="text" class="form-control time-input" name="start_time">
                                             <span class="text-danger form-error" id="error_start_time"></span>
                                         </div>
                                         <div class="col-md-4 mb-3">
                                             <label class="form-label">End Time</label>
                                             <input type="text" name="end_time" class="form-control time-input">
                                             <span class="text-danger form-error" id="error_end_time"></span>
                                         </div>
                                         <div class="col-md-4 mb-3">
                                             <label class="form-label">Break Time</label>
                                             <select class="form-select" name="break_time">
                                                 <option value="" hidden>Select Break Time</option>
                                                 <option value="0">No Break</option>
                                                 <option value="15">15 Minutes</option>
                                                 <option value="30">30 Minutes</option>
                                                 <option value="45">45 Minutes</option>
                                                 <option value="60">1 Hour</option>
                                                 <option value="75">1:15 Hour</option>
                                                 <option value="90">1:30 Hours</option>
                                                 <option value="105">1:45 Hour</option>
                                                 <option value="120">2:00 Hours</option>
                                             </select>
                                             <span class="text-danger form-error" id="error_break_time"></span>
                                         </div>
                                         @hasanyrole('superadmin|admin')
                                             <div class="col-md-6 mb-3">
                                                 <label class="form-label">Guard Rate</label>
                                                 <input type="text" name="guard_rate"
                                                     class="form-control numeric-input guardRate"
                                                     placeholder="Guard Rate">
                                                 <span class="text-danger form-error" id="error_guard_rate"></span>
                                             </div>
                                             <div class="col-md-6 mb-3">
                                                 <label class="form-label">Site Rate </label>
                                                 <input type="text" name="office_rate"
                                                     class="form-control numeric-input siteRate"
                                                     placeholder="Office Rate">
                                                 <span class="text-danger form-error" id="error_office_rate"></span>
                                             </div>
                                         @endhasanyrole
                                         <div class="col-md-12 mb-3">
                                             <label class="form-label">
                                                 Name of the Guards
                                                 <small class="text-muted">(Include additional info such as Trained
                                                     Guards, Banned Guards)</small>
                                             </label>
                                             <textarea name="guard_names" class="form-control" rows="3" placeholder="Enter names and info of guards..."></textarea>
                                             <span class="text-danger form-error" id="error_guard_names"></span>
                                         </div>

                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Expenses</label>
                                             <input type="text" name="billable_rate"
                                                 class="form-control numeric-input" placeholder="Billable">
                                             <span class="text-danger form-error" id="error_billable_rate"></span>
                                         </div>
                                         <div class="col-md-6 mb-3">
                                             <label class="form-label">Expenses</label>
                                             <input type="text" name="payable_rate"
                                                 class="form-control numeric-input" placeholder="Payable">
                                             <span class="text-danger form-error" id="error_payable_rate"></span>
                                         </div>
                                                                                  <div class="col-md-12 mt-3">
                                             <div
                                                 class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                 <h6>Staff-Specific Rates</h6>

                                             </div>
                                             <div class="d-flex gap-2 mb-2 align-items-center flex-wrap">
                                                 <div class="me-2" style="flex:0 0 220px; min-width:160px;">
                                                     @php $create_staffs = \App\Models\User::role('security_staff')->get(); @endphp
                                                     <select id="create_site_staff_select"
                                                         class="form-select create-staff-select2 w-100">
                                                         <option value="">--choose staff--</option>
                                                         @foreach ($create_staffs as $s)
                                                             <option value="{{ $s->id }}">{{ $s->first_name }}
                                                                 {{ $s->last_name }}</option>
                                                         @endforeach
                                                     </select>
                                                 </div>
                                                 <div class="me-2" style="flex:0 0 140px;">
                                                     <input type="text" id="create_site_staff_rate_input"
                                                         class="form-control numeric-input" placeholder="Rate"
                                                         style="width:140px;">
                                                 </div>
                                                 <div>
                                                     <button type="button" id="create_add_site_staff_rate"
                                                         class="btn btn-primary">Add</button>
                                                 </div>
                                             </div>
                                             <div class="table-responsive">
                                                 <table class="table table-sm table-bordered"
                                                     id="create_site_staff_rates_table">
                                                     <thead>
                                                         <tr>
                                                             <th>Staff</th>
                                                             <th style="width:140px">Rate</th>
                                                             <th style="width:120px">Action</th>
                                                         </tr>
                                                     </thead>
                                                     <tbody id="create_site_staff_rates_list"></tbody>
                                                 </table>
                                             </div>
                                         </div>
                                         <br>
                                         <div class="col-md-12 mt-3">
                                             <label class="form-label">Holiday-specific Rates</label>
                                             <div class="d-flex gap-2 mb-2 align-items-center flex-wrap">
                                                 <div class="me-2" style="flex:0 0 220px; min-width:160px;">
                                                     <select id="create_site_holiday_select" class="form-select holiday-select2 w-100">
                                                         <option value="">--choose holiday--</option>
                                                     </select>
                                                 </div>
                                                 <div class="me-2" style="flex:0 0 140px;">
                                                     <input type="text" id="create_site_holiday_site_rate_input" class="form-control numeric-input" placeholder="Site Rate" style="width:140px;">
                                                 </div>
                                                 <div class="me-2" style="flex:0 0 140px;">
                                                     <input type="text" id="create_site_holiday_guard_rate_input" class="form-control numeric-input" placeholder="Guard Rate" style="width:140px;">
                                                 </div>
                                                 <div>
                                                     <button type="button" id="create_add_site_holiday_rate" class="btn btn-primary">Add</button>
                                                 </div>
                                             </div>
                                             <div class="table-responsive">
                                                 <table class="table table-sm table-bordered" id="create_site_holiday_rates_table">
                                                     <thead>
                                                         <tr>
                                                             <th>Holiday</th>
                                                             <th>Date</th>
                                                             <th style="width:140px">Site Rate</th>
                                                             <th style="width:140px">Guard Rate</th>
                                                             <th style="width:120px">Action</th>
                                                         </tr>
                                                     </thead>
                                                     <tbody id="create_site_holiday_rates_list"></tbody>
                                                 </table>
                                             </div>
                                             <input type="hidden" name="holiday_rates" id="create_holiday_rates_input">
                                         </div>
                                         <br>
                                         <div class="card bg-light-500 shadow-none">
                                             <div
                                                 class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                 <h6>Service types</h6>

                                             </div>
                                         </div>
                                         <div class="table-responsive permission-table border rounded">
                                             <table class="table">
                                                 <thead>
                                                     <th></th>
                                                     <th>Name</th>
                                                     <th>Guard Rate</th>
                                                     <th>Office Rate</th>
                                                 </thead>
                                                 <tbody>
                                                     @foreach ($employee_types as $type)
                                                         <tr>
                                                             <td>
                                                                 <input type="checkbox"
                                                                     class="form-check-input toggle-rate"
                                                                     name="employee_types[]"
                                                                     value="{{ $type->id }}"
                                                                     data-id="{{ $type->id }}">
                                                             </td>
                                                             <td>
                                                                 <label
                                                                     class="form-check-label mt-0">{{ $type->name }}</label>
                                                             </td>
                                                             <td>
                                                                 <input type="text"
                                                                     class="form-control numeric-input guard-rate-input rate-{{ $type->id }}"
                                                                     name="employee_guard_rate[{{ $type->id }}]"
                                                                     placeholder="Guard rate" style="display: none;">
                                                             </td>
                                                             <td>
                                                                 <input type="text"
                                                                     class="form-control numeric-input office-rate-input rate-{{ $type->id }}"
                                                                     name="employee_office_rate[{{ $type->id }}]"
                                                                     placeholder="Office rate" style="display: none;">
                                                             </td>
                                                         </tr>
                                                     @endforeach
                                                 </tbody>
                                             </table>
                                         </div>

                                         <br>
                                     </div>
                                 </div>



                             </div>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-outline-light border me-2"
                                 data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" form="add_site-form" id="savesite" class="btn btn-primary">Save
                             </button>
                         </div>
                     </div>
                 </div>
             </form>
         </div>
     </div>
 </div>

 <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
 <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

 <script>
     if (typeof createMap === 'undefined') var createMap = null;
     if (typeof createSiteMarker === 'undefined') var createSiteMarker = null;
     if (typeof createCheckpointMarkers === 'undefined') var createCheckpointMarkers = []; // store markers + indexes

     // Geocoding function
     function geocodeAddress(address, callback) {
         if (!address || address.trim() === '') {
             return;
         }

         // Using Nominatim (OpenStreetMap's geocoding service)
         const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;

         $.ajax({
             url: url,
             method: 'GET',
             dataType: 'json',
             headers: {
                 'Accept': 'application/json',
                 'User-Agent': 'YourApp/1.0' // Required by Nominatim
             },
             success: function(data) {
                 if (data && data.length > 0) {
                     const lat = parseFloat(data[0].lat);
                     const lng = parseFloat(data[0].lon);
                     callback(lat, lng, data[0]);
                 } else {
                     console.warn('Address not found:', address);
                     // Optionally show error to user
                     // toast_warning('Address not found. Please check the address or set location manually on map.');
                 }
             },
             error: function(xhr, status, error) {
                 console.error('Geocoding error:', error);
                 // toast_danger('Error geocoding address. Please set location manually on map.');
             }
         });
     }

     function initCreateMap(lat = 51.505, lng = -0.09, checkpoints = []) {
         if (!createMap) {
             // Init map once
             createMap = L.map('createSiteMap').setView([lat, lng], 13);

             L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                 attribution: '&copy; OpenStreetMap contributors'
             }).addTo(createMap);

             // Site marker (main location)
             createSiteMarker = L.marker([lat, lng], {
                 draggable: true
             }).addTo(createMap);

             createSiteMarker.on('dragend', function() {
                 let pos = createSiteMarker.getLatLng();
                 $('#latitude').val(pos.lat);
                 $('#longitude').val(pos.lng);
             });

             // Click on map → add checkpoint
             createMap.on('click', function(e) {
                 let cpName = prompt("Enter checkpoint name:");
                 if (cpName) {
                     addCheckpointCreate(cpName, e.latlng.lat, e.latlng.lng);
                 }
             });
         } else {
             // Reset view + site marker position
             createMap.setView([lat, lng], 13);
             createSiteMarker.setLatLng([lat, lng]);
         }

         // Update hidden site coords
         $('#latitude').val(lat);
         $('#longitude').val(lng);

         // Clear previous checkpoints
         createCheckpointMarkers.forEach(cp => createMap.removeLayer(cp.marker));
         createCheckpointMarkers = [];
         $('#create_checkpointList').empty();

         // Load checkpoints if editing
         checkpoints.forEach(cp => {
             addCheckpointCreate(cp.name, cp.latitude, cp.longitude, cp.id);
         });

         setTimeout(() => createMap.invalidateSize(), 300); // fix resizing bug
     }

     // Function to update map location
     function updateMapFromAddress(address) {
         if (!createMap || !createSiteMarker) return;

         geocodeAddress(address, function(lat, lng, result) {
             // Update map view
             createMap.setView([lat, lng], 13);

             // Update marker position
             createSiteMarker.setLatLng([lat, lng]);

             // Update hidden input fields
             $('#latitude').val(lat);
             $('#longitude').val(lng);

             // Optional: Show popup with address
             createSiteMarker.bindPopup(`<b>Location:</b><br>${result.display_name || address}`).openPopup();
         });
     }

     function addCheckpointCreate(name, lat, lng, id = null) {
         let index = createCheckpointMarkers.length;

         // Create marker
         let marker = L.marker([lat, lng], {
             draggable: true
         }).addTo(createMap);

         createCheckpointMarkers.push({
             marker,
             index
         });

         // Append row to table
         let row = `
        <tr id="create_checkpoint_row_${index}">
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
                <span class="text-muted small fst-italic">Auto-generated</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" 
                        onclick="removeCheckpointCreate(${index})">
                    Remove
                </button>
            </td>
        </tr>
    `;
         $('#create_checkpointList').append(row);

         // Update row when marker is dragged
         marker.on('dragend', function() {
             let pos = marker.getLatLng();
             $(`#create_checkpoint_row_${index} input[name="checkpoints[${index}][latitude]"]`).val(pos.lat);
             $(`#create_checkpoint_row_${index} input[name="checkpoints[${index}][longitude]"]`).val(pos.lng);
         });
     }

     function removeCheckpointCreate(index) {
         let cp = createCheckpointMarkers.find(c => c.index === index);
         if (cp) {
             createMap.removeLayer(cp.marker);
             $(`#create_checkpoint_row_${index}`).remove();
         }
     }

     // =============================
     // DOM Ready + AJAX example
     // =============================

     $(document).ready(function() {

         // Initialize map for create modal
         initCreateMap();

         // ─────────────────────────────────────────────────────────
         // Re-center the create map from any of:
         //   • plus_code (highest priority — most precise)
         //   • address + post_code (fallback)
         // Uses the server endpoint /sites/geocode → GeoService (Google),
         // which respects plus_code priority and country bias.
         // ─────────────────────────────────────────────────────────
         let createGeocodeTimer = null;
         function recenterCreateMapFromInputs(immediate = false) {
             if (!createMap || !createSiteMarker) return;
             const plus = ($('input[name="plus_code"]').val() || '').trim();
             const addr = ($('textarea[name="address"]').val() || '').trim();
             const post = ($('input[name="post_code"]').val() || '').trim();
             if (!plus && !addr && !post) return;

             clearTimeout(createGeocodeTimer);
             createGeocodeTimer = setTimeout(function() {
                 $.ajax({
                     url: `${baseUrl}/sites/geocode`,
                     method: 'POST',
                     data: { plus_code: plus, address: addr, post_code: post, _token: $('input[name="_token"]').val() },
                     success: function(resp) {
                         if (resp && resp.ok && resp.lat && resp.lng) {
                             createMap.setView([resp.lat, resp.lng], 17);
                             createSiteMarker.setLatLng([resp.lat, resp.lng]);
                             $('#latitude').val(resp.lat);
                             $('#longitude').val(resp.lng);
                             if (resp.formatted_address) {
                                 createSiteMarker.bindPopup(`<b>Location:</b><br>${resp.formatted_address}`).openPopup();
                             }
                         }
                     }
                 });
             }, immediate ? 0 : 700);
         }

         // Plus code change (precise) — small debounce
         $('input[name="plus_code"]').on('change', () => recenterCreateMapFromInputs(true));
         $('input[name="plus_code"]').on('input', () => recenterCreateMapFromInputs());

         // Post code change
         $('input[name="post_code"]').on('change', () => recenterCreateMapFromInputs(true));

         // Address debounced — 1.5s after typing stops
         let addressTimeout;
         $('textarea[name="address"]').on('change', () => recenterCreateMapFromInputs(true));
         $('textarea[name="address"]').on('input', function() {
             clearTimeout(addressTimeout);
             addressTimeout = setTimeout(() => {
                 if (($(this).val() || '').trim().length > 5) {
                     recenterCreateMapFromInputs(true);
                 }
             }, 1500);
         });

         $('#add_site-form').on('submit', function(e) {
             e.preventDefault();
             $("[id^='error_']").text('');

             const form = this;
             const formData = new FormData(form);
             const submitButton = $('#savesite');
             submitButton.prop('disabled', true).html('Saving...');

             if (createSiteMarker) {
                 const p = createSiteMarker.getLatLng();
                 $('#latitude').val(p.lat);
                 $('#longitude').val(p.lng);
             }

             $.ajax({
                 url: $(form).attr('action'),
                 method: 'POST',
                 data: formData,
                 processData: false,
                 contentType: false,
                 headers: {
                     'X-CSRF-TOKEN': $('input[name="_token"]').val()
                 },
                 success: function(response) {
                     // Reset modal form and map state before closing
                     resetCreateModal();
                     closeBsModal('#add_site');
                     toast_success(response.message ||
                         'Site created successfully');
                     reloadDatatable('#sites-table');
                 },
                 error: function(xhr) {
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

         // Optional: re-render map on modal show
         $('#add_site').on('shown.bs.modal', function() {
             setTimeout(() => createMap.invalidateSize(), 300);

             // If address field already has value, geocode it
             const existingAddress = $('textarea[name="address"]').val().trim();
             if (existingAddress) {
                 updateMapFromAddress(existingAddress);
             }
         });

         // Clear modal state when hidden (also used after successful submit)
         function resetCreateModal() {
             try {
                 const form = document.getElementById('add_site-form');
                 if (form) form.reset();
             } catch (e) {
                 // ignore
             }

             // Clear checkpoint rows and remove markers from map
             try {
                 if (Array.isArray(createCheckpointMarkers)) {
                     createCheckpointMarkers.forEach(cp => {
                         try {
                             if (cp && cp.marker && createMap) createMap.removeLayer(cp.marker);
                         } catch (e) {}
                     });
                 }
             } catch (e) {}
             createCheckpointMarkers = [];
             $('#create_checkpointList').empty();

             // Reset main site marker to default and clear hidden coords
             if (createSiteMarker && createMap) {
                 try {
                     createSiteMarker.setLatLng([51.505, -0.09]);
                 } catch (e) {}
             }
             $('#latitude').val('');
             $('#longitude').val('');

             // Reset select2/selects
             try {
                 $('#clientSelect').val('').trigger('change');
             } catch (e) {}

             // Clear any configured create-site staff rates UI/state
             try {
                 createSiteStaffRates = [];
                 $('#create_site_staff_rates_list').empty();
                 if ($('#create_site_staff_select').length) {
                     $('#create_site_staff_select').val('').trigger('change');
                 }
             } catch (ee) {}

             // Clear any configured create-site holiday rates UI/state
             try {
                 createSiteHolidayRates = [];
                 $('#create_site_holiday_rates_list').empty();
                 if ($('#create_site_holiday_select').length) {
                     $('#create_site_holiday_select').val('').trigger('change');
                 }
                 $('#create_site_holiday_site_rate_input').val('');
                 $('#create_site_holiday_guard_rate_input').val('');
             } catch (ee) {}
         }

         $('#add_site').on('hidden.bs.modal', function() {
             resetCreateModal();
         });

         // Clear create-site staff rates when resetting modal
         if (typeof createSiteStaffRates === 'undefined') var createSiteStaffRates = [];

         // Clear create-site holiday rates when resetting modal
         if (typeof createSiteHolidayRates === 'undefined') var createSiteHolidayRates = [];
         if (typeof ukHolidays === 'undefined') var ukHolidays = [];

         function renderCreateSiteStaffRates() {
             const $tbody = $('#create_site_staff_rates_list');
             $tbody.empty();
             createSiteStaffRates.forEach((r, idx) => {
                 const name = r.name || (r.user ? (r.user.first_name + ' ' + r.user.last_name) : r
                     .user_id);
                 const rateVal = (r.guard_rate !== undefined && r.guard_rate !== null) ? r.guard_rate :
                     '';
                 $tbody.append(
                     `<tr data-index="${idx}"><td>${name}</td><td><input type="text" class="form-control numeric-input create-site-staff-rate-input" data-index="${idx}" value="${rateVal}"></td><td><button type="button" class="btn btn-sm btn-danger remove-create-site-staff-rate" data-index="${idx}">Remove</button></td></tr>`
                     );
             });
         }

         // Bind add button for create modal
         $('#create_add_site_staff_rate').off('click').on('click', function() {
             const $sel = $('#create_site_staff_select');
             const userId = $sel.val();
             if (!userId) {
                 toast_danger('Please choose a staff');
                 return;
             }
             const userName = $sel.find('option:selected').text();
             const rate = $('#create_site_staff_rate_input').val() || null;
             if (createSiteStaffRates.find(r => parseInt(r.user_id) === parseInt(userId))) {
                 toast_danger('A rate is already set for the selected staff');
                 return;
             }
             createSiteStaffRates.push({
                 user_id: parseInt(userId),
                 guard_rate: rate,
                 name: userName
             });
             renderCreateSiteStaffRates();
             $('#create_site_staff_rate_input').val('');
         });

         // Delegated remove handler
         $(document).on('click', '.remove-create-site-staff-rate', function() {
             const idx = parseInt($(this).data('index'));
             if (!isNaN(idx)) {
                 createSiteStaffRates.splice(idx, 1);
                 renderCreateSiteStaffRates();
             }
         });

         // Delegated input update handler
         $(document).on('input', '.create-site-staff-rate-input', function() {
             const idx = parseInt($(this).data('index'));
             if (!isNaN(idx) && createSiteStaffRates[idx]) {
                 createSiteStaffRates[idx].guard_rate = $(this).val();
             }
         });

         // Ensure select2 for create staff select
         try {
             if ($('#create_site_staff_select').hasClass('select2-hidden-accessible')) {
                 $('#create_site_staff_select').select2('destroy');
             }
         } catch (ee) {}
         try {
             $('#create_site_staff_select').select2({
                 placeholder: '--choose staff--',
                 allowClear: true,
                 width: 'style',
                 dropdownParent: $('#add_site .modal-content'),
                 minimumResultsForSearch: 0
             });
         } catch (ee) {}

         // ========== Holiday Rates Logic ==========
         function renderCreateSiteHolidayRates() {
             const $tbody = $('#create_site_holiday_rates_list');
             $tbody.empty();
             createSiteHolidayRates.forEach((r, idx) => {
                 const holidayName = r.holiday_name || '';
                 const holidayDate = r.holiday_date || '';
                 const siteRate = (r.site_rate !== undefined && r.site_rate !== null) ? r.site_rate : '';
                 const guardRate = (r.guard_rate !== undefined && r.guard_rate !== null) ? r.guard_rate : '';
                 $tbody.append(
                     `<tr data-index="${idx}"><td>${holidayName}</td><td>${holidayDate}</td><td><input type="text" class="form-control numeric-input create-site-holiday-site-rate-input" data-index="${idx}" value="${siteRate}"></td><td><input type="text" class="form-control numeric-input create-site-holiday-guard-rate-input" data-index="${idx}" value="${guardRate}"></td><td><button type="button" class="btn btn-sm btn-danger remove-create-site-holiday-rate" data-index="${idx}">Remove</button></td></tr>`
                 );
             });
         }

         // Bind add button for create holiday rates
         $('#create_add_site_holiday_rate').off('click').on('click', function() {
             const selectedHoliday = $('#create_site_holiday_select').val();
             if (!selectedHoliday) { toast_danger('Please choose a holiday'); return; }

             const [holidayName, holidayDate] = selectedHoliday.split('|');
             const siteRate = $('#create_site_holiday_site_rate_input').val() || null;
             const guardRate = $('#create_site_holiday_guard_rate_input').val() || null;

             if (createSiteHolidayRates.find(r => r.holiday_date === holidayDate)) {
                 toast_danger('A rate is already set for this holiday');
                 return;
             }

             createSiteHolidayRates.push({ holiday_name: holidayName, holiday_date: holidayDate, site_rate: siteRate, guard_rate: guardRate });
             renderCreateSiteHolidayRates();
             $('#create_site_holiday_site_rate_input').val('');
             $('#create_site_holiday_guard_rate_input').val('');
             $('#create_site_holiday_select').val('').trigger('change');
         });

         // Delegated remove handler for holiday rates
         $(document).on('click', '.remove-create-site-holiday-rate', function() {
             const idx = parseInt($(this).data('index'));
             if (!isNaN(idx)) {
                 createSiteHolidayRates.splice(idx, 1);
                 renderCreateSiteHolidayRates();
             }
         });

         // Delegated input update handlers for holiday rates
         $(document).on('input', '.create-site-holiday-site-rate-input, .create-site-holiday-guard-rate-input', function() {
             const idx = parseInt($(this).closest('tr').data('index'));
             if (!isNaN(idx) && createSiteHolidayRates[idx]) {
                 if ($(this).hasClass('create-site-holiday-site-rate-input')) {
                     createSiteHolidayRates[idx].site_rate = $(this).val();
                 } else {
                     createSiteHolidayRates[idx].guard_rate = $(this).val();
                 }
             }
         });

         // Ensure select2 for create holiday select
         try {
             if ($('#create_site_holiday_select').hasClass('select2-hidden-accessible')) {
                 $('#create_site_holiday_select').select2('destroy');
             }
         } catch (ee) { /* ignore */ }

         // Load holidays when modal is shown
         $('#add_site').on('shown.bs.modal', function() {
             try {
                 $.get(`${baseUrl}/holidays`, function(data) {
                     const $holidaySelect = $('#create_site_holiday_select');
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
                         dropdownParent: $('#add_site .modal-content'),
                         minimumResultsForSearch: 0,
                         data: holidayOptions
                     });
                 });
             } catch (e) { console.error(e); }
         });

         // Include staff rates in add_site form submit
         $('#add_site-form').off('submit').on('submit', function(e) {
             e.preventDefault();
             $("[id^='error_']").text('');

             const form = this;
             // remove any previously added dynamic inputs for create
             $(form).find('.dynamic-create-staff-rate').remove();
             if (Array.isArray(createSiteStaffRates) && createSiteStaffRates.length) {
                 createSiteStaffRates.forEach(function(r, idx) {
                     const uid = r.user_id ?? (r.user ? r.user.id : '') ?? '';
                     const rate = r.guard_rate ?? '';
                     $(form).append($('<input>', {
                         type: 'hidden',
                         name: `staff_rates[${idx}][user_id]`,
                         value: uid
                     }).addClass('dynamic-create-staff-rate'));
                     $(form).append($('<input>', {
                         type: 'hidden',
                         name: `staff_rates[${idx}][guard_rate]`,
                         value: rate
                     }).addClass('dynamic-create-staff-rate'));
                 });
             }

             // Remove any previously added dynamic inputs for holiday rates
             $(form).find('.dynamic-create-holiday-rate').remove();
             if (Array.isArray(createSiteHolidayRates) && createSiteHolidayRates.length) {
                 createSiteHolidayRates.forEach(function(r, idx) {
                     const holidayName = r.holiday_name ?? '';
                     const holidayDate = r.holiday_date ?? '';
                     const siteRate = r.site_rate ?? '';
                     const guardRate = r.guard_rate ?? '';
                     $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][holiday_name]`, value: holidayName }).addClass('dynamic-create-holiday-rate'));
                     $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][holiday_date]`, value: holidayDate }).addClass('dynamic-create-holiday-rate'));
                     $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][site_rate]`, value: siteRate }).addClass('dynamic-create-holiday-rate'));
                     $(form).append($('<input>', { type: 'hidden', name: `holiday_rates[${idx}][guard_rate]`, value: guardRate }).addClass('dynamic-create-holiday-rate'));
                 });
             }

             const formData = new FormData(form);
             const submitButton = $('#savesite');
             submitButton.prop('disabled', true).html('Saving...');

             if (createSiteMarker) {
                 const p = createSiteMarker.getLatLng();
                 $('#latitude').val(p.lat);
                 $('#longitude').val(p.lng);
             }

             $.ajax({
                 url: $(form).attr('action'),
                 method: 'POST',
                 data: formData,
                 processData: false,
                 contentType: false,
                 headers: {
                     'X-CSRF-TOKEN': $('input[name="_token"]').val()
                 },
                 success: function(response) {
                     // Reset modal form and map state before closing
                     resetCreateModal();
                     closeBsModal('#add_site');
                     toast_success(response.message || 'Site created successfully');
                     reloadDatatable('#sites-table');
                 },
                 error: function(xhr) {
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
     });
 </script>
