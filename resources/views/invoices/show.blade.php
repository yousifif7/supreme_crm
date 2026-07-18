@extends('layouts.app')
@section('title', brand_title('Invoice'))

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
    <!-- Page Wrapper -->
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3" id="heading">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Invoice</h2>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
                </div>
            </div>
            <!-- /Breadcrumb -->
@php
    $client = App\Models\Client::where('user_id',$invoice->client_id)->first();
@endphp
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between align-items-center border-bottom mb-3">
                        <div class="col-md-4">
                            <h4 class="mb-1">{{ brand_company() }}</h4>
                            <p class="mb-1">{{ brand_address() }}</p>
                            <p class="mb-1">Email : <span class="text-dark">{{ brand_email() }}</span></p>
<!--                            <p>Phone : <span class="text-dark">+1 234567890</span></p>
-->                        </div>
                        <div class="col-md-4">
                            <div class="mb-2" style="width:80px; margin: 0 auto;">
                                <img src="{{ brand_logo_url('dashboard_logo') }}" class="img-fluid" alt="logo">
                            </div>
                            <p class="mb-1 text-center"><b>Invoice #{{ $invoice->invoice_number }}</b></p>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end mb-3">
                                <p class="mb-1 fw-medium">
                                    Created Date :
                                    <span class="text-dark">
                                        {{ $invoice->created_at ? $invoice->created_at->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </p>
                                <p class="fw-medium">Due Date : <span class="text-dark">{{ format_date($invoice->due_date) }}</span> </p>
                                <p class="fw-medium">Period : <span class="text-dark">{{ format_date($invoice->date_from) }} to
                                        {{ format_date($invoice->date_to) }}</span> </p>
                                <p class="mb-1 fw-medium">Invoice Number : <span class="text-dark">{{ $invoice->invoice_number }}</span></p>
                                <p class="mb-1 fw-medium">Reference : <span class="text-dark">{{ $invoice->reference ?? 'N/A' }}</span></p>
                                <p class="mb-1 fw-medium">VAT Number : <span class="text-dark">{{ $invoice->vat_number ?? ($invoice->client->vat_number ?? 'N/A') }}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="row border-bottom mb-3">
                        <div class="col-md-6">
                            @if ($invoice->type === 'client')
                                <p class="text-dark mb-2 fw-semibold">Client Details</p>
                                <div>
                                    <p class="mb-1">Client Name : <span
                                            class="text-dark">{{ $invoice->client->name }}</span></p>
                                    <p class="mb-1">Email : <span class="text-dark">{{ $invoice->client->email }}</span>
                                    </p>
                                    <p class="mb-1">Phone : <span class="text-dark">{{ $invoice->client->phone }}</span>
                                    </p>
                                    @if ($invoice->site)
                                        <p class="mb-3">Site/Location : <span
                                                class="text-dark">{{ $invoice->site->name }}</span></p>
                                    @endif
                                </div>
                            @elseif($invoice->type === 'subcontractor')
                                <p class="text-dark mb-2 fw-semibold">Subcontractor Details</p>
                                <div>
                                    <p class="mb-1">Subcontractor Name : <span
                                            class="text-dark">{{ $invoice->subcontractor->name }}</span></p>
                                    <p class="mb-1">Email : <span
                                            class="text-dark">{{ $invoice->subcontractor->email }}</span></p>
                                    <p class="mb-1">Phone : <span
                                            class="text-dark">{{ $invoice->subcontractor->phone }}</span></p>
                                </div>
                            @else
                                <p class="text-dark mb-2 fw-semibold">Security Staff Details</p>
                                <div>
                                    <p class="mb-1">Staff Name : <span
                                            class="text-dark">{{ $invoice->securityStaff->name }}</span></p>
                                    <p class="mb-1">Email : <span
                                            class="text-dark">{{ $invoice->securityStaff->email }}</span></p>
                                    <p class="mb-1">Phone : <span
                                            class="text-dark">{{ $invoice->securityStaff->phone }}</span></p>
                                    @if ($invoice->subcontractor)
                                        <p class="mb-1">Subcontractor : <span
                                                class="text-dark">{{ $invoice->subcontractor->name }}</span></p>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="text-dark mb-2 fw-semibold">Shift Summary</p>
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="mb-1">Period From : <span
                                            class="text-dark">{{ format_date($invoice->date_from) }}</span></p>
                                    <p class="mb-1">Period To : <span class="text-dark">{{format_date($invoice->date_to) }}</span>
                                    </p>
                                    <p class="mb-1">Total Hours Worked : <span
                                            class="text-dark">{{ $totalHours }}</span></p>
                                    <p>Hourly Rate : <span class="text-dark">£{{ $invoice->rate_per_hour }}</span></p>
                                </div>
                               <!-- <div class="col-md-6">
                                    <p class="mb-1">Break Deduction (Hrs) : <span
                                            class="text-dark">{{ $totalBreaks }}</span></p>
                                    <p class="mb-1">Book On Hours : <span
                                            class="text-dark">{{ $totalBookOnHours }}</span></p>
                                    <p class="mb-1">Book Off Hours : <span
                                            class="text-dark">{{ $totalBookOffHours }}</span></p>
                                    <p class="mb-1">SSP Hours : <span class="text-dark">{{ $totalSSPHours }}</span></p>
                                    <p class="mb-1">Holiday Hours : <span
                                            class="text-dark">{{ $totalHolidayHours }}</span></p>
                                    <p class="mb-1">Unpaid Hours : <span class="text-dark">{{ $totalUnpaidHours }}</span>
                                    </p>
                                    <p>Total Billable Hours : <span
                                            class="text-dark">{{ $invoice->total_shift_hours }}</span></p>
                                </div>-->
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="table-responsive mb-3">
                            {{-- Breakdown by site, further split by rate so a site that ran at
                                 multiple rates in this period shows one row per rate instead
                                 of a single averaged figure. --}}
                            @php
                                $invoiceItems = collect($invoice->items)->filter(function ($item) use ($invoice) {
                                    // If invoice is scoped to a single site, only include that site
                                    if ($invoice->site && isset($invoice->site->id) && $item->site) {
                                        return $item->site->id == $invoice->site->id;
                                    }
                                    return true;
                                });

                                $bySite = $invoiceItems
                                    ->groupBy(fn($i) => $i->site->site_name ?? ($i->site_name ?? 'N/A'))
                                    ->sortKeys();
                            @endphp

                            <table class="table table-bordered">
                                <thead class="thead-default">
                                    <tr>
                                        <th>Site</th>
                                        <th class="text-end">Hours</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bySite as $siteName => $siteItems)
                                        @php
                                            $byRate = $siteItems
                                                ->groupBy(fn($i) => number_format((float) ($i->rate ?? 0), 2, '.', ''))
                                                ->sortKeys();
                                            $rateCount = $byRate->count();
                                        @endphp

                                        @foreach($byRate as $rateKey => $rateItems)
                                            @php
                                                $rowRate   = (float) $rateKey;
                                                $rowHours  = $rateItems->sum(fn($it) => (float) ($it->hours ?? 0));
                                                $rowAmount = $rateItems->sum(fn($it) => (float) ($it->hours ?? 0) * (float) ($it->rate ?? 0));
                                            @endphp
                                            <tr>
                                                <td>{{ $siteName }}</td>
                                                <td class="text-end">{{ number_format($rowHours, 2) }}</td>
                                                <td class="text-end">£{{ number_format($rowRate, 2) }}</td>
                                                <td class="text-end">£{{ number_format($rowAmount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No shifts on this invoice.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($invoiceItems->count())
                                    <tfoot>
                                        @php
                                            $sumHours = $invoiceItems->sum(fn($i) => (float) ($i->hours ?? 0));
                                            $sumAmount = $invoiceItems->sum(fn($i) => (float) ($i->hours ?? 0) * (float) ($i->rate ?? 0));
                                        @endphp
                                        <tr>
                                            <th class="text-end">Totals</th>
                                            <th class="text-end">{{ number_format($sumHours, 2) }}</th>
                                            <th></th>
                                            <th class="text-end">£{{ number_format($sumAmount, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="row border-bottom mb-3">
                        <div class="col-md-7">
                            <div class="py-4">
                                <div class="mb-3">
                                    <h6 class="mb-1">Payment Terms</h6>
                                    <p>{{ $invoice->payment_note ?? 'Payment due within 30 days' }}</p>
                                </div>
                               <!-- @if ($invoice->notes)
                                    <div class="mb-3">
                                        <h6 class="mb-1">Notes</h6>
                                        <p>{{ $invoice->notes }}</p>
                                    </div>
                                @endif-->
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p class="mb-0">Total Hours</p>
                                <p class="text-dark fw-medium mb-2">{{ $invoice->total_shift_hours }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p class="mb-0">Hourly Rate</p>
                                <p class="text-dark fw-medium mb-2">£{{ $invoice->rate_per_hour }}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                <p class="mb-0">Sub Total</p>
                                <p class="text-dark fw-medium mb-2">£{{ number_format($invoice->gross_amount, 2) }}</p>
                            </div>

                            @if ($sspAmount > 0)
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">SSP Amount</p>
                                    <p class="text-dark fw-medium mb-2">£{{ number_format($sspAmount, 2) }}</p>
                                </div>
                            @endif

                            @if ($holidayAmount > 0)
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">Holiday Amount</p>
                                    <p class="text-dark fw-medium mb-2">£{{ number_format($holidayAmount, 2) }}</p>
                                </div>
                            @endif

                            @if ($unpaidAmount > 0)
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">Unpaid Deductions</p>
                                    <p class="text-dark fw-medium mb-2">-£{{ number_format($unpaidAmount, 2) }}</p>
                                </div>
                            @endif
                            @if ($invoice->tax_amount > 0)
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">Tax</p>
                                    <p class="text-dark fw-medium mb-2">£{{ number_format($invoice->tax_amount, 2) }}</p>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                <h5>Total GBP</h5>
                                <h5>£{{ number_format($invoice->net_amount, 2) }}</h5>
                            </div>
                            @php
                                // Use NumberFormatter if available (requires PHP intl extension).
                                if (class_exists('NumberFormatter')) {
                                    $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                                    $words = $formatter->format($invoice->net_amount);
                                } else {
                                    // Fallback: show numeric amount with two decimals as words unavailable.
                                    $words = number_format($invoice->net_amount, 2);
                                }
                            @endphp
                            <p class="fs-12">
                                Amount in Words : UK POUND {{ ucwords($words) }} Only
                            </p>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="text-dark mb-1">Payment Made Via bank transfer</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <p class="fs-12 mb-0 me-3">Bank Name : <span class="text-dark">HDFC Bank</span></p>
                            <p class="fs-12 mb-0 me-3">Account Number : <span class="text-dark">45366287987</span></p>
                            <p class="fs-12">IFSC : <span class="text-dark">HDFC0018159</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Payment Advice / Detachable section -->
            <div class="card mt-4">
                <div class="card-body">
                    <div style="border-top:2px dashed #999; padding-top:16px; margin-top:8px;">
                        <h4 class="mb-3">PAYMENT ADVICE</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>To:</strong></p>
                                <p class="mb-1">{{ brand_company() }}</p>
                                <p class="mb-1">{{ brand_address() }}</p>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless" style="max-width:320px; float:right;">
                                    <tr>
                                        <td class="fs-12">Customer</td>
                                        <td class="fs-12">{{ $invoice->client->name ?? ($invoice->securityStaff->name ?? 'N/A') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fs-12">Invoice Number</td>
                                        <td class="fs-12">{{ $invoice->invoice_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fs-12">Amount Due</td>
                                        <td class="fs-12">£{{ number_format($invoice->net_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fs-12">Due Date</td>
                                        <td class="fs-12">{{ format_date($invoice->due_date) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fs-12">Amount Enclosed</td>
                                        <td class="fs-12">______________________</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="fs-12"><strong>Bank details</strong></p>
                        <p class="fs-12 mb-0">{{ brand_company() }}</p>
                        <p class="fs-12 mb-0">Sort Code 40-07-15</p>
                        <p class="fs-12 mb-0">Account number 32164426</p>
                        <p class="fs-12">Thanks For Your Business!</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3 fs-12">
                @if(config('brand.registration_no'))
                    Company Registration No: {{ config('brand.registration_no') }}.
                @endif
                Registered Office: {{ brand_address() }}.
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Any additional scripts can go here
        });
    </script>
@endsection
