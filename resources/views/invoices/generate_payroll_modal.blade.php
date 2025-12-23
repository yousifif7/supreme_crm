<div class="modal fade" id="generate_payroll_subcontractor_modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Generate Subcontractor Payroll</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form method="POST" id="generate_payroll_subcontractor_form">
                @csrf
                <div class="tab-content" id="myTabContentPayroll">
                    <div class="tab-pane fade show active" id="payroll-basic-info" role="tabpanel" tabindex="0">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Select Subcontractor <span class="text-danger">*</span></label>
                                        <select id="payroll_subcontractor_select" name="subcontractor_id" class="form-select select2_sub">
                                            <option value="">-- choose subcontractor --</option>
                                            @php
                                                $subs = \App\Models\User::role('subcontractor')->orderBy('first_name')->get();
                                            @endphp
                                            @foreach($subs as $sub)
                                                <option value="{{ $sub->id }}">{{ $sub->first_name }} {{ $sub->last_name }} ({{ $sub->email }})</option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger form-error" id="payroll_error_subcontractor"></span>
                                    </div>
                                </div>

                                <div class="col-md-12" id="payroll_employee_wrapper" style="display:none;">
                                    <div class="mb-3">
                                        <label class="form-label">Select Employee / Staff</label>
                                        <select id="payroll_employee_select" name="employee_id" class="form-select select2_employee">
                                            <option value="">-- choose employee (optional) --</option>
                                        </select>
                                        <span class="text-danger form-error" id="payroll_error_employee"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" id="payroll_date_from" class="form-control">
                                        <span class="text-danger form-error" id="payroll_error_date_from"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" id="payroll_date_to" class="form-control">
                                        <span class="text-danger form-error" id="payroll_error_date_to"></span>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" id="payroll_notes" rows="3"></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="generate_payroll_subcontractor_btn" class="btn btn-primary">Generate</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // initialize select2 for selects inside modal
        function initSelect2() {
            $('.select2_sub, .select2_employee').each(function() {
                const $el = $(this);
                if ($el.data('select2')) return;
                $el.select2({ dropdownParent: $('#generate_payroll_subcontractor_modal'), width: '100%' });
            });
        }

        initSelect2();

        // when a subcontractor is selected, load related employees/staff
        $('#payroll_subcontractor_select').on('change', function() {
            const subId = $(this).val();
            $('#payroll_error_subcontractor').text('');
            $('#payroll_employee_select').empty().append('<option value="">-- choose employee (optional) --</option>');

            if (!subId) {
                $('#payroll_employee_wrapper').hide();
                return;
            }

            $('#payroll_employee_wrapper').show();
            $('#payroll_employee_select').prop('disabled', true).append('<option>Loading...</option>');

            $.ajax({
                url: `${baseUrl}/subcontractor/${subId}/employees`,
                method: 'GET',
                success: function(res) {
                    $('#payroll_employee_select').empty().append('<option value="">-- choose employee (optional) --</option>');
                    const list = Array.isArray(res) ? res : (res.data || []);
                    if (list.length) {
                        list.forEach(function(emp) {
                            const text = emp.first_name ? (emp.first_name + ' ' + (emp.last_name || '') + (emp.email ? ' ('+emp.email+')' : '')) : (emp.name || emp.email || 'Staff');
                            $('#payroll_employee_select').append(`<option value="${emp.id}">${text}</option>`);
                        });
                    } else {
                        $('#payroll_employee_select').append('<option value="">No staff found</option>');
                    }
                },
                error: function() {
                    $('#payroll_employee_select').empty().append('<option value="">Failed to load staff</option>');
                },
                complete: function() {
                    $('#payroll_employee_select').prop('disabled', false).trigger('change.select2');
                    initSelect2();
                }
            });
        });

        $('#generate_payroll_subcontractor_form').on('submit', function(e) {
            e.preventDefault();

            const subId = $('#payroll_subcontractor_select').val();
            const dateFrom = $('#payroll_date_from').val();
            const dateTo = $('#payroll_date_to').val();
            const notes = $('#payroll_notes').val();
            const employeeId = $('#payroll_employee_select').val();

            $('.form-error').text('');

            if (!subId) {
                $('#payroll_error_subcontractor').text('Please select a subcontractor.');
                return;
            }
            if (!dateFrom) {
                $('#payroll_error_date_from').text('Please select a start date.');
                return;
            }
            if (!dateTo) {
                $('#payroll_error_date_to').text('Please select an end date.');
                return;
            }

            const url = `${baseUrl}/generatepayroll_subcontractor/${subId}`;
            $('#generate_payroll_subcontractor_btn').prop('disabled', true).text('Generating...');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    date_from: dateFrom,
                    date_to: dateTo,
                    notes: notes,
                    employee_id: employeeId
                },
                success: function(response) {
                    try {
                        const modalEl = document.getElementById('generate_payroll_subcontractor_modal');
                        if (modalEl && window.bootstrap && bootstrap.Modal) {
                            const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            inst.hide();
                        } else if (window.$ && $.fn && $.fn.modal) {
                            $('#generate_payroll_subcontractor_modal').modal('hide');
                        }
                    } catch (e) {
                        console.error('Error hiding subcontractor modal', e);
                    }
                    toast_success(response.message || 'Payroll generated');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        if (errors.date_from) $('#payroll_error_date_from').text(errors.date_from[0]);
                        if (errors.date_to) $('#payroll_error_date_to').text(errors.date_to[0]);
                        if (errors.subcontractor_id) $('#payroll_error_subcontractor').text(errors.subcontractor_id[0]);
                        if (errors.employee_id) $('#payroll_error_employee').text(errors.employee_id[0]);
                    } else if (xhr.responseJSON?.error) {
                        toast_danger(xhr.responseJSON.error);
                    } else {
                        toast_danger('Failed to generate payroll.');
                    }
                },
                complete: function() {
                    $('#generate_payroll_subcontractor_btn').prop('disabled', false).text('Generate');
                }
            });
        });
    });
</script>