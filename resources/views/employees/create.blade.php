  <div class="modal fade" id="add_employee">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title">Add New Security Staff</h4>
                  <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                      <i class="ti ti-x"></i>
                  </button>
              </div>
              <div class="modal-body pb-0 ">
                  <form method="POST" id="add_worker-form1">
                      @csrf
                      <div class="row part-1">
                          {{-- <div class="col-md-6">
                                            <div class="mb-3">
                                                <div id="map"></div>
                                            </div>
                                        </div> --}}
                          <div class="col-md-4 mb-3">
                              <div class="mb-3">
                                  <label class="form-label">Email <span class="text-danger">*</span></label>
                                  <input type="email" name="email" class="form-control" placeholder="Enter email">
                                  <span class="text-danger form-error" id="error_email"></span>

                              </div>
                          </div>
                          <div class="col-md-4 mb-3">
                              <div class="mb-3">
                                  <label class="form-label">Password <span class="text-danger">*</span></label>
                                  <input type="password" name="password" class="form-control"
                                      placeholder="Enter Password">
                                  <span class="text-danger form-error" id="error_password"></span>
                                  <div id="passwordHelp" class="text-muted small">
                                      Must be at least 8 characters, include uppercase, lowercase, number, and special
                                      character.
                                  </div>
                              </div>
                          </div>
                          <div class="col-md-4 mb-3">
                              <div class="mb-3">
                                  <label class="form-label">Status</label>
                                  <select class="form-select" name="status">
                                      <option value="Active" selected>Active</option>
                                      <option value="Terminated">Terminated</option>
                                      <option value="Need Approval">Need Approval</option>
                                  </select>
                                  <span class="text-danger form-error" id="error_status"></span>
                              </div>
                          </div>
                      </div>

                      <div class="row part-2">
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Forename <span class="text-danger">*</span></label>
                              <input type="text" name="fore_name" class="form-control bg-yellow"
                                  placeholder="Enter Forename">
                              <span class="text-danger form-error" id="error_fore_name"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Surname <span class="text-danger">*</span></label>
                              <input type="text" name="sur_name" class="form-control bg-yellow"
                                  placeholder="Enter Surname">
                              <span class="text-danger form-error" id="error_sur_name"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Employment Start Date</label>
                              <input type="date" name="employment_start_date" class="form-control bg-yellow"
                                  placeholder="Employment start date">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Employment End Date</label>
                              <input type="date" name="employment_end_date" class="form-control bg-yellow"
                                  placeholder="Employment end date">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Gender </label>
                              <select class="form-select bg-yellow" name="gender">
                                  <option value="Male" selected>Male</option>
                                  <option value="Female">Female</option>
                              </select>
                              <span class="text-danger form-error" id="error_gender"></span>
                          </div>
                          {{-- <div class="col-md-4 mb-3">
                                            <label class="form-label ">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control bg-yellow"
                                                placeholder="Enter Email">
                                            <span class="text-danger form-error" id="error_email"></span>
                                        </div> --}}
                          <div class="col-md-4 mb-3">
                              <label class="form-label">N.I. Number </label>
                              <input type="text" name="ni_number" class="form-control bg-yellow"
                                  placeholder="Enter N.I. Number">
                              <span class="text-danger form-error" id="error_ni_number"></span>
                          </div>
                          <div class="col-md-4 mb-3 d-flex align-items-end">
                              <div class="form-check">
                                  <input class="form-check-input" type="checkbox" id="isaCheck">
                                  <label class="form-check-label text-danger" for="isaCheck">SIA not
                                      required</label>

                              </div>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">SIA Licence</label>
                              <input type="text" name="sia_licence" class="form-control bg-yellow"
                                  placeholder="Enter SIA Licence"> <span class="text-danger form-error"
                                  id="error_sia_licence"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">SIA Expiry </label>
                              <input type="date" name="sia_expiry" class="form-control bg-yellow"
                                  placeholder="Enter SIA Expiry">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Licence Type </label>
                              <select class="form-select bg-yellow" name="licence_type">
                                  <option value="">--choose--</option>
                                  @foreach ($licenses as $license)
                                      <option value="{{ $license->name }}">{{ $license->name }}</option>
                                  @endforeach
                              </select>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Driving Licence Number</label>
                              <input type="text" name="driving_licence_number" class="form-control bg-yellow"
                                  placeholder="Enter Driving Licence"> <span class="text-danger form-error"
                                  id="error_sia_licence"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Driving Licence Expiry</label>
                              <input type="date" name="driving_licence_expiry" class="form-control bg-yellow"
                                  placeholder="Enter driving Expiry">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Date of Entry / Re-entry</label>
                              <input type="date" name="entry_date" class="form-control"
                                  placeholder="Enter Date of Entry / Re-entry">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">D.O.B </label>
                              <input type="date" name="dob" class="form-control" placeholder="D.O.B">
                              <span class="text-danger form-error" id="error_dob"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Service Type</label>
                              <select class="form-select" name="service_type">
                                  <option value="">--choose--</option>
                                  @foreach ($employee_types as $type)
                                      <option value="{{ $type->id }}">{{ $type->name }}</option>
                                  @endforeach
                              </select>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Visa Type</label>
                              <select class="form-select visa_type" name="visa_type">
                                  <option value="">-- choose --</option>
                                  @foreach ($visa_types as $visa)
                                      <option value="{{ $visa->name }}">{{ $visa->name }}
                                      </option>
                                  @endforeach
                              </select>
                              <span class="text-danger form-error" id="error_visa_type"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Visa Expiry</label>
                              <input type="date" name="visa_expiry" class="form-control"
                                  placeholder="Enter Visa Expiry">
                              <span class="text-danger form-error" id="error_visa_expiry"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Place of Work</label>
                              <input type="text" name="place_work" class="form-control"
                                  placeholder="Place of Work">
                              <span class="text-danger form-error" id="error_place_work"></span>
                          </div>
                          <div class="terms-section" style="display: none;">
                              <h5>Employee Terms</h5>
                              <div id="term-rows">
                                  <!-- Dynamic rows will be added here -->
                              </div>
                              <button type="button" class="btn btn-sm btn-primary my-3" id="addTermRow">+ Add
                                  Terms</button>
                          </div>
                          <div class="clear-fix"></div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">No of hours per week</label>
                              <input type="text" name="hour_per_week" class="form-control"
                                  placeholder="Enter Hours"><span class="text-danger form-error"
                                  id="error_hour_per_week"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Passport no.</label>
                              <input type="text" name="passport_no" class="form-control"
                                  placeholder="Enter Passport no.">
                              <span class="text-danger form-error" id="error_passport_no"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Passport expiry</label>
                              <input type="date" name="passport_expiry" class="form-control"
                                  placeholder="Enter Passport expiry">
                              <span class="text-danger form-error" id="error_passport_expiry"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Address group</label>
                              <select class="form-select" name="address_group">
                                  <option selected value="">-- choose --</option>
                              </select>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Contact No:</label>
                              <input type="text" name="contact" class="form-control"
                                  placeholder="Enter Contact No">
                              <span class="text-danger form-error" id="error_contact_no"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Emergency contact</label>
                              <input type="text" name="emergency_contact" class="form-control"
                                  placeholder="Enter emergency contact no.">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Job Title</label>
                              <input type="text" name="job_title" class="form-control"
                                  placeholder="Enter Job Title">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Nationality</label>
                              <input type="text" name="nationality" class="form-control"
                                  placeholder="Enter nationality">
                              <span class="text-danger form-error" id="error_nationality"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Next of Kin</label>
                              <input type="text" name="next_kin" class="form-control"
                                  placeholder="Enter next of kin">
                              <span class="text-danger form-error" id="error_next_kin"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Relationship</label>
                              <input type="text" name="relation_with_kin" class="form-control"
                                  placeholder="Enter Relationship">
                              <span class="text-danger form-error" id="error_relation_with_kin"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Kin address</label>
                              <input type="text" name="kin_address" class="form-control"
                                  placeholder="Enter kin address">
                              <span class="text-danger form-error" id="error_kin_address"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Next of Kin Contact No.</label>
                              <input type="text" name="kin_number" class="form-control"
                                  placeholder="Enter Next of Kin Contact No.">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Kin Work tel</label>
                              <input type="text" name="kin_work_tel" class="form-control"
                                  placeholder="Enter Work Tel">
                              <span class="text-danger form-error" id="error_kin_work_tel"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Kin Mobile</label>
                              <input type="text" name="kin_mobile" class="form-control"
                                  placeholder="Enter Kin Mobile">
                              <span class="text-danger form-error" id="error_kin_mobile"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Share code</label>
                              <input type="text" name="share_code" class="form-control"
                                  placeholder="Enter share code">
                              <span class="text-danger form-error" id="error_share_code"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Share Code Expiry</label>
                              <input type="date" name="share_code_expiry" class="form-control">
                              <span class="text-danger form-error" id="error_share_code_expiry"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Biometric residence permit</label>
                              <input type="text" name="biometric_residence_permit" class="form-control"
                                  placeholder="Enter biometric residence permit">
                              <span class="text-danger form-error" id="error_biometric_residence_permit"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Biometric residence permit expiry</label>
                              <input type="date" name="biometric_residence_permit_expiry" class="form-control"
                                  placeholder="Enter biometric residence permit expiry">
                              <span class="text-danger form-error"
                                  id="error_biometric_residence_permit_expiry"></span>
                          </div>
                          {{-- <div class="col-md-4 mb-3">
                                            <label class="form-label">BRP status</label>
                                            <select class="form-select" name="brp_status">
                                                <option value="Student Visa" selected>Student Visa</option>
                                                <option value="Dependent Visa">Dependent Visa</option>
                                                <option value="Refugee Status">Refugee Status</option>
                                                <option value="Applied For A New Visa">Applied For A New Visa</option>
                                                <option value="Skilled Worker Visa">Skilled Worker Visa</option>
                                                <option value="Other Visa">Other Visa</option>
                                            </select>
                                            <span class="text-danger form-error" id="error_brp_status"></span>
                                        </div> --}}
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Settlement</label>
                              <input type="text" name="settlement" class="form-control" placeholder="Settlement">
                              <span class="text-danger form-error" id="error_settlement"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Tags</label>
                              <textarea name="tags" id="" cols="30" rows="4" class="form-control">QA54ER</textarea>
                              <span class="text-danger form-error" id="error_tags"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Department</label>
                              <select class="form-select" name="department_id">
                                  <option value="" selected>--choose--</option>
                                  @foreach ($departments as $department)
                                      <option value="{{ $department->id }}">{{ $department->name }}
                                      </option>
                                  @endforeach
                              </select>
                              <span class="text-danger form-error" id="error_department_id"></span>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Subcontractor</label>
                              <select class="form-select" name="subcontractor">
                                  @foreach ($subcontractors as $subcontractor)
                                      <option value="{{ $subcontractor->id }}">
                                          {{ $subcontractor->name }}</option>
                                  @endforeach
                              </select>
                              <span class="text-danger form-error" id="error_subcontractor"></span>
                          </div>
                          <div class="col-md-4 mb-3 d-flex align-items-end">
                              <div class="form-check">
                                  <input class="mb-0 form-check-input" type="checkbox" id="isaCheck1">
                                  <label class="form-check-label " for="isaCheck">Additional License
                                  </label>
                              </div>
                          </div>
                          <div id="additional-license-section" style="display: none;" class="row">
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">S.I.A License</label>
                                  <input type="text" name="additional_sia_number" class="form-control"
                                      placeholder="Enter S.I.A License">
                                  <span class="text-danger form-error" id="error_additional_sia_number"></span>
                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">License Type</label>
                                  <select class="form-select" name="license_type">
                                      <option value="">--choose--</option>
                                      @foreach ($licenses as $license)
                                          <option value="{{ $license->name }}">{{ $license->name }}
                                          </option>
                                      @endforeach
                                  </select>
                                  <span class="text-danger form-error" id="error_license_type"></span>
                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">License Expiry</label>
                                  <input type="date" name="license_expiry" class="form-control">
                                  <span class="text-danger form-error" id="error_license_expiry"></span>
                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">DBS confirmed</label>
                                  <input type="text" name="dbs_confirmed" class="form-control"
                                      placeholder="Enter DBS confirmed">

                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Address group</label>
                                  <select class="form-select" name="address_group_additional">
                                      <option value="">--choose--</option>
                                  </select>
                                  <span class="text-danger form-error" id="error_address_group_additional"></span>
                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Profile Picture</label>
                                  <input type="file" name="profile_picture" class="form-control">
                                  <span class="text-danger form-error" id="error_profile_picture"></span>

                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Staff Type</label>
                                  <select class="form-select" name="employee_type">
                                      <option value="">--choose--</option>
                                      @foreach ($employee_types as $type)
                                          <option value="{{ $type->id }}">{{ $type->name }}</option>
                                      @endforeach
                                  </select>
                                  <span class="text-danger form-error" id="error_employee_type"></span>


                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Do you need visa to work or remain in the
                                      UK?</label>
                                  <div class="d-flex gap-3">
                                      <div>
                                          <input type="radio" name="visa_to_work" class="form-check-input mb-0"
                                              value="1">
                                          <label class="form-check-label">Yes</label>
                                      </div>
                                      <div>
                                          <input type="radio" name="visa_to_work" class="form-check-input mb-0"
                                              value="0">
                                          <label class="form-check-label">No</label>
                                      </div>
                                  </div>


                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">A current driving license?</label>
                                  <div class="d-flex gap-3">
                                      <div>
                                          <input type="radio" name="driving_license" class="form-check-input mb-0"
                                              value="1">
                                          <label class="form-check-label">Yes</label>
                                      </div>
                                      <div>
                                          <input type="radio" name="driving_license" class="form-check-input mb-0"
                                              value="0">
                                          <label class="form-check-label">No</label>
                                      </div>
                                  </div>


                              </div>
                              <div class="col-md-4 mb-3">
                                  <label for="" class="form-label">
                                      License Number
                                  </label>
                                  <input type="text" name="license_number" class="form-control"
                                      placeholder="Enter License Number">
                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Vehicle in use?</label>
                                  <div class="d-flex gap-3">
                                      <div>
                                          <input type="radio" name="vehicle_in_use" class="form-check-input mb-0"
                                              value="1">
                                          <label class="form-check-label">Yes</label>
                                      </div>
                                      <div>
                                          <input type="radio" name="vehicle_in_use" class="form-check-input mb-0"
                                              value="0">
                                          <label class="form-check-label">No</label>
                                      </div>
                                  </div>


                              </div>
                              <div class="col-md-4 mb-3">
                                  <label class="form-label">Any current endorsement. If so please give
                                      details</label>
                                  <input type="text" name="current_endorsement" class="form-control">
                              </div>
                          </div>

                          <h3 class="mt-2 mb-4">Documents</h3>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="sia_licence_file">SIA Licence</label>
                              <input type="file" name="sia_licence_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="passport_file">Passport</label>
                              <input type="file" name="passport_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="proof_of_address_file">Proof of address</label>
                              <input type="file" name="proof_of_address_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="driving_licence_file">Driving Licence File</label>
                              <input type="file" name="driving_licence_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="ni_letter_file">Ni letter</label>
                              <input type="file" name="ni_letter_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="first_aid_certificate_file">Right to work</label>
                              <input type="file" name="first_aid_certificate_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="act_certificate_file">ACT certificate, Blue and
                                  Orange</label>
                              <input type="file" name="act_certificate_file" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <div class="col-md-4 mb-3">
                              <label class="form-label">Signature Of Applicant</label>
                              <input type="file" name="signature" class="form-control">
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <h3 class="mt-2 mb-4">Additional Documents</h3>


                          <div class="col-md-4 mb-3">
                              <label class="form-label" for="additional_files">Upload any addition documents</label>
                              <input type="file" name="additional_file[]" accept=".jpg,.jpeg,.png,.pdf"
                                  class="form-control" multiple>
                              <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                  Pdf)</span>
                          </div>

                          <h3 class="mt-2 mb-4">Uniform Size</h3>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Collar</label>
                              <input type="text" name="collar" class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Waist</label>
                              <input type="text" name="waist" class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Jacket</label>
                              <input type="text" name="jacket" class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Shoe</label>
                              <input type="text" name="shoe" class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Inseam</label>
                              <input type="text" name="inseam" class="form-control">
                          </div>
                          <h3 class="mt-2 mb-4">Payroll Information</h3>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Guard Rate</label>
                              <input type="text" name="guard_rate" placeholder="Enter Guard Rate"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Payment Period</label>
                              <select class="form-select" name="payment_period">
                                  <option value="Fortnightly" selected>Fortnightly</option>
                              </select>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Fixed Pay (if any)</label>
                              <input type="text" name="fixed_pay" placeholder="Enter Fixed Pay (if any)"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Bank Account Name</label>
                              <input type="text" name="account_name" placeholder="Enter Bank Account Name"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Bank Account Number</label>
                              <input type="text" name="account_number" placeholder="Enter Bank Account Number"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Bank Sort Code</label>
                              <input type="text" name="sort_code" placeholder="Enter Bank Sort Code"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Bank Name</label>
                              <input type="text" name="bank_name" placeholder="Enter Bank Name "
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Bank Branch</label>
                              <input type="text" name="bank_branch" placeholder="Enter Bank Branch"
                                  class="form-control">
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Other Information</label>
                              <textarea name="other_info" id="" cols="30" rows="4" class="form-control"></textarea>
                          </div>

                          <div class="holidays-section">
                              <h5>Employee Holidays</h5>
                              <div id="holiday-rows">
                                  <!-- Dynamic rows will be added here -->
                              </div>
                              <button type="button" class="btn btn-sm btn-primary my-3" id="addHolidayRow">+ Add
                                  Holiday</button>
                          </div>

                      </div>
                  </form>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-outline-light border me-2"
                      data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" form="add_worker-form1" id="saveemployee" class="btn btn-primary">Save
                  </button>
              </div>
          </div>
      </div>
  </div>
