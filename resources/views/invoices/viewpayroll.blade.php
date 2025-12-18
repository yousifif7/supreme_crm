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
                        // Determine the payee: a security staff user (employee) or a subcontractor user
                        $staff = null;
                        $payeeUser = null; // User model for either staff or subcontractor

                        if (!empty($invoice->security_staff_id)) {
                            $staff = App\Models\Employee::where('user_id', $invoice->security_staff_id)->first();
                            $payeeUser = $staff?->user ?? App\Models\User::find($invoice->security_staff_id);
                        } elseif (!empty($invoice->subcontractor_id)) {
                            $payeeUser = $invoice->subcontractor;
                        }
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
                                                        <p class="mb-1">150 Chingford Road, Walthamstow London, E17 4PL</p>
                            <p>Email: <span class="text-dark">admin@splconnect.co.uk</span></p>
<!--                            <p>Phone: <span class="text-dark">+1 234567890</span></p>
-->                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-2" style="width:80px; margin: 0 auto;">
                                <img src="{{ asset('assets/sp_logo.png') }}" class="img-fluid" alt="logo">
                            </div>
                            <p class="mb-1"><b>Payroll #{{ $invoice->invoice_number }}</b></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1">Payroll Period: <span class="text-dark">{{ format_date($invoice->date_from) }} to
                                    {{ format_date($invoice->date_to) }}</span></p>
                            <p class="mb-1">
                                Created Date:
                                <span class="text-dark">
                                    {{ $invoice->created_at ? $invoice->created_at->format('d/m/Y') : 'N/A' }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row border-bottom mb-3">
                        <div class="col-md-6">
                            <p class="text-dark mb-2 fw-semibold">Payee Details</p>
                            <p>Name: <span class="text-dark">{{ $payeeUser?->first_name ?? $payeeUser?->name ?? ($staff?->first_name ?? 'N/A') }} {{ $payeeUser?->last_name ?? '' }}</span></p>
                            <p>Email: <span class="text-dark">{{ $payeeUser?->email ?? ($staff?->email ?? 'N/A') }}</span></p>
                            <p>Phone: <span class="text-dark">{{ $payeeUser?->phone ?? ($staff?->phone ?? 'N/A') }}</span></p>
                            <p>Reference Number: <span class="text-dark">{{ $staff?->employee->reference_number ?? 'N/A' }}</span></p>
                            <p>NI Number: <span class="text-dark">{{ $staff?->employee->ni_number ?? 'N/A' }}</span></p>
                            <p>Tax Code: <span class="text-dark">{{ $staff?->employee->tax_code ?? 'N/A' }}</span></p>
                            <p>Pay Method: <span class="text-dark">{{ $invoice->pay_method ?? $staff?->employee->pay_method ?? 'Bank Transfer' }}</span></p>
                            <p>Total Deductions: <span class="text-dark">£{{ number_format((($invoice->gross_amount ?? 0) - ($invoice->net_amount ?? 0)), 2) }}</span></p>
                            @if ($invoice->subcontractor)
                                <p>Subcontractor: <span class="text-dark">{{ $invoice->subcontractor->name }}</span></p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="text-dark mb-2 fw-semibold">Shift Summary</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>Total Hours Worked: <span class="text-dark">{{ $totalHours }}</span></p>
                                    <p>Hourly Rate: <span class="text-dark">£{{ $invoice->rate_per_hour }}</span></p>
                                </div>
                                <!--<div class="col-md-6">
                                    <p>Break Deduction (Hrs): <span class="text-dark">{{ $totalBreaks }}</span></p>
                                    <p>Book On Hours: <span class="text-dark">{{ $totalBookOnHours }}</span></p>
                                    <p>Book Off Hours: <span class="text-dark">{{ $totalBookOffHours }}</span></p>
                                    <p>Total Billable Hours: <span
                                            class="text-dark">{{ $invoice->total_shift_hours }}</span></p>
                                    <p>SSP Hours: <span class="text-dark">{{ $sspDays }}</span></p>
                                    <p>SSP Amount: <span class="text-dark">£{{ number_format($sspAmount, 2) }}</span></p>

                                    <p>Holiday Hours: <span class="text-dark">{{ $holidayHours }}</span></p>
                                    <p>Holiday Amount: <span
                                            class="text-dark">£{{ number_format($holidayAmount, 2) }}</span></p>

                                    <p>Unpaid Leave Hours: <span class="text-dark">{{ $unpaidHours }}</span></p>
                                    <p>Unpaid Leave Deduction: <span
                                            class="text-dark">£{{ number_format($unpaidAmount, 2) }}</span></p>
                                </div>-->
                            </div>

                        </div>
                    </div>

                    {{-- Year-to-Date Summary: compute from existing invoices if invoice->ytd not present --}}
                    @php
                        $ytdPeriodStart = now()->startOfYear()->format('d/m/Y');
                        $ytdPeriodEnd = now()->endOfYear()->format('d/m/Y');
                        $ytdInvoices = collect();
                        $ytdGross = 0;
                        $ytdNet = 0;
                        $ytdCount = 0;
                        $ytdDeductions = 0;
                        if (! empty($invoice->ytd) || ! empty($invoice->ytd_pay)) {
                            $ytdValue = $invoice->ytd ?? $invoice->ytd_pay;
                        } else {
                            if (! empty($staff?->id)) {
                                $ytdInvoices = \App\Models\Invoice::where('security_staff_id', $staff->id)
                                    ->whereYear('date_from', now()->year)
                                    ->get();
                                $ytdGross = $ytdInvoices->sum('gross_amount');
                                $ytdNet = $ytdInvoices->sum('net_amount');
                                $ytdCount = $ytdInvoices->count();
                                $ytdDeductions = $ytdGross - $ytdNet;
                            }
                            $ytdValue = $ytdGross;
                        }
                    @endphp

                    <div class="mb-4">
                        <div class="row border mb-3 p-3">
                            <div class="col-md-12">
                                <h6 class="mb-2">Year-to-Date Summary ({{ now()->year }})</h6>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1">Period</p>
                                <p class="text-dark">{{ $ytdPeriodStart }} to {{ $ytdPeriodEnd }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1">Total Gross Pay</p>
                                <p class="text-dark">£{{ number_format($ytdValue ?? 0, 2) }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1">Total Net Pay</p>
                                <p class="text-dark">£{{ number_format($ytdNet ?? 0, 2) }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1">Payslips This Year</p>
                                <p class="text-dark">{{ $ytdCount ?? 0 }}</p>
                            </div>
                            <div class="col-md-12 mt-2">
                                <p class="mb-1">Total Deductions</p>
                                <p class="text-dark">£{{ number_format($ytdDeductions ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>
<!--                        <h5 class="mb-3">Shift Details</h5>
-->
                    {{-- Rate Hour / Deductions / Payments sections --}}
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="border p-3 h-100">
                                <h6 class="mb-2">Rate & Hours</h6>
                                <p class="mb-1">Hourly Rate</p>
                                <p class="text-dark">£{{ number_format($invoice->rate_per_hour ?? 0, 2) }}</p>
                                <p class="mb-1 mt-2">Total Hours</p>
                                <p class="text-dark">{{ $totalHours ?? 0 }}</p>
                                <p class="mb-1 mt-2">Total Billable Hours</p>
                                <p class="text-dark">{{ $invoice->total_shift_hours ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border p-3 h-100">
                                <h6 class="mb-2">Deductions</h6>
                                @php
                                    $deductionsTotal = ($invoice->gross_amount ?? 0) - ($invoice->net_amount ?? 0);
                                @endphp
                                <p class="mb-1">Tax</p>
                                <p class="text-dark">£{{ number_format($invoice->tax_amount ?? $invoice->tax ?? 0, 2) }}</p>
                                <p class="mb-1 mt-2">NI</p>
                                <p class="text-dark">£{{ number_format($invoice->ni_amount ?? $staff?->employee->ni_amount ?? 0, 2) }}</p>
                                <p class="mb-1 mt-2">Other Deductions</p>
                                <p class="text-dark">£{{ number_format($invoice->other_deductions ?? (($deductionsTotal > 0) ? ($deductionsTotal - (($invoice->tax_amount ?? $invoice->tax ?? 0) + ($invoice->ni_amount ?? $staff?->employee->ni_amount ?? 0))) : 0), 2) }}</p>
                                <hr>
                                <p class="mb-1">Total Deductions</p>
                                <p class="text-dark">£{{ number_format($deductionsTotal, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border p-3 h-100">
                                <h6 class="mb-2">Payments</h6>
                                <p class="mb-1">Payment Method</p>
                                <p class="text-dark">{{ $invoice->pay_method ?? $staff?->employee->pay_method ?? 'Bank Transfer' }}</p>
                                <p class="mb-1 mt-2">Bank Name</p>
                                <p class="text-dark">{{ $invoice->bank_name ?? $payeeUser?->bank_name ?? $staff->bank_name ?? 'ــ' }}</p>
                                <p class="mb-1 mt-2">Account Number</p>
                                <p class="text-dark">{{ $invoice->account_number ?? $payeeUser?->account_number ?? $staff->account_number ?? 'ــ' }}</p>
                                <p class="mb-1 mt-2">IFSC / Sort Code</p>
                                <p class="text-dark">{{ $invoice->ifsc ?? $payeeUser?->ifsc ?? $staff->sort_code ?? 'ــ' }}</p>
                            </div>
                        </div>
                    </div>

                        <div class="table-responsive mb-3">
                            @php
                                // Group items by site name so shifts show under their specific site section
                                $grouped = $invoice->items->groupBy(function ($i) {
                                    return $i->site->site_name ?? 'No Site';
                                });
                            @endphp

                            @foreach ($grouped as $siteName => $items)
<!--                                <h6 class="mb-2">Site: <strong>{{ $siteName }}</strong></h6>
-->                                <table class="table table-bordered mb-4">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Hours</th>
                                            <th class="text-end">Rate</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->date }}</td>
                                                <td class="text-end">{{ number_format($item->hours, 2) }}</td>
                                                <td class="text-end">£{{ number_format($item->rate, 2) }}</td>
                                                <td class="text-end">£{{ number_format($item->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach
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
                                <p class="text-dark fw-medium">£{{ $invoice->rate_per_hour }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Gross Pay @if($invoice->type=='security_staff')(including SSP/Holiday)@endif</p>
                                <p class="text-dark fw-medium">£{{ number_format($invoice->gross_amount, 2) }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p>Unpaid Leave Deduction</p>
                                <p class="text-dark fw-medium">- £{{ number_format($unpaidAmount, 2) }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                <h5>Net Pay</h5>
                                <h5>£{{ number_format($invoice->net_amount, 2) }}</h5>
                            </div>
                            @if($invoice->type =='subcontractor')
                                <div class="d-flex justify-content-between align-items-center border-top mt-2 mb-2 pe-3">
                                    <p>Commission ({{ number_format($invoice->commission_percent ?? 0, 2) }}%)</p>
                                    <p class="text-dark fw-medium">£{{ number_format($invoice->commission_amount ?? 0, 2) }}</p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                    <p>Amount to Staff</p>
                                    <p class="text-dark fw-medium">£{{ number_format($invoice->staff_amount ?? ($invoice->total_amount - ($invoice->commission_amount ?? 0)), 2) }}</p>
                                </div>
                            @endif
                            @php
                                // Intl NumberFormatter may not be available on all PHP installs.
                                if (class_exists('\\NumberFormatter')) {
                                    try {
                                        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                                        $words = $formatter->format($invoice->net_amount);
                                        if (! $words) {
                                            $words = (string) $invoice->net_amount;
                                        }
                                    } catch (\Throwable $e) {
                                        // Fallback to numeric representation if formatter fails
                                        $words = (string) $invoice->net_amount;
                                    }
                                } else {
                                    // Intl extension not installed — fallback
                                    $words = (string) $invoice->net_amount;
                                }
                            @endphp
                            <p class="fs-12">
                                Amount in Words: UK POUND {{ ucwords($words) }} Only
                            </p>
                        </div>
                    </div>


                    <div class="text-center">
                        <p class="text-dark mb-1">Payment Made Via bank transfer</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <p class="fs-12 mb-0 me-3">Bank Name: <span class="text-dark">{{$staff->bank_name ??'ــ'}}</span></p>
                            <p class="fs-12 mb-0 me-3">Account Number: <span class="text-dark">{{$staff->account_number?? 'ــ'}}</span></p>
                            <p class="fs-12">Sort Code: <span class="text-dark">{{$staff->sort_code ?? 'ــ'}}</span></p>
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
