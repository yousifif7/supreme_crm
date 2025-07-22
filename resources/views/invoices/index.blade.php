@extends('layouts.app')
@section('title', 'CRM - Invoices')
@section('contents')
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Invoices</h2>

                </div>

            </div>
            <div class="d-flex my-xl-auto justify-content-between align-items-center flex-wrap ">
                <div class="me-2">
                    <div class="dropdown">
                        <button class="btn btn-primary" id="bulkDeleteBtn">Delete Selected</button>
                        <a href="javascript:void(0);"
                            class="dropdown-toggle export_btn btn btn-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Export
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start p-3">
                            <li>
                                <a href="{{ route('invoices.export.pdf') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                            </li>
                            <li>
                                <a href="{{ route('invoices.export.excel') }}" class="dropdown-item rounded-1"><i
                                        class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="me-2 mb-2 filter_area">

                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control search_box" placeholder="Search...">


                        <!-- /Search -->


                    </div>
                    <div class="sort-box">
                        <select name="" id="" class="form-control">
                            <option value="" hidden>Sort Invoices</option>
                            <option value="">All</option>
                            <option value="">Coordinators</option>
                            <option value="">Archieved</option>
                        </select>
                        <i class="ti ti-chevron-down"></i>
                    </div>

                </div>


            </div>
            <!-- /Breadcrumb -->

            <div class="card">

                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Title</th>
                                    <th>Client Name</th>
                                    <th>Site Name</th>
                                    <th>Invoice Date</th>
                                    <th>Due Date</th>
                                    <th>Total Shift Hours</th>
                                    <th>Net Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($invoices->currentPage() - 1) * $invoices->perPage() + 1; @endphp
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td><input type="checkbox" class="invoice-checkbox" value="{{ $invoice->id }}">
                                        </td>
                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <div class="d-flex align-items-center file-name-icon">
                                                <div class="ms-2">
                                                    <h6 class="fw-medium"><a
                                                            href="{{ ($invoice->client_id) ? route('invoices.show', $invoice->id) : route('payrolls.show', $invoice->id)  }}">{{ $invoice->invoice_no }}</a>
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $invoice->invoice_title }}</td>
                                        <td>{{ $invoice->client?->client_name }}</td>
                                        <td>{{ $invoice->site?->site_name }}</td>
                                        <td>{{ $invoice->invoice_date }}</td>
                                        <td>{{ $invoice->due_date }}</td>
                                        <td>{{ $invoice->total_shift_hours }}</td>
                                        <td>{{ $invoice->net_amount }}</td>
                                        <td>{{ $invoice->paid_amount }}</td>
                                        <td>{{ $invoice->payment_date }}</td>
                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                {{--<a href="#" class="me-2" onclick="editSite({{ $invoice->id }})">
                                                    <i class="ti ti-edit"></i>
                                                </a>--}}
                                                <a onclick="deleteInvoice({{ $invoice->id }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer d-flex justify-content-center">
                            {{ $invoices->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>



    </div>
    <!-- /Page Wrapper -->

    <!-- Invoice Success -->
    <div class="modal fade" id="success_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center p-3">
                        <span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
                                class="ti ti-check fs-24"></i></span>
                        <h5 class="mb-2" id="success_message"></h5>

                        </p>
                        <div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <a href="{{ url('invoices') }}" class="btn btn-dark w-100">Back to List</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Invoice Success -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Confirm Delete</h4>
                    <p class="mb-3">You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->

@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Site search functionality
        $('.search_box').on('keyup', function() {
            let searchText = $(this).val().toLowerCase();

            $('.datatable tbody tr').each(function() {
                let rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(searchText) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

    let selectedId = null;

    function deleteInvoice(record_id) {
        selectedId = record_id;
        $('#delete_modal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (selectedId !== null) {
            $.ajax({
                url: `${baseUrl}/deleteinvoice/${selectedId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#delete_modal').modal('hide');

                        $('#success_message').html('Invoice Deleted Successfully!')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        $('#delete_modal').modal('hide');
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        });

        // Select All toggle
        $('#selectAll').on('change', function() {
            $('.invoice-checkbox').prop('checked', $(this).prop('checked'));
        });
        // Bulk delete button
        $('#bulkDeleteBtn').on('click', function() {
            const selected = $('.invoice-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one invoice to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete the selected invoice?')) return;

            $.ajax({
                url: '{{ route('invoices.bulkDelete') }}',
                type: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#success_message').text('Selected invoice deleted successfully!');
                    $('#success_modal').modal('show');
                },
                error: function() {
                    alert('Something went wrong during bulk delete.');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {

            $('.submenu > a').click(function(e) {
                e.preventDefault();

                var $this = $(this);
                var $submenu = $this.next('ul');

                if (!$this.hasClass('subdrop')) {
                    $('.submenu > a').removeClass('subdrop');
                    $('.submenu ul').slideUp(200);

                    $this.addClass('subdrop');
                    $submenu.slideDown(200);
                } else {
                    $this.removeClass('subdrop');
                    $submenu.slideUp(200);
                }
            });


            var currentPage = window.location.pathname.split("/").pop();

            $('#sidebar-menu a').each(function() {
                var linkPage = $(this).attr('href');
                if (linkPage === currentPage) {
                    $(this).addClass('active');

                    var $submenu = $(this).closest('.submenu');
                    if ($submenu.length) {
                        $submenu.find('> a').addClass('subdrop');
                        $submenu.find('ul').slideDown(0).css('display', 'block');
                    }
                }
            });
        });
    </script>
@endsection
