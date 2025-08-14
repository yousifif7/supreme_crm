   <div class="modal fade" id="edit_subcontractor">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Subcontractor</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <form method="POST" id="edit_subcontractor-form">
                        @csrf
                        <input type="hidden" name="subcontractor_id" id="subcontractor_id">

                        <div class="tab-content" id="subTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Company Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="company_name" id="company_name"
                                                            class="form-control" placeholder="Enter Company Name">
                                                        <span class="text-danger form-error"
                                                            id="editerror_company_name"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Company Address</label>
                                                        <textarea class="form-control" name="company_address" id="company_address" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="editerror_company_address"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number</label>
                                                                <input type="text" name="contact_number"
                                                                    id="contact_number" class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Person</label>
                                                                <input type="text" name="contact_person"
                                                                    id="contact_person" class="form-control"
                                                                    placeholder="Enter Contact Person">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_contact_person"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Email <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="email" name="email" id="email"
                                                                    class="form-control" placeholder="Enter Email">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_email"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Department <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="department" id="department"
                                                            class="form-control" placeholder="Enter Department">
                                                        <span class="text-danger form-error"
                                                            id="editerror_department"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Terms</label>
                                                        <input type="text" name="invoice_terms" id="invoice_terms"
                                                            class="form-control" placeholder="e.g. Weekly / Monthly">
                                                        <span class="text-danger form-error"
                                                            id="editerror_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms</label>
                                                        <textarea name="payment_terms" id="payment_terms" class="form-control" rows="2"
                                                            placeholder="Enter payment terms"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="editerror_payment_terms"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Pay Rate</label>
                                                        <input type="text" name="pay_rate" id="pay_rate"
                                                            class="form-control numeric-input"
                                                            placeholder="Enter Pay Rate">
                                                        <span class="text-danger form-error"
                                                            id="editerror_pay_rate"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-check mb-3">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="pmva_trained_officer" id="pmvaCheckEdit"
                                                                    value="1">
                                                                <label class="form-check-label" for="pmvaCheckEdit">PMVA
                                                                    Trained Officer</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center">
                                                        <div class="col-md-4">
                                                            <div class="form-check mb-3">
                                                                <input type="checkbox" name="vat_registered"
                                                                    class="form-check-input" id="vatCheckEdit"
                                                                    value="1">
                                                                <label class="form-check-label" for="vatCheckEdit">VAT
                                                                    Registered?</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <input type="text" name="vat_number" id="vat_number"
                                                                    class="form-control" placeholder="Enter VAT Number">
                                                                <span class="text-danger form-error"
                                                                    id="editerror_vat_number"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- col-md-6 -->
                                            </div> <!-- row -->
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary"
                                        id="updateSubcontractor">Update</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>