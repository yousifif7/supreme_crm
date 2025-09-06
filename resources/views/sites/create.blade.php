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
                                             <select class="form-select select2 select-client" id="clientSelect"
                                                 name="client_id" style="height: 100px !important;">
                                                 <option value="">--choose--</option>
                                                 @foreach ($clients as $client)
                                                     <option value="{{ $client->id }}">{{ $client->first_name }}
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
                                         <label class="form-label">Checkpoints / Locations</label>
                                         <button type="button" class="btn btn-sm btn-primary mb-2"
                                             id="addCheckpointBtn">
                                             + Add Checkpoint
                                         </button>
                                         <div id="checkpointsContainer"></div>
                                     </div>
                                 </div> <!--part-1 -->
                                 <div class="col-md-6">
                                     <div class="row">
                                         <div class="col-md-4 mb-3">
                                             <label class="form-label">Start Time</label>
                                             <input type="time" class="form-control" name="start_time">
                                             <span class="text-danger form-error" id="error_start_time"></span>
                                         </div>
                                         <div class="col-md-4 mb-3">
                                             <label class="form-label">End Time</label>
                                             <input type="time" name="end_time" class="form-control">
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
     document.addEventListener("DOMContentLoaded", function() {
         // Default map center (you can change)
         var map = L.map('map').setView([51.505, -0.09], 13);

         // OpenStreetMap layer
         L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
             attribution: '&copy; OpenStreetMap contributors'
         }).addTo(map);

         var marker;

         // On map click set marker and update inputs
         map.on('click', function(e) {
             var lat = e.latlng.lat.toFixed(6);
             var lng = e.latlng.lng.toFixed(6);

             // Update inputs
             document.getElementById('latitude').value = lat;
             document.getElementById('longitude').value = lng;

             // Add or move marker
             if (marker) {
                 marker.setLatLng(e.latlng);
             } else {
                 marker = L.marker(e.latlng).addTo(map);
             }
         });
     });

     document.addEventListener("DOMContentLoaded", function() {
         let checkpointIndex = 0;

         // Add new checkpoint block
         $('#addCheckpointBtn').on('click', function() {
             checkpointIndex++;

             let checkpointHtml = `
            <div class="card p-3 mb-2 checkpoint-item" data-index="${checkpointIndex}">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="checkpoints[${checkpointIndex}][name]" 
                               class="form-control" placeholder="Checkpoint Name" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="text" name="checkpoints[${checkpointIndex}][latitude]" 
                               class="form-control checkpoint-lat" placeholder="Latitude" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="text" name="checkpoints[${checkpointIndex}][longitude]" 
                               class="form-control checkpoint-lng" placeholder="Longitude" required>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-danger removeCheckpoint">Remove</button>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="checkpoint-map" id="map-${checkpointIndex}" style="height:200px; border-radius:6px;"></div>
                    </div>
                </div>
            </div>
        `;

             $('#checkpointsContainer').append(checkpointHtml);

             initMap(`map-${checkpointIndex}`, checkpointIndex);
         });

         // Remove checkpoint block
         $(document).on('click', '.removeCheckpoint', function() {
             $(this).closest('.checkpoint-item').remove();
         });

         // Initialize Leaflet map for a checkpoint
         function initMap(mapId, index) {
             var map = L.map(mapId).setView([51.505, -0.09], 13);

             L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                 //  attribution: '&copy; OpenStreetMap contributors'
             }).addTo(map);

             let marker;

             map.on('click', function(e) {
                 let lat = e.latlng.lat.toFixed(6);
                 let lng = e.latlng.lng.toFixed(6);

                 $(`input[name="checkpoints[${index}][latitude]"]`).val(lat);
                 $(`input[name="checkpoints[${index}][longitude]"]`).val(lng);

                 if (marker) {
                     marker.setLatLng(e.latlng);
                 } else {
                     marker = L.marker(e.latlng).addTo(map);
                 }
             });
         }
     });
 </script>
