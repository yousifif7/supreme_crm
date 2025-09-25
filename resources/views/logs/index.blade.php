@extends('layouts.app')
@section('title', 'CRM - LOGs')
@section('contents')
<div id="employment-report" class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <h2 class="mb-1">Edit LOGs</h2>
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
<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, 'desc']], // default sort by Date (latest first)
    });
});

</script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- jQuery + DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush
