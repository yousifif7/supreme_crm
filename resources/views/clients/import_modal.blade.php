<!-- Import modal -->
<div class="modal fade" id="import_modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Import Clients</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{ route('clients.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                        aria-labelledby="info-tab" tabindex="0">
                        <div class="modal-body pb-0 ">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info">
                                        <h6 class="mb-2"><i class="ti ti-info-circle"></i> Import Guidelines:</h6>
                                        <ul class="mb-0 small">
                                            <li>Row 1 and Column A should be left empty</li>
                                            <li>Row 2 starting from Column B should contain headers</li>
                                            <li>Data should start from Row 3, Column B onwards</li>
                                            <li><strong>Required:</strong> Name</li>
                                            <li><strong>Optional:</strong> Address, Contact Number, Contact Person, Contact Email, Username, Password, Invoice Terms, Payment Terms, Contract Start, Contract End, Company ID, Guard Rate, Office Rate, VAT, Manager ID</li>
                                            <li>If Username is provided, a user account will be created</li>
                                            <li>If Username is provided but Password is not, default password "password123" will be used</li>
                                            <li>Boolean fields (VAT) accept: Yes/No, True/False, 1/0, Active/Inactive</li>
                                            <li>Date fields should be in a recognizable format (YYYY-MM-DD, MM/DD/YYYY, etc.)</li>
                                            <li>Rate fields should be numeric values</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex gap-2">
                                        <input type="file" name="import_file" class="form-control" required accept=".xlsx,.xls,.csv">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <a href="{{ route('clients.export.excel', ['template' => 1]) }}" class="btn btn-outline-primary w-100">
                                        <i class="ti ti-download"></i> Download Template
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light border me-2"
                                data-bs-dismiss="modal">Cancel</button>

                            <button class="btn btn-primary" type="submit">Import</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
