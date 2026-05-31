  <div class="modal fade" id="add_client">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title">Add New Client</h4>
                  <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                      <i class="ti ti-x"></i>
                  </button>
              </div>
              <form method="POST" id="add_client-form">
                  @csrf
                  <div class="tab-content" id="myTabContent">
                      <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
                          tabindex="0">
                          <div class="modal-body pb-0">
                              <div class="shift-wrapper">
                                  <div class="shift-group">
                                      <div class="row">

                                          <div class="col-md-6">
                                              <div class="mb-3">
                                                  <label class="form-label">Client Name <span
                                                          class="text-danger">*</span></label>
                                                  <input type="text" name="client_name" class="form-control"
                                                      placeholder="Enter Client Name">
                                                  <span class="text-danger form-error" id="error_client_name"></span>
                                              </div>
                                              <div class="mb-3">
                                                  <label class="form-label">Address </label>
                                                  <textarea class="form-control" name="address" rows="2"></textarea>
                                                  <span class="text-danger form-error" id="error_address"></span>
                                              </div>
                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contact Number</label>
                                                          <input type="text" name="contact_number"
                                                              class="form-control" placeholder="Enter Contact Number">
                                                          <span class="text-danger form-error"
                                                              id="error_contact_number"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contact Person </label>
                                                          <input type="text" name="contact_person"
                                                              class="form-control" placeholder="Enter Client Person">
                                                          <span class="text-danger form-error"
                                                              id="error_contact_person"></span>
                                                      </div>
                                                  </div>
                                                  {{-- <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email </label>
                                                                <input type="email" name="email" class="form-control"
                                                                    placeholder="Enter Contact Email">
                                                                <span class="text-danger form-error"
                                                                    id="error_email"></span>
                                                            </div>
                                                        </div> --}}
                                              </div>
                                              <div class="row">
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 1:</label>
                                                          <input type="file" name="doc_1" class="form-control">
                                                          <span class="text-danger form-error" id="error_doc_1"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 2:</label>
                                                          <input type="file" name="doc_2" class="form-control">
                                                          <span class="text-danger form-error" id="error_doc_2"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 3:</label>
                                                          <input type="file" name="doc_3" class="form-control">
                                                          <span class="text-danger form-error" id="error_doc_3"></span>
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="mb-3">
                                                  <label class="form-label">Company</label>
                                                  <select class="form-select" name="company_id">
                                                      <option value="">-- choose --</option>
                                                      @foreach ($companys as $company)
                                                          <option value="{{ $company->id }}">
                                                              {{ $company->company_name }}</option>
                                                      @endforeach
                                                  </select>
                                                  <span class="text-danger form-error" id="error_company_id"></span>
                                              </div>
                                          </div>

                                          <div class="col-md-6">
                                              <div class="row">
                                                  <div class="col-md-12">
                                                      <div class="mb-3">
                                                          <div class="form-label">Password <span
                                                                  class="text-danger">*</span></div>
                                                          <input type="password" name="password" class="form-control"
                                                              placeholder="••••••">
                                                          <span class="text-danger form-error"
                                                              id="error_password"></span>
                                                          <div id="passwordHelp" class="text-muted small">
                                                              Must be at least 8 characters, include uppercase,
                                                              lowercase, number, and special character.
                                                          </div>
                                                          <div class="text-muted small mt-1">
                                                              A login email will be auto-generated from the client name.
                                                          </div>
                                                      </div>
                                                  </div>

                                              </div>

                                              <div class="mb-3">
                                                  <label class="form-label">Invoice Terms</label>
                                                  <select class="form-select" name="invoice_terms">
                                                      <option value="">--choose--</option>
                                                      <option value="Fortnightly Invoice">Fortnightly Invoice
                                                      </option>
                                                      <option value="Weekly Invoice">Weekly Invoice
                                                      </option>
                                                      <option value="Monthly Invoice">Monthly Invoice
                                                      </option>
                                                  </select>
                                                  <span class="text-danger form-error"
                                                      id="error_invoice_terms"></span>
                                              </div>

                                              <div class="mb-3">
                                                  <label class="form-label">Payment Terms</label>
                                                  <textarea class="form-control" name="payment_terms" rows="2"></textarea>
                                                  <span class="text-danger form-error"
                                                      id="error_payment_terms"></span>
                                              </div>

                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contract Start: </label>
                                                          <input type="date" name="contract_start"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="error_contract_start"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contract End:</label>
                                                          <input type="date" name="contract_end"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="error_contract_end"></span>
                                                      </div>
                                                  </div>
                                              </div>

                                              @hasanyrole('superadmin|admin')
                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Guard Rate:</label>
                                                          <input type="text" name="guard_rate"
                                                              class="form-control numeric-input"
                                                              placeholder="Enter Guard rate">
                                                          <span class="text-danger form-error"
                                                              id="error_guard_rate"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Office Rate:</label>
                                                          <input type="text" name="office_rate"
                                                              class="form-control numeric-input"
                                                              placeholder="Enter Office rate">
                                                          <span class="text-danger form-error"
                                                              id="error_office_rate"></span>
                                                      </div>
                                                  </div>
                                              </div>
                                              @endhasanyrole

                                              <div class="row align-items-center justify-content-between">
                                                  <div class="col-md-3">
                                                      <div class="mb-3 form-check">
                                                          <input type="checkbox" name="vat_registered"
                                                              class="form-check-input" id="vatCheck">
                                                          <label class="form-check-label" for="vatCheck">VAT
                                                              Registered?</label>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-9">
                                                      <div class="mb-3" id="vatInput" style="display: none;">
                                                          <input type="text" name="vat" class="form-control"
                                                              placeholder="Enter VAT Registration Number">
                                                          <span class="text-danger form-error" id="error_vat"></span>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>

                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="modal-footer">
                              <button type="button" class="btn btn-light me-2"
                                  data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" form="add_client-form" id="saveclient"
                                  class="btn btn-primary">Save </button>
                          </div>
                      </div>
                  </div>


              </form>

          </div>
      </div>
  </div>
