<!DOCTYPE html>
<html>

<head>
    <title>Subcontractors PDF</title>
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
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .small-col {
            width: 8%;
        }

        .medium-col {
            width: 12%;
        }

        .large-col {
            width: 15%;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Subcontractors List</h2>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="small-col">#</th>
                <th class="large-col">Company Name</th>
                <th class="large-col">Company Address</th>
                <th class="medium-col">Contact Person</th>
                <th class="medium-col">Contact Number</th>
                <th class="medium-col">Email</th>
                {{-- <th class="medium-col">Username</th>
                <th class="medium-col">Invoice Terms</th>
                <th class="medium-col">Payment Terms</th>
                <th class="small-col">Department</th>
                <th class="small-col">VAT Reg.</th>
                <th class="medium-col">VAT Number</th>
                <th class="small-col">Pay Rate</th>
                <th class="small-col">PMVA</th>
                <th class="small-col">Status</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($subcontractors as $index => $subcontractor)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="company-name">{{ $subcontractor->company_name }}</td>
                    <td>{{ $subcontractor->company_address }}</td>
                    <td>{{ $subcontractor->contact_person }}</td>
                    <td>{{ $subcontractor->contact_number }}</td>
                    <td>{{ $subcontractor->email }}</td>
                    {{-- <td>{{ $subcontractor->username }}</td>
                    <td>{{ $subcontractor->invoice_terms }}</td>
                    <td>{{ $subcontractor->payment_terms }}</td>
                    <td>{{ $subcontractor->department }}</td>
                    <td class="text-center">{{ $subcontractor->vat_registered ? 'Yes' : 'No' }}</td>
                    <td>{{ $subcontractor->vat_number }}</td>
                    <td class="text-center">{{ $subcontractor->pay_rate }}</td>
                    <td class="text-center">{{ $subcontractor->pmva_trained_officer ? 'Yes' : 'No' }}</td>
                    <td class="text-center">{{ $subcontractor->is_active ? 'Active' : 'Inactive' }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($subcontractors->count() == 0)
        <div style="text-align: center; margin-top: 50px; color: #666;">
            <p>No subcontractors found.</p>
        </div>
    @endif

    <div style="margin-top: 30px; font-size: 10px; color: #666;">
        <p>Total Records: {{ $subcontractors->count() }}</p>
        <p>Report generated on {{ date('F j, Y \a\t g:i A') }}</p>
    </div>
</body>

</html>
