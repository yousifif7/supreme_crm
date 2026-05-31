<!DOCTYPE html>
<html>

<head>
    <title>Document Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h3 {
            margin: 0 0 4px 0;
        }

        .meta {
            font-size: 10px;
            color: #555;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .doc-uploaded {
            color: #1a7f37;
        }

        .doc-missing {
            color: #b3261e;
        }

        .expired {
            color: #b3261e;
        }

        .valid {
            color: #1a7f37;
        }
    </style>
</head>

<body>
    <h3>Document Report</h3>
    <div class="meta">
        Generated {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ $employees->count() }} officer(s)
        @php
            $typeLabels = collect((array) $documentField)
                ->map(fn($f) => $documentFields[$f] ?? $f)
                ->implode(', ');
        @endphp
        @if ($typeLabels)
            &nbsp;|&nbsp; Document type(s): {{ $typeLabels }}
        @endif
    </div>

    @if ($employees->isEmpty())
        <p>No employees match the current filters.</p>
    @else
        @php
            $selectedFields = (array) $documentField;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width:30px;">ID</th>
                    <th>Name</th>
                    <th style="width:60px;">Status</th>
                    <th>Document Status</th>
                    <th>Expiry Date</th>
                    <th>Days Remaining</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td>{{ $employee->fore_name }} {{ $employee->sur_name }}</td>
                        <td>{{ ucfirst($employee->status) }}</td>
                        <td>
                            @php
                                $uploadedCount = 0;
                                $totalCount = 0;
                                $additionalFiles = $employee->additional_files ?? [];

                                foreach ($selectedFields as $field) {
                                    if ($field === 'other') {
                                        continue;
                                    }
                                    $totalCount++;
                                    $label = $documentFields[$field] ?? $field;
                                    if (!empty($employee->{$field})) {
                                        $uploadedCount++;
                                        echo '<div class="doc-uploaded">' . e($label) . ': Uploaded</div>';
                                    } else {
                                        echo '<div class="doc-missing">' . e($label) . ': Missing</div>';
                                    }
                                }

                                if (in_array('other', $selectedFields)) {
                                    $totalCount++;
                                    $matches = $otherDocument
                                        ? array_filter(
                                            $additionalFiles,
                                            fn($f) => stripos(basename($f), $otherDocument) !== false,
                                        )
                                        : $additionalFiles;

                                    if (!empty($matches)) {
                                        $uploadedCount++;
                                        foreach ($matches as $file) {
                                            echo '<div class="doc-uploaded">Other: ' . e($file) . '</div>';
                                        }
                                    } else {
                                        echo '<div class="doc-missing">Other: Missing</div>';
                                    }
                                }
                            @endphp
                            <strong>{{ $uploadedCount }}/{{ $totalCount }} documents</strong>
                        </td>
                        <td>
                            @php
                                $expiryDates = [];
                                foreach ($selectedFields as $field) {
                                    if (
                                        $field !== 'other' &&
                                        array_key_exists($field, $expiryFields) &&
                                        $employee->{$field}
                                    ) {
                                        $expiryDates[$field] = $employee->{$expiryFields[$field]};
                                    }
                                }
                            @endphp
                            @if ($expiryDates)
                                @foreach ($expiryDates as $field => $date)
                                    {{ $documentFields[$field] ?? $field }}:
                                    {{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'N/A' }}<br>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            @if ($expiryDates)
                                @foreach ($expiryDates as $field => $date)
                                    @php
                                        $daysRemaining = $date
                                            ? now()->diffInDays(\Carbon\Carbon::parse($date), false)
                                            : null;
                                    @endphp
                                    @if ($daysRemaining !== null)
                                        @if ($daysRemaining > 0)
                                            <span class="valid">{{ round($daysRemaining) }} days</span><br>
                                        @else
                                            <span class="expired">Expired {{ abs(round($daysRemaining)) }} days ago</span><br>
                                        @endif
                                    @endif
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>

</html>
