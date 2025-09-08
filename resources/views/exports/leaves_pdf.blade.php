<!DOCTYPE html>
<html>

<head>
    <title>Leaves PDF</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <h2>Leaves List {{ count($leaves) }}</h2>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Details</th>
                <th>Leave Type</th>
                <th>Date From</th>
                <th>Date To</th>
                <th>Status</th>
                <th>Reject Reason</th>
                <th>Hours Requested</th>
                <th>Approved Hours</th>
                <th>Paid</th>
                <th>SSP Paid Days</th>
                <th>Unpaid Days</th>
                <th>Amount Paid</th>
                <th>Applied At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leaves as $lv)
            @php
                $employee = App\Models\user::find($lv->user_id);
            @endphp
                <tr>
                    <td>{{ $employee?->first_name .' '. $employee?->last_name }}</td>
                    <td>{{ $lv->leave_entitlement }}</td>
                    <td>{{ ucwords($lv->type) }}</td>
                    <td>{{ $lv->from_date }}</td>
                    <td>{{ $lv->to_date }}</td>
                    <td>{{ ucwords($lv->status) }}</td>
                    <td>{{ $lv->reject_reason ?? '-' }}</td>
                    <td>{{ $lv->hours }}</td>
                    <td>{{ $lv->approved_hours }}</td>
                    <td>{{ $lv->paid ? 'Yes' : 'No' }}</td>
                    <td>{{ $lv->ssp_paid_days }}</td>
                    <td>{{ $lv->unpaid_days }}</td>
                    <td>{{ number_format($lv->amount_paid, 2) }}</td>
                    <td>{{ $lv->created_at?->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
