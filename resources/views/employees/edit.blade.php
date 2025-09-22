   <div class="modal fade" id="edit_employee">
       <div class="modal-dialog modal-dialog-scrollable modal-lg">
           <div class="modal-content">
               <div class="modal-header">
                   <h4 class="modal-title">Edit Employee</h4>
                   <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                       <i class="ti ti-x"></i>
                   </button>
               </div>
               <div class="modal-body pb-0 ">
                   <form method="POST" id="edit_employee_form" enctype="multipart/form-data">
                       @csrf
                       <input type="hidden" name="employee_id" id="employee_id">
                       {{-- <div class="row part-1">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div id="map1"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">

                                        </div>
                                    </div> --}}

                       <div class="row part-2">
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Forename <span class="text-danger">*</span></label>
                               <input type="text" name="fore_name" id="fore_name" class="form-control bg-yellow"
                                   placeholder="Enter Forename" required>
                               <span class="text-danger form-error" id="error_forename"></span>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Surname <span class="text-danger">*</span></label>
                               <input type="text" name="sur_name" id="sur_name" class="form-control bg-yellow"
                                   placeholder="Enter Surname" required>
                               <span class="text-danger form-error" id="error_surname"></span>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Employment Start Date</label>
                               <input type="date" name="employment_start_date" class="form-control bg-yellow"
                                   id="employment_start_date" placeholder="Employment start date">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Employment End Date</label>
                               <input type="date" name="employment_end_date" class="form-control bg-yellow"
                                   id="employment_end_date" placeholder="Employment end date">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Gender</label>
                               <select class="form-select bg-yellow" name="gender" id="gender">
                                   <option value="Male" selected>Male</option>
                                   <option value="Female">Female</option>
                               </select>
                               <span class="text-danger form-error" id="error_gender"></span>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label ">Email <span class="text-danger">*</span></label>
                               <input type="email" name="email" id="email" class="form-control bg-yellow"
                                   placeholder="Enter Email">
                               <span class="text-danger form-error" id="error_email"></span>
                           </div>
                           <div class="col-md-4 mb-3">
                               <div class="mb-3 ">
                                   <label class="form-label">Password <span class="text-danger">
                                           *</span></label>
                                   <div class="pass-group">
                                       <input type="password" name="password" class="pass-input form-control">
                                       <span class="ti toggle-password ti-eye-off"></span>
                                   </div>
                                   <span class="text-danger form-error" id="error_password"></span>
                               </div>
                           </div>
                           <div class="col-md-4 mb-3">
                               <div class="mb-3 ">
                                   <label class="form-label">Confirm Password <span class="text-danger">
                                           *</span></label>
                                   <div class="pass-group">
                                       <input type="password" name="password_confirmation"
                                           class="pass-inputs form-control">
                                       <span class="ti toggle-passwords ti-eye-off"></span>
                                   </div>
                               </div>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">N.I. Number</label>
                               <input type="text" name="ni_number" id="ni_number" class="form-control bg-yellow"
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
                               <input type="text" name="sia_licence" id="sia_licence"
                                   class="form-control bg-yellow" placeholder="Enter SIA Licence"> <span
                                   class="text-danger form-error" id="error_sia_licence"></span>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">SIA Expiry</label>
                               <input type="date" name="sia_expiry" id="sia_expiry"
                                   class="form-control bg-yellow" placeholder="Enter SIA Expiry">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Licence Type </label>
                               <select class="form-select bg-yellow" name="licence_type" id="licence_type">
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
                               <input type="date" name="entry_date" id="entry_date" class="form-control"
                                   placeholder="Enter Date of Entry / Re-entry">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">D.O.B</label>
                               <input type="date" name="dob" id="dob" class="form-control"
                                   placeholder="D.O.B">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Service Type</label>
                               <select class="form-select" name="service_type" id="service_type">
                                   <option selected value="Alarm Response">Alarm Response</option>
                                   <option value="Keyholding">Keyholding</option>
                                   <option value="Event Staff">Event Staff</option>
                                   <option value="Mobile Patrol">Mobile Patrol</option>
                                   <option value="Static Guards">Static Guards</option>
                               </select>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label">Visa Type</label>
                               <select class="form-select visa_type" name="visa_type" id="visa_type">
                                   <option value="">-- choose --</option>
                                   @foreach ($visa_types as $visa)
                                       <option value="{{ $visa->name }}">{{ $visa->name }}
                                       </option>
                                   @endforeach

                               </select>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="visa_expiry">Visa Expiry</label>
                               <input type="date" name="visa_expiry" id="visa_expiry" class="form-control"
                                   placeholder="Enter Visa Expiry">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="place_work">Place of Work</label>
                               <input type="text" name="place_work" id="place_work" class="form-control"
                                   placeholder="Place of Work">
                           </div>
                           <div class="terms-section-edit" style="display: none;">
                               <h5>Employee Terms</h5>
                               <div id="editterm-rows">
                                   <!-- Terms load here -->
                               </div>
                               <button type="button" id="editTermRow" class="btn btn-sm btn-primary my-3">+ Add
                                   Term</button>
                           </div>
                           <div class="clear-fix"></div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="hour_per_week">No of hours per week</label>
                               <input type="text" name="hour_per_week" id="hour_per_week" class="form-control"
                                   placeholder="Enter Hours">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="passport_no">Passport no.</label>
                               <input type="text" name="passport_no" id="passport_no" class="form-control"
                                   placeholder="Enter Passport no.">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="passport_expiry">Passport expiry</label>
                               <input type="date" name="passport_expiry" id="passport_expiry"
                                   class="form-control" placeholder="Enter Passport expiry">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="address_group">Address group</label>
                               <select class="form-select" name="address_group" id="address_group">
                                   <option selected>-- choose --</option>
                               </select>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="contact">Contact No:</label>
                               <input type="text" name="contact" id="contact" class="form-control"
                                   placeholder="Enter Contact No">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="emergency_contact">Emergency contact</label>
                               <input type="text" name="emergency_contact" id="emergency_contact"
                                   class="form-control" placeholder="Enter emergency contact no.">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="job_title">Job Title</label>
                               <input type="text" name="job_title" id="job_title" class="form-control"
                                   placeholder="Enter Job Title">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="nationality">Nationality</label>
                               <input type="text" name="nationality" id="nationality" class="form-control"
                                   placeholder="Enter nationality">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="next_kin">Next of Kin</label>
                               <input type="text" name="next_kin" id="next_kin" class="form-control"
                                   placeholder="Enter next of kin">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="relation_with_kin">Relationship</label>
                               <input type="text" name="relation_with_kin" id="relation_with_kin"
                                   class="form-control" placeholder="Enter Relationship">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="kin_address">Kin address</label>
                               <input type="text" name="kin_address" id="kin_address" class="form-control"
                                   placeholder="Enter kin address">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="kin_number">Next of Kin Contact No.</label>
                               <input type="text" name="kin_number" id="kin_number" class="form-control"
                                   placeholder="Enter Next of Kin Contact No.">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="kin_work_tel">Kin Work tel</label>
                               <input type="text" name="kin_work_tel" id="kin_work_tel" class="form-control"
                                   placeholder="Enter Work Tel">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="kin_mobile">Kin Mobile</label>
                               <input type="text" name="kin_mobile" id="kin_mobile" class="form-control"
                                   placeholder="Enter Kin Mobile">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="share_code">Share Code</label>
                               <input type="text" name="share_code" id="share_code" class="form-control"
                                   placeholder="Enter Share Code">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="share_code_expiry">Share Code Expiry</label>
                               <input type="date" name="share_code_expiry" id="share_code_expiry"
                                   class="form-control" placeholder="Enter Share Code Expiry">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="biometric_residence_permit">Biometric residence
                                   permit</label>
                               <input type="text" name="biometric_residence_permit"
                                   id="biometric_residence_permit" class="form-control"
                                   placeholder="Enter biometric residence permit">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="biometric_residence_permit_expiry">Biometric
                                   residence permit expiry</label>
                               <input type="date" name="biometric_residence_permit_expiry"
                                   id="biometric_residence_permit_expiry" class="form-control"
                                   placeholder="Enter biometric residence permit expiry">
                           </div>

                           {{-- <div class="col-md-4 mb-3">
                                            <label class="form-label" for="brp_status">BRP status</label>
                                            <select class="form-select" name="brp_status" id="brp_status1">
                                                <option value="Student Visa" selected>Student Visa</option>
                                                <option value="Dependent Visa">Dependent Visa</option>
                                                <option value="Refugee Status">Refugee Status</option>
                                                <option value="Applied For A New Visa">Applied For A New Visa</option>
                                                <option value="Skilled Worker Visa">Skilled Worker Visa</option>
                                                <option value="Other Visa">Other Visa</option>
                                            </select>
                                        </div> --}}

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="settlement">Settlement</label>
                               <input type="text" name="settlement" id="settlement" class="form-control"
                                   placeholder="Settlement">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="tags">Tags</label>
                               <textarea name="tags" id="tags" cols="30" rows="4" class="form-control">QA54ER</textarea>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="department">Department</label>
                               <select class="form-select" name="department_id" id="department_id">
                                   <option value="" selected>--choose--</option>
                                   @foreach ($departments as $department)
                                       <option value="{{ $department->id }}">{{ $department->name }}
                                       </option>
                                   @endforeach
                               </select>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="subcontractor">Subcontractor</label>
                               <select class="form-select" name="subcontractor" id="subcontractor">
                                   <option value="AWS SERVICES LTD">AWS SERVICES LTD</option>
                                   <option value="GOOD HANDS LTD">GOOD HANDS LTD</option>
                                   <option value="TOTAL PROTECTION SERVICES LTD">TOTAL PROTECTION SERVICES
                                       LTD
                                   </option>
                                   <option value="MASSEC PROTECT LTD">MASSEC PROTECT LTD</option>
                                   <option value="XL STRATEGY SERVICES LTD">XL STRATEGY SERVICES LTD</option>
                               </select>
                           </div>

                           <div class="col-md-4 mb-3 d-flex align-items-end">
                               <div class="form-check">
                                   <input class="mb-0 form-check-input" type="checkbox" id="isaCheck1">
                                   <label class="form-check-label" for="isaCheck1">Additional
                                       License</label>
                               </div>
                           </div>

                           <div id="additional-license-section" class="row">
                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="additional_sia_number">S.I.A
                                       License</label>
                                   <input type="text" name="additional_sia_number" id="additional_sia_number"
                                       class="form-control" placeholder="Enter S.I.A License">
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="license_type">License Type</label>
                                   <select class="form-select" name="license_type" id="license_type">
                                       <option value="">--choose--</option>
                                       @foreach ($licenses as $license)
                                           <option value="{{ $license->name }}">{{ $license->name }}
                                           </option>
                                       @endforeach
                                   </select>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="license_expiry">License Expiry</label>
                                   <input type="date" name="license_expiry" id="license_expiry"
                                       class="form-control">
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="dbs_confirmed">DBS confirmed</label>
                                   <input type="text" name="dbs_confirmed" id="dbs_confirmed"
                                       class="form-control" placeholder="Enter DBS confirmed">
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="address_group_additional">Address
                                       group</label>
                                   <select class="form-select" name="address_group_additional"
                                       id="address_group_additional">
                                       <option value="">--choose--</option>
                                   </select>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="profile_picture">Profile Picture</label>
                                   <input type="file" name="profile_picture" id="profile_picture"
                                       class="form-control">
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="employee_type">Employee Type</label>
                                   <select class="form-select" name="employee_type" id="employee_type">
                                       <option value="">--choose--</option>

                                       @foreach ($employee_types as $type)
                                           <option value="{{ $type->id }}">{{ $type->name }}</option>
                                       @endforeach
                                   </select>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label">Do you need visa to work or remain in the
                                       UK?</label>
                                   <div class="d-flex gap-3">
                                       <div>
                                           <input type="radio" name="visa_to_work" id="visa_to_work_yes"
                                               class="form-check-input mb-0" value="1">
                                           <label class="form-check-label" for="visa_to_work_yes">Yes</label>
                                       </div>
                                       <div>
                                           <input type="radio" name="visa_to_work" id="visa_to_work_no"
                                               class="form-check-input mb-0" value="0">
                                           <label class="form-check-label" for="visa_to_work_no">No</label>
                                       </div>
                                   </div>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label">A current driving license?</label>
                                   <div class="d-flex gap-3">
                                       <div>
                                           <input type="radio" name="driving_license" id="driving_license_yes"
                                               class="form-check-input mb-0" value="1">
                                           <label class="form-check-label" for="driving_license_yes">Yes</label>
                                       </div>
                                       <div>
                                           <input type="radio" name="driving_license" id="driving_license_no"
                                               class="form-check-input mb-0" value="0">
                                           <label class="form-check-label" for="driving_license_no">No</label>
                                       </div>
                                   </div>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="license_number">License Number</label>
                                   <input type="text" name="license_number" id="license_number1"
                                       class="form-control" placeholder="Enter License Number">
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label">Vehicle in use?</label>
                                   <div class="d-flex gap-3">
                                       <div>
                                           <input type="radio" name="vehicle_in_use" id="vehicle_in_use_yes"
                                               class="form-check-input mb-0" value="1">
                                           <label class="form-check-label" for="vehicle_in_use_yes">Yes</label>
                                       </div>
                                       <div>
                                           <input type="radio" name="vehicle_in_use" id="vehicle_in_use_no"
                                               class="form-check-input mb-0" value="0">
                                           <label class="form-check-label" for="vehicle_in_use_no">No</label>
                                       </div>
                                   </div>
                               </div>

                               <div class="col-md-4 mb-3">
                                   <label class="form-label" for="current_endorsement">Any current
                                       endorsement.
                                       If so please give details</label>
                                   <input type="text" name="current_endorsement" id="current_endorsement"
                                       class="form-control">
                               </div>
                           </div>

                           <h3 class="mt-2 mb-4">Documents</h3>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="sia_licence_file">SIA Licence</label>
                               <input type="file" name="sia_licence_file" accept=".jpg,.jpeg,.png,.pdf"
                                   id="sia_licence_file" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="passport_file">Passport_file</label>
                               <input type="file" name="passport_file" accept=".jpg,.jpeg,.png,.pdf"
                                   id="passport_file" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="proof_of_address_file">Proof of address</label>
                               <input type="file" name="proof_of_address_file" accept=".jpg,.jpeg,.png,.pdf"
                                   id="proof_of_address_file" class="form-control">
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
                                   id="ni_letter_file" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="first_aid_certificate_file">Right to work</label>
                               <input type="file" name="first_aid_certificate_file" accept=".jpg,.jpeg,.png,.pdf"
                                   id="first_aid_certificate_file" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="act_certificate_file">ACT certificate, Blue and
                                   Orange</label>
                               <input type="file" name="act_certificate_file" accept=".jpg,.jpeg,.png,.pdf"
                                   id="act_certificate_file" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="signature">Signature Of Applicant</label>
                               <input type="file" name="signature" id="signature" class="form-control">
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>


                           <h3 class="mt-2 mb-4">Additional Documents</h3>


                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="additional_files">Upload any addition
                                   documents</label>
                               <input type="file" name="additional_file[]" accept=".jpg,.jpeg,.png,.pdf"
                                   class="form-control" multiple>
                               <span class="text-default">Max File size 20MB and Allowed File Types (Jpeg, Jpg, Png,
                                   Pdf)</span>
                           </div>

                           <h3 class="mt-2 mb-4">Uniform Size</h3>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="collar">Collar</label>
                               <input type="text" name="collar" id="collar" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="waist">Waist</label>
                               <input type="text" name="waist" id="waist" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="jacket">Jacket</label>
                               <input type="text" name="jacket" id="jacket" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="shoe">Shoe</label>
                               <input type="text" name="shoe" id="shoe" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="inseam">Inseam</label>
                               <input type="text" name="inseam" id="inseam" class="form-control">
                           </div>

                           <h3 class="mt-2 mb-4">Payroll Information</h3>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="guard_rate">Guard Rate</label>
                               <input type="text" name="guard_rate" id="guard_rate1"
                                   placeholder="Enter Guard Rate" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="payment_period">Payment Period</label>
                               <select class="form-select" name="payment_period" id="payment_period">
                                   <option value="Fortnightly" selected>Fortnightly</option>
                               </select>
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="fixed_pay">Fixed Pay (if any)</label>
                               <input type="text" name="fixed_pay" id="fixed_pay"
                                   placeholder="Enter Fixed Pay (if any)" class="form-control">
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="account_name">Bank Account Name</label>
                               <input type="text" id="account_name" name="account_name"
                                   placeholder="Enter Bank Account Name" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="account_number">Bank Account Number</label>
                               <input type="text" id="account_number" name="account_number"
                                   placeholder="Enter Bank Account Number" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="sort_code">Bank Sort Code</label>
                               <input type="text" id="sort_code" name="sort_code"
                                   placeholder="Enter Bank Sort Code" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="bank_name">Bank Name</label>
                               <input type="text" id="bank_name" name="bank_name" placeholder="Enter Bank Name"
                                   class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="bank_branch">Bank Branch</label>
                               <input type="text" id="bank_branch" name="bank_branch"
                                   placeholder="Enter Bank Branch" class="form-control">
                           </div>

                           <div class="col-md-4 mb-3">
                               <label class="form-label" for="other_info">Other Information</label>
                               <textarea id="other_info" name="other_info" cols="30" rows="4" class="form-control"></textarea>
                           </div>

                           <div class="holidays-section-edit">
                               <h5>Employee Holidays</h5>
                               <div id="editholiday-rows">
                                   <!-- Holidays load here -->
                               </div>
                               <button type="button" id="editHolidayRow" class="btn btn-sm btn-primary my-3">+
                                   Add Holiday</button>
                           </div>

                       </div>
                   </form>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-outline-light border me-2"
                       data-bs-dismiss="modal">Cancel</button>
                   <button type="submit" id="editEmployeeBtn" class="btn btn-primary"
                       form="edit_employee_form">Update
                       Employee</button>
               </div>
           </div>
       </div>
   </div>
