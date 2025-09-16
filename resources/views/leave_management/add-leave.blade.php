        <div class="modal fade" id="add_leave">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Add New Leave</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data"
                        id="add_leave_form">
                        @csrf
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="row">
                                        <!-- Leave Fields -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Details <span class="text-danger">*</span></label>
                                                <input type="text" name="reason" class="form-control">
                                                <span class="text-danger form-error" id="error_leave_entitlement"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date From <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="start_date" class="form-control">
                                                <span class="text-danger form-error" id="error_from_date"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date To <span class="text-danger">*</span></label>
                                                <input type="date" name="end_date" class="form-control">
                                                <span class="text-danger form-error" id="error_to_date"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Employee</label>
                                                <select name="employee_id" class="form-control select2_modal">
                                                    <option value="">Select Employee</option>
                                                    @foreach ($employees as $key => $employee)
                                                        <option value="{{ $key }}"
                                                            {{ $key == 'applied' ? 'selected' : '' }}>{{ $employee }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Leave Type<span
                                                        class="text-danger">*</span></label>
                                                <select name="type" class="form-control">
                                                    <option value="">----select----</option>
                                                    <option value="sick_leave">Sick Leave (SSP)</option>
                                                    <option value="annual_holiday">Annual Holiday</option>
                                                    <option value="unpaid_leave">Unpaid Leave</option>
                                                    <option value="other_leave">Other Leave</option>
                                                </select>
                                                <span class="text-danger form-error" id="error_to_date"></span>
                                            </div>
                                        </div>
                                        {{-- <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control" disabled>
                                                    <option value="">Select Status</option>
                                                    @foreach ($status as $key => $st)
                                                        <option value="{{ $key }}" {{ $key == 'applied' ? 'selected' : ''}} >{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-danger form-error" id="error_role"></span>
                                            </div>
                                        </div> --}}

                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-light border me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="add_leave_form" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>