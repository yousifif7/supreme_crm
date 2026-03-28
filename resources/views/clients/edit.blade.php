  <div class="modal fade" id="edit_client">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title">Edit Client</h4>
                  <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                      <i class="ti ti-x"></i>
                  </button>
              </div>
              <form method="POST" id="edit_client-form">
                  @csrf
                  <input type="hidden" name="client_id" id="client_id">
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
                                                  <input type="text" name="client_name" id="client_name"
                                                      class="form-control" placeholder="Enter Client Name">
                                                  <span class="text-danger form-error"
                                                      id="editerror_client_name"></span>
                                              </div>
                                              <div class="mb-3">
                                                  <label class="form-label">Address </label>
                                                  <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                                                  <span class="text-danger form-error" id="editerror_address"></span>
                                              </div>
                                              <div class="row">
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contact Number </label>
                                                          <input type="text" name="contact_number"
                                                              id="contact_number" class="form-control"
                                                              placeholder="Enter Contact Number">
                                                          <span class="text-danger form-error"
                                                              id="editerror_contact_number"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">contact person</label>
                                                          <input type="text" name="contact_person"
                                                              id="contact_person" class="form-control"
                                                              placeholder="Enter Client Person">
                                                          <span class="text-danger form-error"
                                                              id="editerror_contact_person"></span>
                                                      </div>
                                                  </div>
                                                  {{-- <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contact Email</label>
                                                          <input type="email" name="email" id="email"
                                                              class="form-control" placeholder="Enter Contact Email">
                                                          <span class="text-danger form-error"
                                                              id="editerror_email"></span>
                                                      </div>
                                                  </div> --}}
                                              </div>

                                              <!-- Document Previews -->
                                              <div class="row">
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 1:</label>
                                                          <input type="file" name="doc_1" id="doc_1"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="editerror_doc_1"></span>
                                                          <div id="doc_1_preview"></div> <!-- Document Preview -->
                                                      </div>
                                                  </div>
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 2:</label>
                                                          <input type="file" name="doc_2" id="doc_2"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="editerror_doc_2"></span>
                                                          <div id="doc_2_preview"></div> <!-- Document Preview -->
                                                      </div>
                                                  </div>
                                                  <div class="col-md-4">
                                                      <div class="mb-3">
                                                          <label class="form-label">Doc 3:</label>
                                                          <input type="file" name="doc_3" id="doc_3"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="editerror_doc_3"></span>
                                                          <div id="doc_3_preview"></div> <!-- Document Preview -->
                                                      </div>
                                                  </div>
                                              </div>

                                              <!-- Other Fields -->
                                              <div class="mb-3">
                                                  <label class="form-label">Company</label>
                                                  <select class="form-select" name="company_id" id="company_id">
                                                      <option value="">-- choose --</option>
                                                      @foreach ($companys as $company)
                                                          <option value="{{ $company->id }}">
                                                              {{ $company->company_name }}</option>
                                                      @endforeach
                                                  </select>
                                                  <span class="text-danger form-error"
                                                      id="editerror_company_id"></span>
                                              </div>
                                          </div>

                                          <div class="col-md-6">
                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <div class="form-label">Email <span
                                                                  class="text-danger">*</span>
                                                          </div>
                                                          <input type="email" name="email" id="email"
                                                              class="form-control" placeholder="Email">
                                                          <span class="text-danger form-error"
                                                              id="error_email"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
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
                                                      </div>

                                                      {{-- Open Client system button (visible to admin/superadmin) --}}
                                                      @hasanyrole('admin|superadmin')
                                                      <div class="mb-3" id="open-client-system-container" style="display:none;">
                                                          <a href="#" id="open_client_system" target="_blank" class="btn btn-primary w-100">Open Client system</a>
                                                      </div>
                                                      @endhasanyrole
                                                  </div>
                                              </div>
                                              <div class="mb-3">
                                                  <label class="form-label">invoice terms </label>
                                                  <select class="form-select" name="invoice_terms"
                                                      id="invoice_terms">
                                                      <option value="">--choose--</option>
                                                      <option value="Fortnightly Invoice">Fortnightly Invoice
                                                      </option>
                                                      <option value="Weekly Invoice">Weekly Invoice
                                                      </option>
                                                      <option value="Monthly Invoice">Monthly Invoice
                                                      </option>
                                                  </select>
                                                  <span class="text-danger form-error"
                                                      id="editerror_invoice_terms"></span>
                                              </div>

                                              <div class="mb-3">
                                                  <label class="form-label">Payment Terms </label>
                                                  <textarea class="form-control" name="payment_terms" id="payment_terms" rows="2"></textarea>
                                                  <span class="text-danger form-error"
                                                      id="editerror_payment_terms"></span>
                                              </div>

                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contract Start: </label>
                                                          <input type="date" name="contract_start"
                                                              id="contract_start" class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="editerror_contract_start"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Contract End: </label>
                                                          <input type="date" name="contract_end" id="contract_end"
                                                              class="form-control">
                                                          <span class="text-danger form-error"
                                                              id="editerror_contract_end"></span>
                                                      </div>
                                                  </div>
                                              </div>
                                              @hasanyrole('superadmin|admin')
                                              <div class="row">
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Guard rate:</label>
                                                          <input type="text" name="guard_rate" id="guard_rate"
                                                              class="form-control numeric-input"
                                                              placeholder="Enter Guard rate">
                                                          <span class="text-danger form-error"
                                                              id="editerror_guard_rate"></span>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-6">
                                                      <div class="mb-3">
                                                          <label class="form-label">Office rate: </label>
                                                          <input type="text" name="office_rate" id="office_rate"
                                                              class="form-control numeric-input"
                                                              placeholder="Enter Office rate">
                                                          <span class="text-danger form-error"
                                                              id="editerror_office_rate"></span>
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
                                                      <div class="mb-3">
                                                          <input type="text" name="vat" id="vat"
                                                              class="form-control"
                                                              placeholder="Enter VAT Registration Number">
                                                          <span class="text-danger form-error"
                                                              id="editerror_vat"></span>
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
                              <button type="submit" form="edit_client-form" id="editclient"
                                  class="btn btn-primary">Update </button>
                          </div>
                      </div>
                  </div>
              </form>
          </div>
      </div>
  </div>

  @push('scripts')
  <script>
      // When the edit modal is shown, set the Open Client system link to the admin client view for the selected client
      document.addEventListener('DOMContentLoaded', function () {
          var editModal = document.getElementById('edit_client');
          if (!editModal) return;

          $('#edit_client').on('shown.bs.modal', function () {
              var id = document.getElementById('client_id').value;
              if (!id) {
                  document.getElementById('open-client-system-container') && (document.getElementById('open-client-system-container').style.display = 'none');
                  return;
              }

              // Build impersonation start URL: /impersonate/{clientId}
              var impersonateUrl = "{{ url('/impersonate') }}" + '/' + id;
              var link = document.getElementById('open_client_system');
              if (link) {
                  link.setAttribute('href', impersonateUrl);
                  document.getElementById('open-client-system-container').style.display = '';
              }
          });
      });
  </script>
  @endpush
