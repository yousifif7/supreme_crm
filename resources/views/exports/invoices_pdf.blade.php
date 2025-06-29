<!DOCTYPE html>
<html>

<head>
    <title>Sites PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <h2>Site List</h2>
    <table>
        <thead>
            <tr>
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
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->invoice_title }}</td>
                    <td>{{ $invoice->client?->client_name }}</td>
                    <td>{{ $invoice->site?->site_name }}</td>
                    <td>{{ $invoice->invoice_date }}</td>
                    <td>{{ $invoice->due_date }}</td>
                    <td>{{ $invoice->total_shift_hours }}</td>
                    <td>{{ $invoice->net_amount }}</td>
                    <td>{{ $invoice->paid_amount }}</td>
                    <td>{{ $invoice->payment_date }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
