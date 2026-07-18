<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payroll - {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; color: #222; }
        .container { width: 720px; margin: 0 auto; padding: 24px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 14px; margin-bottom: 18px; }
        .header .company h2 { margin: 0 0 4px; font-size: 16px; }
        .header .company p { margin: 2px 0; font-size: 11px; color: #555; }
        .header .payroll-meta { text-align: right; }
        .header .payroll-meta h3 { margin: 0 0 4px; font-size: 15px; }
        .header .payroll-meta p { margin: 2px 0; font-size: 11px; }

        /* Sections */
        .section { margin-bottom: 16px; }
        .section-title { font-size: 13px; font-weight: bold; border-bottom: 1px solid #aaa; margin-bottom: 8px; padding-bottom: 3px; }

        /* Two-column row */
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { width: 50%; vertical-align: top; padding: 0 6px 0 0; }

        /* Info list */
        .info-list p { margin: 3px 0; font-size: 11px; }
        .info-list p span { font-weight: bold; }

        /* Boxes */
        .box { border: 1px solid #ccc; padding: 10px 12px; }
        .box p { margin: 3px 0; font-size: 11px; }
        .box p.label { color: #555; margin-bottom: 1px; }
        .box p.value { font-weight: bold; font-size: 12px; margin-bottom: 6px; }

        /* Shift table */
        table.shift-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.shift-table th, table.shift-table td { border: 1px solid #bbb; padding: 6px 8px; font-size: 11px; }
        table.shift-table th { background: #f0f0f0; font-weight: bold; }
        table.shift-table td.text-right, table.shift-table th.text-right { text-align: right; }

        /* Totals block */
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #eee; font-size: 12px; }
        .totals-row.bold { font-weight: bold; font-size: 13px; border-top: 2px solid #333; border-bottom: none; margin-top: 4px; padding-top: 6px; }
        .totals-wrap { width: 320px; margin-left: auto; }

        /* Footer */
        .footer { border-top: 1px solid #ccc; margin-top: 20px; padding-top: 10px; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
<div class="container">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="company">
            <h2>{{ brand_company() }}</h2>
            <p>{{ brand_address() }}</p>
            <p>Email: {{ brand_email() }}</p>
        </div>
        <div class="payroll-meta">
            <h3>Payroll Slip</h3>
            <p><strong>Payroll #:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Period:</strong> {{ $invoice->date_from }} to {{ $invoice->date_to }}</p>
            <p><strong>Issue Date:</strong> {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') : 'N/A' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>

    {{-- ── PAYEE & SHIFT SUMMARY ── --}}
    <table class="two-col" style="margin-bottom:16px;">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Payee Details</div>
                    <div class="info-list">
                        <p><span>Name:</span> {{ $payeeUser?->first_name ?? $payeeUser?->name ?? ($staff?->first_name ?? 'N/A') }} {{ $payeeUser?->last_name ?? '' }}</p>
                        <p><span>Email:</span> {{ $payeeUser?->email ?? ($staff?->email ?? 'N/A') }}</p>
                        <p><span>Phone:</span> {{ $payeeUser?->phone ?? ($staff?->phone ?? 'N/A') }}</p>
                        @if($staff?->employee)
                            <p><span>Reference No:</span> {{ $staff->employee->reference_number ?? 'N/A' }}</p>
                            <p><span>NI Number:</span> {{ $staff->employee->ni_number ?? 'N/A' }}</p>
                            <p><span>Tax Code:</span> {{ $staff->employee->tax_code ?? 'N/A' }}</p>
                        @endif
                        <p><span>Pay Method:</span> {{ $invoice->pay_method ?? $staff?->employee->pay_method ?? 'Bank Transfer' }}</p>
                    </div>
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Shift Summary</div>
                    <div class="info-list">
                        <p><span>Hourly Rate:</span> £{{ number_format($invoice->rate_per_hour ?? 0, 2) }}</p>
                        <p><span>Total Hours (incl. breaks):</span> {{ number_format($totalHours, 2) }}</p>
                        <p><span>Break Deductions:</span> {{ number_format($totalBreaks, 2) }} hrs</p>
                        <p><span>Book-On Deductions:</span> {{ number_format($totalBookOn, 2) }} hrs</p>
                        <p><span>Book-Off Deductions:</span> {{ number_format($totalBookOff, 2) }} hrs</p>
                        <p><span>Billable Hours:</span> {{ number_format($invoice->total_shift_hours ?? 0, 2) }}</p>
                        @if($sspDays > 0)
                            <p><span>SSP Days:</span> {{ $sspDays }} (£{{ number_format($sspAmount, 2) }})</p>
                        @endif
                        @if($holidayHours > 0)
                            <p><span>Holiday Hours:</span> {{ number_format($holidayHours, 2) }} (£{{ number_format($holidayAmount, 2) }})</p>
                        @endif
                        @if($unpaidHours > 0)
                            <p><span>Unpaid Leave:</span> {{ number_format($unpaidHours, 2) }} hrs (- £{{ number_format($unpaidAmount, 2) }})</p>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── SITE BREAKDOWN ── --}}
    <div class="section">
        <div class="section-title">Shift Breakdown by Site</div>
        @php
            $grouped = $invoice->items->groupBy(fn($i) => $i->site->site_name ?? 'No Site');
        @endphp
        <table class="shift-table">
            <thead>
                <tr>
                    <th>Site</th>
                    <th class="text-right">Billable Hrs</th>
                    <th class="text-right">Rate (£/hr)</th>
                    <th class="text-right">Amount (£)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grouped as $siteName => $items)
                    @php
                        $siteHours  = $items->sum('hours');
                        $siteAmount = $items->sum(fn($it) => ($it->hours ?? 0) * ($it->rate ?? 0));
                        $siteRate   = $siteHours > 0 ? $siteAmount / $siteHours : 0;
                    @endphp
                    <tr>
                        <td>{{ $siteName }}</td>
                        <td class="text-right">{{ number_format($siteHours, 2) }}</td>
                        <td class="text-right">{{ number_format($siteRate, 2) }}</td>
                        <td class="text-right">{{ number_format($siteAmount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── TOTALS ── --}}
    <div class="section" style="margin-top:14px;">
        <div class="totals-wrap">
            <div class="totals-row">
                <span>Gross Pay</span>
                <span>£{{ number_format($invoice->gross_amount ?? $invoice->total_amount ?? 0, 2) }}</span>
            </div>
            @if($unpaidAmount > 0)
            <div class="totals-row">
                <span>Unpaid Leave Deduction</span>
                <span>- £{{ number_format($unpaidAmount, 2) }}</span>
            </div>
            @endif
            @php $deductionsTotal = ($invoice->gross_amount ?? 0) - ($invoice->net_amount ?? 0); @endphp
            @if($deductionsTotal > 0)
            <div class="totals-row">
                <span>Total Deductions (Tax / NI / Other)</span>
                <span>- £{{ number_format($deductionsTotal, 2) }}</span>
            </div>
            @endif
            <div class="totals-row bold">
                <span>Net Pay</span>
                <span>£{{ number_format($invoice->net_amount ?? $invoice->total_amount ?? 0, 2) }}</span>
            </div>
            @if($invoice->type === 'subcontractor')
            <div class="totals-row" style="margin-top:6px;">
                <span>Commission ({{ number_format($invoice->commission_percent ?? 0, 2) }}%)</span>
                <span>£{{ number_format($invoice->commission_amount ?? 0, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Amount to Staff</span>
                <span>£{{ number_format($invoice->staff_amount ?? (($invoice->total_amount ?? 0) - ($invoice->commission_amount ?? 0)), 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ── PAYMENT INFO ── --}}
    <div class="section" style="margin-top:14px;">
        <div class="section-title">Payment Details</div>
        <table class="two-col">
            <tr>
                <td class="info-list">
                    <p><span>Payment Terms:</span> {{ $invoice->payment_note ?? 'Payment due within 30 days' }}</p>
                    <p><span>Payment Method:</span> {{ $invoice->pay_method ?? $staff?->employee->pay_method ?? 'Bank Transfer' }}</p>
                </td>
                <td class="info-list">
                    <p><span>Bank Name:</span> {{ $payeeUser?->bank_name ?? $staff?->bank_name ?? '—' }}</p>
                    <p><span>Account Number:</span> {{ $payeeUser?->account_number ?? $staff?->account_number ?? '—' }}</p>
                    <p><span>Sort Code:</span> {{ $payeeUser?->ifsc ?? $staff?->sort_code ?? '—' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This payroll slip is generated by {{ brand_name() }} &bull; {{ brand_company() }}
    </div>

</div>
</body>
</html>
