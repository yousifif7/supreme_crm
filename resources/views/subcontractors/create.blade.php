  <div class="modal fade" id="add_subcontractor">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Subcontractor</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <form method="POST" id="add_subcontractor-form" action="{{ route('subcontractors.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="tab-content" id="subTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Company Name <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="company_name" class="form-control"
                                                            placeholder="Enter Company Name">
                                                        <span class="text-danger form-error"
                                                            id="error_company_name"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Company Address</label>
                                                        <textarea class="form-control" name="company_address" rows="2"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="error_company_address"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Number </label>
                                                                <input type="text" name="contact_number"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_number"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Person</label>
                                                                <input type="text" name="contact_person"
                                                                    class="form-control"
                                                                    placeholder="Enter Contact Person">
                                                                <span class="text-danger form-error"
                                                                    id="error_contact_person"></span>
                                                            </div>
                                                        </div>
                                                        {{--<div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Email <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="email" name="email" class="form-control"
                                                                    placeholder="Enter Email">
                                                                <span class="text-danger form-error"
                                                                    id="error_email"></span>
                                                            </div>
                                                        </div>--}}
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Department </label>
                                                        <input type="text" name="department" class="form-control"
                                                            placeholder="Enter Department">
                                                        <span class="text-danger form-error" id="error_department"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Terms</label>
                                                        <input type="text" name="invoice_terms" class="form-control"
                                                            placeholder="e.g. Weekly / Monthly">
                                                        <span class="text-danger form-error"
                                                            id="error_invoice_terms"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Terms</label>
                                                        <textarea name="payment_terms" class="form-control" rows="2" placeholder="Enter payment terms"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="error_payment_terms"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Email <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="text" name="email"
                                                                    class="form-control" placeholder="Email">
                                                                <span class="text-danger form-error"
                                                                    id="error_email"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Password <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="password" name="password"
                                                                    class="form-control" placeholder="••••••">
                                                                <span class="text-danger form-error"
                                                                    id="error_password"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Pay Rate</label>
                                                        <input type="text" name="pay_rate"
                                                            class="form-control numeric-input"
                                                            placeholder="Enter Pay Rate">
                                                        <span class="text-danger form-error" id="error_pay_rate"></span>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-check mb-3">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="pmva_trained_officer" id="pmvaCheck"
                                                                    value="1">
                                                                <label class="form-check-label" for="pmvaCheck">PMVA
                                                                    Trained Officer</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center">
                                                        <div class="col-md-4">
                                                            <div class="form-check mb-3">
                                                                <input type="checkbox" name="vat_registered"
                                                                    class="form-check-input" id="vatCheckSub"
                                                                    value="1">
                                                                <label class="form-check-label" for="vatCheckSub">VAT
                                                                    Registered?</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="mb-3" id="vatInputSub" style="display: none;">
                                                                <input type="text" name="vat_number"
                                                                    class="form-control" placeholder="Enter VAT Number">
                                                                <span class="text-danger form-error"
                                                                    id="error_vat_number"></span>
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
                                    <button type="submit" class="btn btn-primary" id="saveSubcontractor">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>