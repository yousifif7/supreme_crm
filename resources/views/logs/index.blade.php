@extends('layouts.app')
@section('title', 'SPL Connect - LOGs')
@section('contents')
<div id="employment-report" class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <h2 class="mb-1">Edit LOGs</h2>
        </div>

        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-3">
                <label for="filterFromDate" class="form-label">From Date</label>
                <input type="date" id="filterFromDate" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filterToDate" class="form-label">To Date</label>
                <input type="date" id="filterToDate" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="button" id="resetDateFilter" class="btn btn-outline-secondary w-100">Reset</button>
            </div>
        </div>

        <table id="logsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- jQuery + DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    const table = $('#logsTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        pageLength: 25,
        lengthMenu: [[25, 50, 100, 250], [25, 50, 100, 250]],
        order: [[3, 'desc']], // default sort by Date (latest first)
        ajax: {
            url: "{{ route('logs.index') }}",
            type: 'GET',
            data: function(d) {
                d.from_date = $('#filterFromDate').val();
                d.to_date   = $('#filterToDate').val();
            }
        },
        columns: [
            { data: 'user_name',   name: 'user_name' },
            { data: 'action',      name: 'action' },
            { data: 'description', name: 'description' },
            { data: 'created_at',  name: 'created_at' }
        ]
    });

    let dateFilterDebounce;
    $('#filterFromDate, #filterToDate').on('change', function() {
        clearTimeout(dateFilterDebounce);
        dateFilterDebounce = setTimeout(() => table.draw(), 150);
    });

    $('#resetDateFilter').on('click', function() {
        $('#filterFromDate').val('');
        $('#filterToDate').val('');
        table.draw();
    });
});

</script>
@endpush
