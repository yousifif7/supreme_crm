@extends('layouts.app')
@section('title', 'CRM - Client Report')

@section('contents')
    <div class="page-wrapper" id="client-report">
        <div class="content">
            <h2 class="mb-3">Client Report</h2>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.clients') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                    placeholder="Client name, contact..." value="{{ $search }}">
                            </div>

                            <div class="col-md-3">
                                <label for="company_id" class="form-label">Company</label>
                                <select name="company_id" id="company_id" class="form-select select2">
                                    <option value="">All Companies</option>
                                    @foreach ($companies as $id => $name)
                                        <option value="{{ $id }}" {{ $selectedCompany == $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="manager_id" class="form-label">Manager</label>
                                <select name="manager_id" id="manager_id" class="form-select select2">
                                    <option value="">All Managers</option>
                                    @foreach ($managers as $id => $name)
                                        <option value="{{ $id }}" {{ $selectedManager == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Contract Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="active" {{ $selectedStatus == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="expired" {{ $selectedStatus == 'expired' ? 'selected' : '' }}>Expired
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3 mt-2">
                                <label for="contract_start" class="form-label">Contract Start After</label>
                                <input type="date" name="contract_start" class="form-control"
                                    value="{{ $contractStart }}">
                            </div>

                            <div class="col-md-3 mt-2">
                                <label for="contract_end" class="form-label">Contract End Before</label>
                                <input type="date" name="contract_end" class="form-control" value="{{ $contractEnd }}">
                            </div>

                            <div class="col-md-2 d-flex align-items-end mt-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>

                            <br>
                            <div class="d-flex gap-2 mb-3">
                                <a href="{{ route('reports.clients', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                                    class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                                <a href="{{ route('reports.clients', array_merge(request()->query(), ['export' => 'excel'])) }}"
                                    class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if ($clients->isEmpty())
                        <div class="alert alert-warning m-3">No clients match the filters.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped datatables">
                                <thead>
                                    <tr>
                                         <th>#</th>
                                        <th>Client Name</th>
                                        <th>Company</th>
                                        <th>Manager</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Contract Period</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clients as $key=>$client)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $client->client_name }}</td>
                                            <td>{{ $client->company->company_name ?? 'N/A' }}</td>
                                            <td>{{ $client->manager->fore_name ?? 'N/A' }}
                                                {{ $client->manager->sur_name ?? '' }}</td>
                                            <td>{{ $client->contact_person }}</td>
                                            <td>{{ $client->email }}</td>
                                            <td>
                                                {{ $client->contract_start ? \Carbon\Carbon::parse($client->contract_start)->format('d/m/Y') : 'N/A' }}
                                                -
                                                {{ $client->contract_end ? \Carbon\Carbon::parse($client->contract_end)->format('d/m/Y') : 'N/A' }}
                                            </td>
                                            <td>
                                                @if ($client->contract_end && \Carbon\Carbon::parse($client->contract_end)->isPast())
                                                    <span class="badge bg-danger">Expired</span>
                                                @else
                                                    <span class="badge bg-success">Active</span>
                                                @endif
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

@endsection

@section('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 JS (if not already included in your layout) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables core + Bootstrap 5 integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function () {
            $('.datatables').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search client..."
                }
            });
        });
    </script>

    <style>
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ccc;
            padding: 6px 10px;
            width: 250px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
        }
    </style>
@endsection
