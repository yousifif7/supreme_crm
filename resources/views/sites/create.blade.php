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
                                            <input type="number" name="radius" class="form-control numeric-input" id="radius" min="0" step="any" placeholder="Enter radius in meters">
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
                
                // Address field change event
                $('textarea[name="address"]').on('change', function() {
                    const address = $(this).val().trim();
                    if (address) {
                        updateMapFromAddress(address);
                    }
                });
                
                // Optional: Add debouncing to prevent too many API calls
                let addressTimeout;
                $('textarea[name="address"]').on('input', function() {
                    clearTimeout(addressTimeout);
                    addressTimeout = setTimeout(() => {
                        const address = $(this).val().trim();
                        if (address.length > 5) { // Only search if address is reasonably long
                            updateMapFromAddress(address);
                        }
                    }, 1500); // Wait 1.5 seconds after typing stops
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
                                try { if (cp && cp.marker && createMap) createMap.removeLayer(cp.marker); } catch (e) {}
                            });
                        }
                    } catch (e) {}
                    createCheckpointMarkers = [];
                    $('#create_checkpointList').empty();

                    // Reset main site marker to default and clear hidden coords
                    if (createSiteMarker && createMap) {
                        try { createSiteMarker.setLatLng([51.505, -0.09]); } catch (e) {}
                    }
                    $('#latitude').val('');
                    $('#longitude').val('');

                    // Reset select2/selects
                    try { $('#clientSelect').val('').trigger('change'); } catch (e) {}
                }

                $('#add_site').on('hidden.bs.modal', function() {
                    resetCreateModal();
                });
            });

 </script>