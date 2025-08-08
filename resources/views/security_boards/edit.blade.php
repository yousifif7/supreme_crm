<!-- Edit Shift -->
        <div class="modal fade" id="edit_shift">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Shift</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form method="POST" id="edit_shift-form">
                        @csrf
                        <input type="hidden" name="shift_id" id="shift_id">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                aria-labelledby="info-tab" tabindex="0">
                                <div class="modal-body pb-0">
                                    <div class="shift-wrapper">
                                        <div class="shift-group">
                                            <div class="row">
                                               
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Staff <span
                                                                class="text-danger">*</span></label>
                                                        <select name="staff_id"
                                                            class="form-select select2_modal" id="staff_id" required>
                                                            <option value="">--choose--</option>
                                                            @foreach ($staffs as $staff)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_client_id"></span>
                                                    </div>
                                                </div>

                                                {{--<div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Site <span
                                                                class="text-danger">*</span></label>
                                                        <select name="site_id" id="site_id" class="form-select select2"
                                                            readonly>
                                                            <option value="">--choose--</option>
                                                            @foreach ($sites as $site)
                                                                <option value="{{ $staff->id }}">
                                                                    {{ $site->site_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="text-danger form-error" id="error_site_id"></span>
                                                    </div>
                                                </div>--}}
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shift Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" name="shift_date" id="shift_date"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_shift_date_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Start <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="start_shift" id="start_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error"
                                                            id="error_start_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">End <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="end_shift" id="end_shift"
                                                            class="form-control">
                                                        <span class="text-danger form-error" id="error_end_shift"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Book on <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="book_on" id="book_on"
                                                            class="form-control" value="{{ date('h:i') }}">
                                                        <span class="text-danger form-error" id="error_book_on"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Book off <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" name="book_off" id="book_off"
                                                            class="form-control" value="{{ date('h:i') }}">
                                                        <span class="text-danger form-error" id="error_book_off"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status </label>
                                                       <select class="form-select" id="status_id" name="status_id">
        <option value="">--choose--</option>
        @php
            $statusLabels = [
                0 => 'Pending',
                1 => 'Dispatched',
                2 => 'Accepted',
                3 => 'Started',
                4 => 'Ended',
                5 => 'Rejected',
                6 => 'Cancelled',
                7 => 'Pre-start',
                8 => 'Await-finish',
            ];
        @endphp
        @foreach ($statusLabels as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </select>
                                                        <span class="text-danger form-error" id="error_status_id"></span>
                                                    </div>
                                                </div>
                                            </div> <!-- .row -->
                                        </div> <!-- .shift-group -->
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" form="edit_shift-form" id="editshift"
                                        class="btn btn-primary">Update </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>

                    function editShift(record_id) {
            $.get(`${baseUrl}/editshift/` + record_id, function(data) {
                if (data.shift) {
                    $('#shift_id').val(record_id);
                    $('#staff_id').val(data.shift.staff_id).trigger('change');
                    $('#shift_date').val(data.shift.shift_date);
                    
                    $('#start_shift').val(data.shift.start_time);
                    $('#end_shift').val(data.shift.end_time);

                    if(typeof data.shift.absentee_start_time != 'undefined')
                        $('#book_on').val(data.shift.absentee_start_time);
                    if(typeof data.shift.absentee_end_time != 'undefined')
                        $('#book_off').val(data.shift.absentee_end_time);
$('#status_id').val(data.shift.is_assign);

                    // ✅ Show Modal
                    $('#edit_shift').modal('show');
                }
            });
        }
              $('#edit_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#editshift'); // Your submit button should have this ID

                // Get the client ID from a hidden input field
                let shiftId = $(this).find('#shift_id').val();

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Updating...');

                $.ajax({
                    url: `${baseUrl}/updateshift/${shiftId}`, // OR use Laravel Blade: `{{ url('sites') }}/` + siteId
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#edit_shift');
                        toast_success('Shift Updated Successfully!');
                        reloadDatatable('#shifts-table');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else {
                            toast_danger('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Update');
                    }
                });
            });
        </script>