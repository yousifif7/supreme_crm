   <div class="modal fade" id="generate_invoice">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Generate Client Invoice</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="generate_invoice-form">
                        @csrf
                        <input type="hidden" name="client_id" id="invoice_client_id">
                        <div class="tab-content" id="myTabContentInvoice">
                            <div class="tab-pane fade show active" id="invoice-basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                   

                                                    <div class="mb-3">
                                                        <label class="form-label">Due Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="due_date" id="invoice_due_date"
                                                            class="form-control" placeholder="">
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_due_date"></span>
                                                    </div>

                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date From: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_from"
                                                                    id="invoice_date_from" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="invoiceerror_date_from"></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date To: <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date" name="date_to"
                                                                    id="invoice_date_to" class="form-control">
                                                                <span class="text-danger form-error"
                                                                    id="invoiceerror_date_to"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Notes <span
                                                                class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="notes" id="invoice_notes" rows="4"></textarea>
                                                        <span class="text-danger form-error"
                                                            id="invoiceerror_notes"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="generate_invoice-form" id="generateinvoice"
                                        class="btn btn-primary">Generate </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>