  <div class="modal fade" id="edit_site">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title">Edit Site</h4>
                  <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                      <i class="ti ti-x"></i>
                  </button>
              </div>
              <form method="POST" id="edit_site-form">
                  @csrf
                  <input type="hidden" name="site_id" id="site_id">
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
                                              <select class="form-select select2 select-client" name="client_id"
                                                  id="client_id">
                                                  <option value="">--choose--</option>
                                                  @foreach ($clients as $client)
                                                      <option value="{{ $client->id }}">{{ $client->name }}
                                                      </option>
                                                  @endforeach
                                              </select>
                                              <span class="text-danger form-error" id="editerror_client_id"></span>
                                          </div>

                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Site Name <span
                                                      class="text-danger">*</span></label>
                                              <input type="text" name="site_name" id="site_name" class="form-control"
                                                  placeholder="Enter Site Name">
                                              <span class="text-danger form-error" id="editerror_site_name"></span>
                                          </div>
                                          <div class="col-md-12 mb-3">
                                              <label class="form-label">Address</label>
                                              <textarea class="form-control" name="address" id="address" cols="30" rows="4"></textarea>
                                              <span class="text-danger form-error" id="editerror_address"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Post Code</label>
                                              <input type="text" name="post_code" id="post_code" class="form-control"
                                                  placeholder="Enter Post Code">
                                              <span class="text-danger form-error" id="editerror_post_code"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Site Code </label>
                                              <input type="text" name="site_code" id="site_code" class="form-control"
                                                  placeholder="Enter Site Code">
                                              <span class="text-danger form-error" id="editerror_site_code"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Radius (meters)</label>
                                              <input type="number" name="radius" id="radius" class="form-control numeric-input" min="0" step="any" placeholder="Enter radius in meters">
                                              <span class="text-danger form-error" id="editerror_radius"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Contact Person </label>
                                              <input type="text" name="contact_person" id="contact_person"
                                                  class="form-control" placeholder="Enter Contact person">
                                              <span class="text-danger form-error" id="editerror_contact_person"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Contact Number </label>
                                              <input type="text" name="contact_number" id="contact_number"
                                                  class="form-control" placeholder="Enter Contact Number">
                                              <span class="text-danger form-error" id="editerror_contact_number"></span>
                                          </div>
                                          <div class="col-md-12 mb-3">
                                              <label class="form-label">Site Note</label>
                                              <textarea class="form-control" name="note" id="note" cols="30" rows="4"></textarea>
                                              <span class="text-danger form-error" id="editerror_note"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Manager</label>
                                              <select class="form-select" name="manager_1_id" id="manager_1_id">
                                                  <option value="">--choose--</option>
                                              </select>
                                              <span class="text-danger form-error" id="editerror_manager_1_id"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Manager (2)</label>
                                              <select class="form-select" name="manager_2_id" id="manager_2_id">
                                                  <option value="">--choose--</option>
                                              </select>
                                              <span class="text-danger form-error" id="editerror_manager_2_id"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <div class="form-check">
                                                  <input class="form-check-input" type="checkbox" name="has_qr"
                                                      id="edit_has_qr" value="1"
                                                      {{ old('has_qr', $site->has_qr ?? 0) ? 'checked' : '' }}>
                                                  <label class="form-label text-danger" for="edit_has_qr">
                                                      Enable QR Checkpoints
                                                  </label>
                                              </div>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">NFC Tags</label>
                                              <div class="d-flex gap-2 mb-2">
                                                  <button type="button" id="generateNfcBtn" class="btn btn-sm btn-outline-primary">Regenerate NFC tag</button>
                                              </div>
                                              <div id="edit_nfc_list">
                                                  <!-- populated dynamically with existing NFC tag -->
                                              </div>
                                          </div>
                                          <div class="col-md-12 mb-3">
                                              <label class="form-label">Site & Checkpoints</label>
                                              <div id="editSiteMap" style="height: 350px; border-radius: 8px;"></div>

                                              <!-- Hidden inputs for site coordinates (edit modal) -->
                                              <input type="hidden" name="latitude" id="edit_latitude">
                                              <input type="hidden" name="longitude" id="edit_longitude">

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
                                                      <tbody id="edit_checkpointList">
                                                          <!-- dynamically filled -->
                                                      </tbody>
                                                  </table>
                                              </div>
                                          </div>
                                      </div>
                                  </div> <!--part-1 -->
                                  <div class="col-md-6">
                                      <div class="row">
                                          <div class="col-md-4 mb-3">
                                              <label class="form-label">Start Time</label>
                                              <input type="text" class="form-control time-input" name="start_time"
                                                  id="start_time">
                                              <span class="text-danger form-error" id="editerror_start_time"></span>
                                          </div>
                                          <div class="col-md-4 mb-3">
                                              <label class="form-label">End Time</label>
                                              <input type="text" name="end_time" id="end_time"
                                                  class="form-control time-input">
                                              <span class="text-danger form-error" id="editerror_end_time"></span>
                                          </div>
                                          <div class="col-md-4 mb-3">
                                              <label class="form-label">Break Time</label>
                                              <select class="form-select" name="break_time" id="break_time">
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
                                              <span class="text-danger form-error" id="editerror_break_time"></span>
                                          </div>

                                          @hasanyrole('superadmin|admin')
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Guard Rate</label>
                                              <input type="text" name="guard_rate" id="guard_rate"
                                                  class="form-control" placeholder="Guard Rate">
                                              <span class="text-danger form-error" id="editerror_guard_rate"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Office Rate</label>
                                              <input type="text" name="office_rate" id="office_rate"
                                                  class="form-control" placeholder="Office Rate">
                                              <span class="text-danger form-error" id="editerror_office_rate"></span>
                                          </div>
                                          @endhasanyrole
                                          <div class="col-md-12 mb-3">
                                              <label class="form-label">
                                                  Name of the Guards
                                                  <small class="text-muted">(Include additional info such as Trained
                                                      Guards, Banned Guards)</small>
                                              </label>
                                              <textarea name="guard_names" id="guard_names" class="form-control" rows="3"
                                                  placeholder="Enter names and info of guards..."></textarea>
                                              <span class="text-danger form-error" id="editerror_guard_names"></span>
                                          </div>
                                          @hasanyrole('superadmin|admin')
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Expenses </label>
                                              <input type="text" name="billable_rate" id="billable_rate"
                                                  class="form-control numeric-input" placeholder="Billable">
                                              <span class="text-danger form-error"
                                                  id="editerror_billable_rate"></span>
                                          </div>
                                          <div class="col-md-6 mb-3">
                                              <label class="form-label">Expenses </label>
                                              <input type="text" name="payable_rate" id="payable_rate"
                                                  class="form-control numeric-input" placeholder="Payable">
                                              <span class="text-danger form-error" id="editerror_payable_rate"></span>
                                          </div>
                                          @endhasanyrole
                                          <div class="card bg-light-500 shadow-none">
                                              <div
                                                  class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                  <h6>Services types</h6>

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
                                                                  <input type="checkbox" class="form-check-input"
                                                                      name="employee_types[]"
                                                                      value="{{ $type->id }}">
                                                              </td>
                                                              <td>
                                                                  <div
                                                                      class="form-check form-check-md form-switch me-2">
                                                                      <label class="form-check-label mt-0">
                                                                          {{ $type->name }}
                                                                      </label>
                                                                  </div>
                                                              </td>
                                                              <td>
                                                                  <div
                                                                      class="form-check form-check-md d-flex align-items-center">
                                                                      <label class="form-check-label mt-0">
                                                                          <input type="text"
                                                                              class="form-controlnumeric-input"
                                                                              name="employee_guard_rate[{{ $type->id }}]"
                                                                              placeholder="Guard rate">
                                                                      </label>
                                                                  </div>
                                                              </td>
                                                              <td>
                                                                  <div
                                                                      class="form-check form-check-md d-flex align-items-center">
                                                                      <label class="form-check-label mt-0">
                                                                          <input type="text"
                                                                              class="form-control numeric-input"
                                                                              name="employee_office_rate[{{ $type->id }}]"
                                                                              placeholder="Office rate">
                                                                      </label>
                                                                  </div>
                                                              </td>
                                                          </tr>
                                                      @endforeach
                                                  </tbody>
                                              </table>
                                          </div>

                                      </div>
                                  </div>
                                  
                                  <div class="col-md-12 mt-3">
                                      <label class="form-label">Staff-specific Rates</label>
                                      <div class="d-flex gap-2 mb-2 align-items-center flex-wrap">
                                          <div class="me-2" style="flex:0 0 220px; min-width:160px;">
                                              <select id="site_staff_select" class="form-select staff-select2 w-100">
                                                  <option value="">--choose staff--</option>
                                              </select>
                                          </div>
                                          <div class="me-2" style="flex:0 0 140px;">
                                              <input type="text" id="site_staff_rate_input" class="form-control numeric-input" placeholder="Rate" style="width:140px;">
                                          </div>
                                          <div>
                                              <button type="button" id="add_site_staff_rate" class="btn btn-primary">Add</button>
                                          </div>
                                      </div>
                                      <div class="table-responsive">
                                          <table class="table table-sm table-bordered" id="site_staff_rates_table">
                                              <thead>
                                                  <tr>
                                                      <th>Staff</th>
                                                      <th style="width:140px">Rate</th>
                                                      <th style="width:120px">Action</th>
                                                  </tr>
                                              </thead>
                                              <tbody id="site_staff_rates_list"></tbody>
                                          </table>
                                      </div>
                                      <input type="hidden" name="staff_rates" id="staff_rates_input">
                                  </div>
                              </div>
                          </div>
                          <div class="modal-footer">
                              <button type="button" class="btn btn-outline-light border me-2"
                                  data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" form="edit_site-form" id="editsite"
                                  class="btn btn-primary">Update
                              </button>
                          </div>
                      </div>

                  </div>
              </form>
          </div>
      </div>
  </div>
