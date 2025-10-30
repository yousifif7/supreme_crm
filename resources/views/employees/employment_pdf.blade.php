<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Employment Report - {{ $employee->fore_name }} {{ $employee->sur_name }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        h2,
        h3 {
            margin-bottom: 6px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            width: 30%;
            background: #f5f5f5;
        }

        .section {
            margin-bottom: 25px;
        }
    </style>
</head>

<body>
    <h2>Employment Report</h2>
    <h3>{{ $employee->fore_name }} {{ $employee->sur_name }}</h3>

    {{-- Basic Info --}}
    <div class="section">
        <h3>Personal Information</h3>
        <table>
            <tr>
                <th>ID</th>
                <td>{{ $employee->id }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $employee->email }}</td>
            </tr>
            <tr>
                <th>Gender</th>
                <td>{{ ucfirst($employee->gender) }}</td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td>{{ optional($employee->dob)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Nationality</th>
                <td>{{ $employee->nationality }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $employee->address_group }} {{ $employee->address_group_additional }}</td>
            </tr>
            <tr>
                <th>Contact</th>
                <td>{{ $employee->contact }}</td>
            </tr>
            <tr>
                <th>Emergency Contact</th>
                <td>{{ $employee->emergency_contact }}</td>
            </tr>
        </table>
    </div>

    {{-- Employment --}}
    <div class="section">
        <h3>Employment Details</h3>
        <table>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($employee->status) }}</td>
            </tr>
            <tr>
                <th>Job Title</th>
                <td>{{ $employee->job_title }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $employee->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Service Type</th>
                <td>{{ $employee->service_type }}</td>
            </tr>
            <tr>
                <th>Employment Start</th>
                <td>{{ $start ? $start->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Employment End</th>
                <td>{{ $employee->employment_end_date ? $end->format('d/m/Y') : 'Present' }}</td>
            </tr>
            <tr>
                <th>Total Duration</th>
                <td>{{ $duration }}</td>
            </tr>
            <tr>
                <th>Hours per Week</th>
                <td>{{ $employee->hour_per_week }}</td>
            </tr>
            <tr>
                <th>Place of Work</th>
                <td>{{ $employee->place_work }}</td>
            </tr>
        </table>
    </div>

    {{-- Legal & Visa --}}
    <div class="section">
        <h3>Legal / Visa Information</h3>
        <table>
            <tr>
                <th>SIA Licence</th>
                <td>{{ $employee->sia_licence }}</td>
            </tr>
            <tr>
                <th>SIA Expiry</th>
                <td>{{ optional($employee->sia_expiry)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Licence Type</th>
                <td>{{ $employee->licence_type }}</td>
            </tr>
            <tr>
                <th>Visa Type</th>
                <td>{{ $employee->visatype->name ?? $employee->visa_type }}</td>
            </tr>
            <tr>
                <th>Visa Expiry</th>
                <td>{{ optional($employee->visa_expiry)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Passport No</th>
                <td>{{ $employee->passport_no }}</td>
            </tr>
            <tr>
                <th>Passport Expiry</th>
                <td>{{ optional($employee->passport_expiry)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>NI Number</th>
                <td>{{ $employee->ni_number }}</td>
            </tr>
        </table>
    </div>

    {{-- Bank --}}
    <div class="section">
        <h3>Bank Details</h3>
        <table>
            <tr>
                <th>Account Name</th>
                <td>{{ $employee->account_name }}</td>
            </tr>
            <tr>
                <th>Account Number</th>
                <td>{{ $employee->account_number }}</td>
            </tr>
            <tr>
                <th>Sort Code</th>
                <td>{{ $employee->sort_code }}</td>
            </tr>
            <tr>
                <th>Bank Name</th>
                <td>{{ $employee->bank_name }}</td>
            </tr>
            <tr>
                <th>Bank Branch</th>
                <td>{{ $employee->bank_branch }}</td>
            </tr>
        </table>
    </div>

    {{-- Next of Kin --}}
    <div class="section">
        <h3>Next of Kin</h3>
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $employee->next_kin }}</td>
            </tr>
            <tr>
                <th>Relation</th>
                <td>{{ $employee->relation_with_kin }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $employee->kin_address }}</td>
            </tr>
            <tr>
                <th>Contact</th>
                <td>{{ $employee->kin_number }}</td>
            </tr>
            <tr>
                <th>Mobile</th>
                <td>{{ $employee->kin_mobile }}</td>
            </tr>
            <tr>
                <th>Work Tel</th>
                <td>{{ $employee->kin_work_tel }}</td>
            </tr>
        </table>
    </div>

    {{-- Holidays --}}
    <div class="section">
        <h3>Holiday Entitlement</h3>
        <table>
            <tr>
                <th>Main Entitlement</th>
                <td>{{ $employee->holidays_entitlement }}</td>
            </tr>
            <tr>
                <th>From</th>
                <td>{{ optional($employee->holiday_from)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>To</th>
                <td>{{ optional($employee->holiday_to)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Additional Entitlement</th>
                <td>{{ $employee->holidays_entitlement_additional }}</td>
            </tr>
            <tr>
                <th>From</th>
                <td>{{ optional($employee->holiday_from_additional)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>To</th>
                <td>{{ optional($employee->holiday_to_additional)->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>
</body>

</html>
