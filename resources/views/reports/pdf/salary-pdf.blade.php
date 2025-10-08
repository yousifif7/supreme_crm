<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Report</title>
    <style> body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px } table{width:100%;border-collapse:collapse} th,td{padding:6px;border:1px solid #ddd;text-align:left} .tot{font-weight:bold}</style>
</head>
<body>
    <h2>Salary Report</h2>
    <p>Staff: {{ $report['staff']->first_name ?? '' }} {{ $report['staff']->last_name ?? '' }}</p>
    <p>Period: {{ $report['date_from']->toDateString() }} — {{ $report['date_to']->toDateString() }}</p>

    <table>
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>Rate per hour</td><td>{{ $report['payroll']['rate'] ?? '' }}</td></tr>
        <tr><td>Total shift hours</td><td>{{ $report['payroll']['total_hours'] ?? '' }}</td></tr>
        <tr><td>Total breaks</td><td>{{ $report['payroll']['total_breaks'] ?? '' }}</td></tr>
        <tr><td>Gross</td><td>{{ $report['payroll']['gross_amount'] ?? '' }}</td></tr>
        <tr><td class="tot">Net</td><td class="tot">{{ $report['payroll']['net_amount'] ?? '' }}</td></tr>
    </table>

    <h4>Bank details</h4>
    <table>
        <tr><td>Bank</td><td>{{ $bankInfo['bank_name'] ?? '-' }}</td></tr>
        <tr><td>Account name</td><td>{{ $bankInfo['account_name'] ?? '-' }}</td></tr>
        <tr><td>Account number</td><td>{{ $bankInfo['account_number'] ?? '-' }}</td></tr>
        <tr><td>Sort code</td><td>{{ $bankInfo['sort_code'] ?? '-' }}</td></tr>
    </table>
</body>
</html>