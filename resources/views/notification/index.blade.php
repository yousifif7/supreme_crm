@extends('layouts.app')
@section('title', 'SPL Connect - Notifications')

@section('contents')
<div class="page-wrapper" style="min-height: 306px;">
			<div class="content">

				<!-- Breadcrumb -->
				<div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
					<div class="my-auto mb-2">
						<h2 class="mb-1">Notifications</h2>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="index.html"><i class="ti ti-smart-home"></i></a>
								</li>
								
								<li class="breadcrumb-item active" aria-current="page">Notifications List</li>
							</ol>
						</nav>

					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
						
						  <div class="mb-3">
        <button id="markReadBtn" class="btn btn-success">Mark as Read</button>
        <button id="deleteBtn" class="btn btn-danger">Delete</button>
    </div>
					</div>
				</div>
				<!-- /Breadcrumb -->

            <div class="container">
    <h1>Notifications</h1>

    <div class="row mb-3 g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small mb-1" for="startDate">Start Date</label>
            <input id="startDate" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1" for="endDate">End Date</label>
            <input id="endDate" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1" for="notificationSearch">Search</label>
            <input id="notificationSearch" type="search" class="form-control form-control-sm" placeholder="Search text...">
        </div>
        <div class="col-auto">
            <button id="clearFilters" class="btn btn-light btn-sm">Clear</button>
        </div>
    </div>

    {!! $dataTable->table(['class' => 'table table-bordered table-striped']) !!}
</div>
				<!-- /Leads List -->

			</div>


		</div>

		@endsection
		@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
$(document).ready(function() {

    // Select all checkbox
    $('#selectAll').on('click', function() {
        $('.dT-row-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Wire filters to DataTable: add filter params to ajax and redraw on change
    var notificationsTable = window.LaravelDataTables && window.LaravelDataTables['notifications-table'] ? window.LaravelDataTables['notifications-table'] : null;

    // Attach preXhr to include filter params when server-side processing occurs
    $('#notifications-table').on('preXhr.dt', function(e, settings, data) {
        data.start_date = $('#startDate').val();
        data.end_date = $('#endDate').val();
        data.search_text = $('#notificationSearch').val();
    });

    // Trigger redraw when filter inputs change
    $('#startDate, #endDate').on('change', function() {
        if(notificationsTable){
            notificationsTable.draw();
        } else {
            $('#notifications-table').DataTable().draw();
        }
    });

    // Search box: keyup with debounce
    var searchTimer;
    $('#notificationSearch').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if(notificationsTable){
                notificationsTable.draw();
            } else {
                $('#notifications-table').DataTable().draw();
            }
        }, 300);
    });

    // Clear filters button
    $('#clearFilters').on('click', function(e){
        e.preventDefault();
        $('#startDate').val('');
        $('#endDate').val('');
        $('#notificationSearch').val('');
        if(notificationsTable){
            notificationsTable.draw();
        } else {
            $('#notifications-table').DataTable().draw();
        }
    });

    // Bulk mark as read
    $('#markReadBtn').on('click', function() {
        var ids = $('.dT-row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if(ids.length === 0){
            alert('Please select at least one notification.');
            return;
        }

        $.post("{{ route('notifications.bulkMarkAsRead') }}", {
            ids: ids,
            _token: "{{ csrf_token() }}"
        }, function(data) {
            alert(data.message);
            window.LaravelDataTables['notifications-table'].ajax.reload();
        });
    });

    // Bulk delete
    $('#deleteBtn').on('click', function() {
        if(!confirm('Are you sure you want to delete selected notifications?')) return;

        var ids = $('.dT-row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if(ids.length === 0){
            alert('Please select at least one notification.');
            return;
        }

        $.post("{{ route('notifications.bulkDelete') }}", {
            ids: ids,
            _token: "{{ csrf_token() }}"
        }, function(data) {
            alert(data.message);
            window.LaravelDataTables['notifications-table'].ajax.reload();
        });
    });

});
</script>
@endpush