<!DOCTYPE html>
<html>

<head>
    <title>Vehicle Compliances</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>Vehicle Compliance Report</h2>
    <table>
        <thead>
            <tr>
                <th>MOT No</th>
                <th>MOT Expiry</th>
                <th>Insurance</th>
                <th>Policy No</th>
                <th>Insurance Expiry</th>
                <th>Tax Status</th>
                <th>Tax Expiry</th>
                <th>LEZ/ULEZ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($compliances as $row)
                <tr>
                    <td>{{ $row->mot_certificate_number }}</td>
                    <td>{{ $row->mot_expiry_date }}</td>
                    <td>{{ $row->insurance_provider }}</td>
                    <td>{{ $row->insurance_policy_number }}</td>
                    <td>{{ $row->insurance_expiry_date }}</td>
                    <td>{{ $row->vehicle_tax_status }}</td>
                    <td>{{ $row->tax_expiry_date }}</td>
                    <td>{{ $row->lez_ulez_compliant ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
