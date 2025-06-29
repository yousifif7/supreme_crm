@extends('layouts.app')
@section('title', 'CRM - Invoice')

@section('styles')
    <style>
        @media print {
            .header,
            .sidebar,
            #heading {
                display: none !important;
            }
            .table th, .table td,
            .page-wrapper
            {            
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

            </div>
            <!-- /Breadcrumb -->

            <div class="card">

                <div class="card-body">
                        <div class="row justify-content-between align-items-center border-bottom mb-3">
                            <div class="col-md-4">
                                <h4 class="mb-1">Supreme Protection</h4>
                                <p class="mb-1">Wembley HA9,UK</p>
                                <p class="mb-1">Email : <span class="text-dark">edison@example.com</span></p>
                                <p>Phone : <span class="text-dark">+1 234567890</span></p>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2" style="width:80px; margin: 0 auto;">
                                    <img src="{{ asset('assets/sp_logo.png') }}" class="img-fluid" alt="logo">
                                </div>
                                <p class="mb-1 text-center"><b>{{ $invoice->invoice_title }}</b></p>
                            </div>
                            <div class="col-md-4">
                                <div class=" text-end mb-3">
                                    <h5 class="text-gray mb-1">Invoice # <span class="text-primary">{{ $invoice->invoice_no }}</span></h5>
                                    <p class="mb-1 fw-medium">Created Date : <span class="text-dark">{{ date('M d, Y', strtotime($invoice->invoice_date)) }}</span> </p>
                                    @if(isset($client))
                                    <p class="fw-medium">Due Date : <span class="text-dark">{{ date('M d, Y', strtotime($invoice->due_date)) }}</span> </p>
                                    @endif
                                    {{--<p class="fw-medium">Contract/Agreement Number : <span class="text-dark"></span> </p>--}}
                                </div>
                            </div>
                        </div>
                        <div class="row border-bottom mb-3">
                            <div class="col-md-4">
                                @if(isset($client))
                                <p class="text-dark mb-2 fw-semibold">Client Details</p>
                                <div>
                                    <p class="mb-1">Client Name : <span class="text-dark">{{ $client->client_name }}</span></p>
                                    <p class="mb-1">Client Address: {{ $client->address }}</p>

                                    <p class="text-dark mb-2 fw-semibold">Client Contact Info</p>
                                    <p class="mb-1">Email : <span class="text-dark">{{ $client->email }}</span></p>
                                    <p class="mb-1">Phone : <span class="text-dark">{{ $client->contact_number }}</span></p>
                                    <p class="mb-3">Site/Location Name : <span class="text-dark">{{ $site->site_name }}</span></p>
                                </div>
                                @else
                                <p class="text-dark mb-2 fw-semibold">Employee Details</p>
                                <div>
                                    <p class="mb-1">Employee Name : <span class="text-dark">{{ $employee->fore_name.' '.$employee->sur_name }}</span></p>
                                    <p class="mb-1">Ni Number : <span class="text-dark">{{ $employee->ni_number }}</span></p>
                                    <p class="mb-1">Job Title: {{ $employee->job_title }}</p>

                                    <p class="text-dark mb-2 fw-semibold">Employee Contact Info</p>
                                    <p class="mb-1">Email : <span class="text-dark">{{ $employee->email }}</span></p>
                                    <p class="mb-1">Phone : <span class="text-dark">{{ $employee->contact }}</span></p>
                                    <p class="mb-3">Site/Location Name : <span class="text-dark">{{ $site->site_name }}</span></p>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <p class="text-dark mb-2 fw-semibold">Shift Information</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1">Shift Start Date : <span class="text-dark">{{ $shift->from_shift }}</span></p>
                                        <p class="mb-1">Shift Start Time : <span class="text-dark">{{ $shift->start_shift }}</span></p>
                                        <p class="mb-1">Shift End Date : <span class="text-dark">{{ $shift->to_shift }}</span></p>
                                        <p class="mb-1">Shift End Time : <span class="text-dark">{{ $shift->end_shift }}</span></p>
                                        <p>Shift Duration (Hrs) : <span class="text-dark">{{ $totalHours }}</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">Break Deduction (Hrs) : <span class="text-dark">{{ $totalBreaks+$totalBookOnHours+$totalBookOffHours}}</span></p>
                                        <p class="mb-1">Total Billable Hours : <span class="text-dark">{{ $totalHours-$totalBreaks+$totalBookOnHours+$totalBookOffHours }}</span></p>
                                        <p class="mb-1">Number of Guards Deployed : <span class="text-dark">{{ $shift->number_shift }}</span></p>
                                        <p class="mb-1">Shift Days : <span class="text-dark">{{ implode(',',$shiftDays) }}</span></p>
                                        <p>Shift Type (Day/Night/Weekend/Bank Holiday) : <span class="text-dark"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            {{--<p class="fw-medium">Invoice For : <span class="text-dark fw-medium">Design &amp; development of Website</span></p>--}}
                            <div class="table-responsive mb-3">
                                <table class="table">
                                    <thead class="thead-default">
                                        <tr>
                                            <th>Billing Rates</th>
                                            <th class="text-end">Hours</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{--<tr>
                                            <td><h6>Client Rate Per Hour</h6></td>
                                            <td class="text-gray-9 fw-medium text-end">1</td>
                                            <td class="text-gray-9 fw-medium text-end">$500</td>
                                            <td class="text-gray-9 fw-medium text-end">$500</td>
                                        </tr>--}}
                                        <tr>
                                            <td>Cost to Client (Hours × Client Rate)</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $totalHours-$totalBreaks }}</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $invoice->rate_per_hour }}$</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ ($totalHours-$totalBreaks) * $invoice->rate_per_hour }}$</td>
                                        </tr>
                                        {{--<tr>
                                            <td><h6>Additional Charges</h6></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                        </tr>
                                        <tr>
                                            <td>Last-Minute Shift Surcharge</td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end">0</td>
                                        </tr>
                                        <tr>
                                            <td>Holiday Pay Uplift</td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end">0</td>
                                        </tr>
                                        <tr>
                                            <td>Overtime Charges</td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end">0</td>
                                        </tr>
                                        <tr>
                                            <td>Travel/Mileage Reimbursement</td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end">0</td>
                                        </tr>
                                        <tr>
                                            <td>Equipment Charges (if applicable)</td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end"></td>
                                            <td class="text-gray-9 fw-medium text-end">0</td>
                                        </tr>--}}
                                        @if($totalBookOnHours)
                                        <tr>
                                            <td>Bookon Charges (if applicable)</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $totalBookOnHours }}</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $invoice->rate_per_hour }}$</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ ($totalBookOnHours) * $invoice->rate_per_hour }}$</td>
                                        </tr>
                                        @endif
                                        @if($totalBookOffHours)
                                        <tr>
                                            <td>Bookoff Charges (if applicable)</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $totalBookOffHours }}</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ $invoice->rate_per_hour }}$</td>
                                            <td class="text-gray-9 fw-medium text-end">{{ ($totalBookOffHours) * $invoice->rate_per_hour }}$</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @php
                            $totalDeductions = ($totalBookOnHours * $invoice->rate_per_hour) + ($totalBookOffHours * $invoice->rate_per_hour);
                        @endphp
                        <div class="row border-bottom mb-3">
                            <div class="col-md-7">
                                <div class="py-4">
                                    <div class="mb-3">
                                        @if(isset($client))
                                        <h6 class="mb-1">Terms and Conditions</h6>
                                        <p>{{ $invoice->payment_note }}</p>
                                        @else
                                        <h6 class="mb-1">Notes</h6>
                                        <p>{{ $invoice->notes }}</p>
                                        @endif
                                    </div>
                                    {{--<div class="mb-3">
                                        <h6 class="mb-1">Notes</h6>
                                        <p>Computer Generated Invoice not required signatures.</p>
                                    </div>--}}
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">Sub Total</p>
                                    <p class="text-dark fw-medium mb-2">{{ (($totalHours-$totalBreaks) * $invoice->rate_per_hour) + $totalDeductions }}$</p>
                                </div>
                                {{--<div class="d-flex justify-content-between align-items-center border-bottom mb-2 pe-3">
                                    <p class="mb-0">Discount(0%)</p>
                                    <p class="text-dark fw-medium mb-2">$10</p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                    <p class="mb-0">VAT(5%)</p>
                                    <p class="text-dark fw-medium mb-2">$54</p>
                                </div>--}}
                                <div class="d-flex justify-content-between align-items-center mb-2 pe-3">
                                    <h5>Total Amount</h5>
                                    <h5>{{ (($totalHours-$totalBreaks) * $invoice->rate_per_hour) + $totalDeductions }}$</h5>
                                </div>
                                @php
                                    $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                                    $words = $formatter->format(($totalHours-$totalBreaks) * $invoice->rate_per_hour);
                                @endphp
                                <p class="fs-12">
                                    Amount in Words : Dollar {{ ucwords($words) }}
                                </p>
                            </div>
                        </div>
                        {{--<div class="row justify-content-end align-items-end text-end border-bottom mb-3">
                            <div class="col-md-3">
                                <div class="text-end">
                                    <img src="assets/img/sign.svg" class="img-fluid" alt="sign">
                                </div>
                                <div class="text-end mb-3">
                                    <h6 class="fs-14 fw-medium pe-3">Ted M. Davis</h6>
                                    <p>Assistant Manager</p>
                                </div>
                            </div>
                        </div>--}}
                        
                        <div class="text-center">
                            {{--<div class="mb-3" style="margin:auto; width: 60px">
                                <img src="{{ asset('assets/sp_logo.png') }}" class="img-fluid" alt="logo">
                            </div>--}}
                            <p class="text-dark mb-1">Payment Made Via bank transfer / Cheque in the name of Thomas Lawler</p>
                            <div class="d-flex justify-content-center align-items-center">
                                <p class="fs-12 mb-0 me-3">Bank Name : <span class="text-dark">HDFC Bank</span></p>
                                <p class="fs-12 mb-0 me-3">Account Number : <span class="text-dark">45366287987</span></p>
                                <p class="fs-12">IFSC : <span class="text-dark">HDFC0018159</span></p>
                            </div>
                        </div>
                </div>
            </div>

        </div>



    </div>
    <!-- /Page Wrapper -->


@endsection
@section('scripts')

    <script>
        $(document).ready(function() {

        });
    </script>
@endsection
