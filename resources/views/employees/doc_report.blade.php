@extends('layouts.app')
@section('title', 'SPL Connect - Document Report')
@section('contents')
    <div id="all-workers" class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Document Report</h2>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="me-2 mb-2 filter_area"></div>

            <!-- Filters Card -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('documents.report') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="document_field" class="form-label">Document Type</label>
                                <select name="document_field[]" id="document_field" class="form-select document-type-select"
                                    multiple="multiple">
                                    @foreach ($documentFields as $field => $label)
                                        <option value="{{ $field }}"
                                            {{ in_array($field, (array) $documentField) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="other-document-input" class="col-md-2" style="display: none;">
                                <label for="upload_status" class="form-label">Custom search</label>
                                <input type="text" name="other_document" id="other_document" class="form-control"
                                    value="{{ request('other_document') }}" placeholder="Enter custom document name">
                            </div>

                            <div class="col-md-2">
                                <label for="upload_status" class="form-label">Upload Status</label>
                                <select name="upload_status" id="upload_status" class="form-select">
                                    <option value="">Any</option>
                                    <option value="uploaded" {{ $uploadStatus == 'uploaded' ? 'selected' : '' }}>Uploaded
                                    </option>
                                    <option value="missing" {{ $uploadStatus == 'missing' ? 'selected' : '' }}>Missing
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="expiry_status" class="form-label">Expiry Status</label>
                                <select name="expiry_status" id="expiry_status" class="form-select"
                                    {{ empty($documentField) || !collect((array) $documentField)->some(fn($f) => array_key_exists($f, $expiryFields)) || $uploadStatus == 'missing' ? 'disabled' : '' }}>
                                    <option value="">Any</option>
                                    <option value="valid" {{ $expiryStatus == 'valid' ? 'selected' : '' }}>Valid</option>
                                    <option value="expired" {{ $expiryStatus == 'expired' ? 'selected' : '' }}>Expired
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="status" class="form-label">Employee Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Card -->
            <div class="card mt-3">
                <div class="card-body p-0">
                    <div class="custom-datatable-filter table-responsive">
                        @if (!$hasFilters)
                            <div class="alert alert-info">Please apply filters to view results.</div>
                        @elseif($employees->isEmpty())
                            <div class="alert alert-warning">No employees match the current filters.</div>
                        @else
                            <div class="table-responsive">
                                <table id="employeeTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Document Status</th>
                                            <th>Expiry Date</th>
                                            <th>Days Remaining</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employees as $employee)
                                            <tr>
                                                <td>{{ $employee->id }}</td>
                                                <td>{{ $employee->fore_name }} {{ $employee->sur_name }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $employee->status == 'active' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($employee->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $selectedFields = (array) $documentField;
                                                        $uploadedCount = 0;
                                                        $totalCount = count($selectedFields); // each selected document counts as 1
                                                        $additionalFiles = $employee->additional_files ?? [];
                                                        $otherDocument = $otherDocument ?? null;

                                                        foreach ($selectedFields as $field) {
                                                            if ($field !== 'other') {
                                                                if (!empty($employee->{$field})) {
                                                                    $uploadedCount++;
                                                                    echo '<span class="badge bg-success">' .
                                                                        ($documentFields[$field] ?? $field) .
                                                                        ' Uploaded</span> ';
                                                                } else {
                                                                    echo '<span class="badge bg-danger">' .
                                                                        ($documentFields[$field] ?? $field) .
                                                                        ' Missing</span> ';
                                                                }
                                                            }
                                                        }

                                                        $otherDocument =
                                                            $otherDocument ?? (request('other_document') ?? null);

                                                        if (in_array('other', $selectedFields)) {
                                                            $totalCount++; // "Other" counts as 1 document type
                                                            $matches = $otherDocument
                                                                ? array_filter(
                                                                    $additionalFiles,
                                                                    fn($f) => stripos(basename($f), $otherDocument) !==
                                                                        false,
                                                                )
                                                                : $additionalFiles;

                                                            if (!empty($matches)) {
                                                                $uploadedCount++; // increment once for this document type
                                                                foreach ($matches as $file) {
                                                                    echo '<span class="badge bg-success">Other: ' .
                                                                        $file .
                                                                        '</span> ';
                                                                }
                                                            } else {
                                                                echo '<span class="badge bg-danger">Other: Missing</span> ';
                                                            }
                                                        }
                                                    @endphp
                                                    <span class="badge bg-{{ $uploadedCount > 0 ? 'success' : 'danger' }}">
                                                        {{ $uploadedCount }}/{{ $totalCount }} documents
                                                    </span>

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
                                                                $expiryDates[$field] =
                                                                    $employee->{$expiryFields[$field]};
                                                            }
                                                        }
                                                    @endphp

                                                    @if ($expiryDates)
                                                        @foreach ($expiryDates as $field => $date)
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
                                                                    ? now()->diffInDays(
                                                                        \Carbon\Carbon::parse($date),
                                                                        false,
                                                                    )
                                                                    : null;
                                                            @endphp
                                                            @if ($daysRemaining !== null)
                                                                @if ($daysRemaining > 0)
                                                                    <span
                                                                        class="badge bg-success">{{ round($daysRemaining) }}
                                                                        days</span>
                                                                @else
                                                                    <span class="badge bg-danger">Expired
                                                                        {{ abs(round($daysRemaining)) }} days ago</span>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ url('employees#' . $employee->id) }}"
                                                        class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize select2 for both fields
            $('#document_field').select2({
                placeholder: "Select document type(s)",
                allowClear: true,
                width: '100%'
            });
        });
    </script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#employeeTable').DataTable({
                pageLength: 10,
                ordering: true,
                searching: true,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employees..."
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const documentFieldSelect = document.getElementById('document_field');
            const uploadStatusSelect = document.getElementById('upload_status');
            const expiryStatusSelect = document.getElementById('expiry_status');
            const expiryCapableFields = @json(array_keys($expiryFields));

            function updateExpiryStatusDisabledState() {
                const selectedFields = Array.from(documentFieldSelect.selectedOptions).map(opt => opt.value);
                const uploadStatus = uploadStatusSelect.value;

                if (selectedFields.some(f => expiryCapableFields.includes(f)) && uploadStatus !== 'missing') {
                    expiryStatusSelect.disabled = false;
                } else {
                    expiryStatusSelect.disabled = true;
                    expiryStatusSelect.value = '';
                }
            }

            documentFieldSelect.addEventListener('change', updateExpiryStatusDisabledState);
            uploadStatusSelect.addEventListener('change', updateExpiryStatusDisabledState);

            updateExpiryStatusDisabledState();
        });

        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById("document_field");
            const otherInput = document.getElementById("other-document-input");

            function toggleOtherInput() {
                const selected = Array.from(select.selectedOptions).map(opt => opt.value);
                if (selected.includes("other")) {
                    otherInput.style.display = "block";
                } else {
                    otherInput.style.display = "none";
                    document.getElementById("other_document").value = "";
                }
            }

            // Run on load
            toggleOtherInput();

            // Run on change
            select.addEventListener("change", toggleOtherInput);
        });
    </script>
@endsection
