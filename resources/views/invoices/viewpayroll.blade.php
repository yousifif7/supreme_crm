@extends('layouts.app')
@section('title', 'CRM - Payroll')

@section('styles')
    <style>
        @media print {

            .header,
            .sidebar,
            #heading,
            .no-print {
                display: none !important;
            }

            .table th,
            .table td,
            .page-wrapper {
                padding: 1px !important;
            }

            .row {
                display: flex !important;
                flex-wrap: wrap !important;
            }

            .col {
                flex: 1 0 0%;
                max-width: 100%;
            }

            /* Column widths */
            .col-md-2 {
                flex: 0 0 16.666667%;
                max-width: 16.666667%;
            }

            .col-md-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }

            .col-md-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }

            .col-md-5 {
                flex: 0 0 41.666667%;
                max-width: 41.666667%;
            }

            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .col-md-7 {
                flex: 0 0 58.333333%;
                max-width: 58.333333%;
            }

            .col-md-8 {
                flex: 0 0 66.666667%;
                max-width: 66.666667%;
            }
        }
    </style>
@endsection

@section('contents')

    @php
        $staff = App\Models\User::role('security_staff', $invoice->secuirty_staff_id)->first();
    @endphp
    <div id="payroll-page" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3" id="heading">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Payroll</h2>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-primary">Print Payroll</button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="row justify-content-between align-items-center border-bottom mb-3">
                        <div class="col-md-4">
                            <h4 class="mb-1">Supreme Protection</h4>
                            <p class="mb-1">Wembley HA9, UK</p>
                            <p>Email: <span class="text-dark">edison@example.com</span></p>
                            <p>Phone: <span class="text-dark">+1 234567890</span></p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-2" style="width:80px; margin: 0 auto;">
                                <img src="{{ asset('assets/sp_logo.png') }}" class="img-fluid" alt="logo">
                            </div>
                            <p class="mb-1"><b>Payroll #{{ $invoice->invoice_number }}</b></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1">Payroll Period: <span class="text-dark">{{ $invoice->date_from }} to
                                    {{ $invoice->date_to }}</span></p>
                            <p class="mb-1">
                                Created Date:
                                <span class="text-dark">
                                    {{ $invoice->created_at ? $invoice->created_at->format('M d, Y') : 'N/A' }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row border-bottom mb-3">
                        <div class="col-md-4">
                            <p class="text-dark mb-2 fw-semibold">Staff Details</p>
                            <p>Name: <span
                                    class="text-dark">{{ $staff?->first_name ?? 'N/A' }}{{ $staff?->last_name ?? '' }}</span>
                            </p>
                            <p>Email: <span class="text-dark">{{ $staff?->email }}</span></p>
                            <p>Phone: <span class="text-dark">{{ $staff?->phone }}</span></p>
                            @if ($invoice->subcontractor)
                                <p>Subcontractor: <span class="text-dark">{{ $invoice->subcontractor->name }}</span></p>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <p class="text-dark mb-2 fw-semibold">Shift Summary</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>Total Hours Worked: <span class="text-dark">{{ $totalHours }}</span></p>
                                    <p>Hourly Rate: <span class="text-dark">{{ $invoice->rate_per_hour }}$</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p>Break Deduction (Hrs): <span class="text-dark">{{ $totalBreaks }}</span></p>
                                    <p>Book On Hours: <span class="text-dark">{{ $totalBookOnHours }}</span></p>
                                    <p>Book Off Hours: <span class="text-dark">{{ $totalBookOffHours }}</span></p>
                                    <p>Total Billable Hours: <span
                                            class="text-dark">{{ $invoice->total_shift_hours }}</span></p>
                                    <p>SSP Hours: <span class="text-dark">{{ $sspDays }}</span></p>
                                    <p>SSP Amount: <span class="text-dark">{{ number_format($sspAmount, 2) }}$</span></p>

                                    <p>Holiday Hours: <span class="text-dark">{{ $holidayHours }}</span></p>
                                    <p>Holiday Amount: <span
                                            class="text-dark">{{ number_format($holidayAmount, 2) }}$</span></p>

                                    <p>Unpaid Leave Hours: <span class="text-dark">{{ $unpaidHours }}</span></p>
                                    <p>Unpaid Leave Deduction: <span
                                            class="text-dark">{{ number_format($unpaidAmount, 2) }}$</span></p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">Shift Details</h5>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Site</th>
                                        <th class="text-end">Hours</th>
                                        <th class="text-end">Breaks</th>
                                        <th class="text-end">Book On</th>
                                        <th class="text-end">Book Off</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $item)
                                        <tr>
                                            <td>{{ $item->date }}</td>
                                            <td>{{ $item->site->site_name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($item->hours, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->break_hours, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->book_on_hours, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->book_off_hours, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->rate, 2) }}$</td>
                                            <td class="text-end">{{ number_format($item->amount, 2) }}$</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row border-bottom mb-3">
                        <div class="col-md-7">
                            <div class="py-4">
                                <h6>Payment Terms</h6>
                                <p>{{ $invoice->payment_note ?? 'Payment due within 30 days' }}</p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Total Hours</p>
                                <p class="text-dark fw-medium">{{ $invoice->total_shift_hours }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Hourly Rate</p>
                                <p class="text-dark fw-medium">{{ $invoice->rate_per_hour }}$</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Gross Pay (including SSP/Holiday)</p>
                                <p class="text-dark fw-medium">{{ number_format($invoice->gross_amount, 2) }}$</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Unpaid Leave Deduction</p>
                                <p class="text-dark fw-medium">- {{ number_format($unpaidAmount, 2) }}$</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                <h5>Net Pay</h5>
                                <h5>{{ number_format($invoice->net_amount, 2) }}$</h5>
                            </div>
                            @php
                                $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                                $words = $formatter->format($invoice->net_amount);
                            @endphp
                            <p class="fs-12">
                                Amount in Words: Dollar {{ ucwords($words) }} Only
                            </p>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="text-dark mb-1">Payment Made Via bank transfer</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <p class="fs-12 mb-0 me-3">Bank Name: <span class="text-dark">HDFC Bank</span></p>
                            <p class="fs-12 mb-0 me-3">Account Number: <span class="text-dark">45366287987</span></p>
                            <p class="fs-12">IFSC: <span class="text-dark">HDFC0018159</span></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Optional: add interactive payroll scripts here
        });
    </script>
@endsection
