@extends('layouts.app')
@section('title', 'CRM - Booking Report')

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