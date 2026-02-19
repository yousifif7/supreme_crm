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
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->user_name }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
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
    $.fn.dataTable.ext.search.push(function(settings, data) {
        if (settings.nTable.id !== 'logsTable') {
            return true;
        }

        const fromDate = $('#filterFromDate').val();
        const toDate = $('#filterToDate').val();
        const dateText = data[3];

        if (!dateText) {
            return true;
        }

        const rowDate = new Date(dateText.replace(' ', 'T'));
        if (Number.isNaN(rowDate.getTime())) {
            return true;
        }

        if (fromDate) {
            const minDate = new Date(fromDate + 'T00:00:00');
            if (rowDate < minDate) {
                return false;
            }
        }

        if (toDate) {
            const maxDate = new Date(toDate + 'T23:59:59');
            if (rowDate > maxDate) {
                return false;
            }
        }

        return true;
    });

    const table = $('#logsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[3, 'desc']], // default sort by Date (latest first)
    });

    $('#filterFromDate, #filterToDate').on('change', function() {
        table.draw();
    });

    $('#resetDateFilter').on('click', function() {
        $('#filterFromDate').val('');
        $('#filterToDate').val('');
        table.draw();
    });
});

</script>
@endpush
