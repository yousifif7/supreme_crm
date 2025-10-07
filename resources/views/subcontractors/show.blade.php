<!-- Subcontractor Detail Modal -->
<div class="modal fade" id="viewSubcontractorDetailModal" tabindex="-1"
     aria-labelledby="subcontractorDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="subcontractorDetailLabel">
                    Subcontractor <span id="subcontractor_name_heading" class="fw-bold"></span> Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr><th>Contact Person</th><td id="contact_person_detail"></td></tr>
                        <tr><th>Company Name</th><td id="company_name_detail"></td></tr>
                        <tr><th>Address</th><td id="company_address_detail"></td></tr>
                        <tr><th>Contact Number</th><td id="contact_number_detail"></td></tr>
                        <tr><th>Email</th><td id="email_detail"></td></tr>
                        <tr><th>Username</th><td id="username_detail"></td></tr>
                        <tr><th>Invoice Terms</th><td id="invoice_terms_detail"></td></tr>
                        <tr><th>Payment Terms</th><td id="payment_terms_detail"></td></tr>
                        <tr><th>Department</th><td id="department_detail"></td></tr>
                        <tr><th>Pay Rate</th><td id="pay_rate_detail"></td></tr>
                        <tr><th>PMVA Trained Officer</th><td id="pmva_detail"></td></tr>
                        <tr><th>VAT Registered?</th><td id="vat_registered_detail"></td></tr>
                        <tr><th>VAT Number</th><td id="vat_number_detail"></td></tr>
                        <tr><th>Status</th><td id="status_detail"></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
