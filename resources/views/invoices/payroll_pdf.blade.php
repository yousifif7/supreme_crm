<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payroll - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 14px; margin: 0; padding: 0; }
        .container { width: 700px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        h1 { text-align: center; margin-bottom: 10px; }
        .header, .section { margin-bottom: 20px; }
        .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .total { font-weight: bold; }
        .section-title { font-size: 16px; margin-bottom: 5px; text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>Payroll Slip</h1>

    <div class="header">
        <p><strong>Employee:</strong> {{ $invoice->securityStaff->first_name ?? 'Staff' }} {{ $invoice->securityStaff->last_name ?? '' }}</p>
        <p><strong>Payroll Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Period:</strong> {{ $invoice->date_from }} to {{ $invoice->date_to }}</p>
        <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
    </div>

    <div class="section">
        <div class="section-title">Leave Summary</div>
        <table>
            <thead>
            <tr>
                <th>Leave Type</th>
                <th>Paid Days/Hours</th>
                <th>Unpaid Days/Hours</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Sick Pay (SSP)</td>
                <td>{{ $invoice->ssp_paid_days ?? 0 }}</td>
                <td>{{ $invoice->ssp_unpaid_days ?? 0 }}</td>
                <td>£23.75/day after 3 waiting days, max 28 weeks</td>
            </tr>
            <tr>
                <td>Annual Holiday</td>
                <td>{{ $invoice->holiday_paid_hours ?? 0 }}</td>
                <td>{{ $invoice->holiday_unpaid_hours ?? 0 }}</td>
                <td>Accrual or fixed, pro-rated if needed</td>
            </tr>
            <tr>
                <td>Unpaid Leave</td>
                <td>0</td>
                <td>{{ $invoice->unpaid_leave_hours ?? 0 }}</td>
                <td>Applied automatically if balance exceeded</td>
            </tr>
            <tr>
                <td>Other Leave</td>
                <td>{{ $invoice->other_leave_paid_hours ?? 0 }}</td>
                <td>{{ $invoice->other_leave_unpaid_hours ?? 0 }}</td>
                <td>Marked Paid/Unpaid by manager</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Summary</div>
        <table>
            <tbody>
            <tr>
                <th>Total Amount</th>
                <td>£{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            @if($invoice->adminReview)
                <tr>
                    <th>Admin Revised Amount</th>
                    <td>£{{ number_format($invoice->adminReview->revised_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Revision Reason</th>
                    <td>{{ $invoice->adminReview->revision_reason }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
