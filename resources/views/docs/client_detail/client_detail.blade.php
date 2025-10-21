<?php $page="userlist";?>
@extends('layouts.app')
@section('contents')
@section('title') Client Detail List @endsection

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-wrapper">
    <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Form List</h2>
                @if (session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif
                {{-- show validation errors --}}
                @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            
        </div>
        <div class="card">
            <div class="card-body ">
<div class="row">
    <div class="col-md-12 mb-4">
        <form method="GET" action="{{ route('form_detail.index', ['id' => $digi->id]) }}">
            <div class="row">
                <div class="col-md-3">
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search...">
                </div>

                <div class="col-md-3">
                    <input type="date" name="created_at" value="{{ request('created_at') }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('form_detail.index', $digi->id) }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>
@if($digi->mail_desc && strip_tags($digi->mail_desc) != '')
<form method="POST" action="{{ route('mail.send') }}" id="sendMailForm">
    @csrf

    <div class="mt-3" style="    text-align: right;">
        <input type="hidden" name="selected_ids" id="selectedIdsInput">
          <input type="hidden" name="form_id" value="{{$digi->id}}">

        <button type="submit" class="btn btn-primary mb-2">Mail Send</button>
    </div>
</form>
@endif


                <div class="table-responsive">
                        <table id="sortableTable" class="table">
                            <thead>
                                <tr>
                                    @php
                                        $hasActionPermission = auth()->user()->can('client detail destroy') || auth()->user()->can('client detail edit') || auth()->user()->can('client detail show only') ;
                                        $dynamicFields = App\Models\Docs\DynamicInputs::where('child_id', 0)->where('parent_id',$digi->id)
                                            ->distinct()
                                            ->get();
                                    @endphp
                        
                                    @if ($hasActionPermission)
                                        <th>Action</th>
                                    @endif
                        
                                    @foreach ($dynamicFields->take(5) as $field) 
                                        <th class="sortable">{!! $field->title !!} ▲▼</th>
                                    @endforeach
                                    <th class="sortable">Created At ▲▼</th>
                                    @if($digi->mail_desc && strip_tags($digi->mail_desc) != '')
                                    <th><input type="checkbox" id="selectAllCheckbox"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inputs as $index => $input)
                                <tr>
                                    @php
                                        $decodedData = json_decode($input->name, true) ?? [];
                                        $limitedData = array_slice($decodedData, 0, 5, true);
                                    @endphp
                        
                                    @if($hasActionPermission)
                                    <td>
                                        @can('client detail edit')
                                        <a class="btn btn-xs btn-success editBtn btn-change" href="javascript:void(0);" data-id="{{ $input->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>                      
                                        @endcan       

                                        <a class="btn btn-xs btn-info btn-change" href="{{route('client.complete.detail.index',$input->id)}}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        @can('client detail destroy')
                                            <form method="POST" action="{{ route('clientdetail.destroy', $input->id) }}" style="display: contents;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="confirm-text btn btn-xs btn-danger btn-change">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                    @endif
                        
                                    @foreach ($limitedData as $key => $value)
                                        <td>
                                            @if(Str::startsWith($value, 'uploads/'))
                                                <a href="{{ asset($value) }}" target="_blank">
                                                    <img src="{{ asset($value) }}" alt="Uploaded Image" width="50" height="50" style="border-radius: 5px;">
                                                </a>
                                            @else
                                                {!! $value !!}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>{{ \Carbon\Carbon::parse($input->created_at)->format('Y-m-d H:i') }}</td>
                                    @if($digi->mail_desc && strip_tags($digi->mail_desc) != '')
                                    <td>
                                        <td><input type="checkbox" class="rowCheckbox" name="selected_ids[]" value="{{ $input->id }}"></td>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                <div class="col-md-12 text-right">
                    {{ $inputs->appends(['search' => request('search'), 'per_page' => request('per_page')])->links('pagination::bootstrap-5') }}

                </div>

                    </div>

            </div>
        </div>
        <!-- /product list -->
    </div>
</div>
<!-- Edit Modal -->
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Time Stamp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    @csrf
                    <input type="hidden" id="edit_id" name="id"> 

                    <label for="edit_date">Select Date & Time:</label>
                    <input type="text" id="edit_date" name="created_at" class="form-control" required>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button> <!-- FIX: type="submit" -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
@section('script')
<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on("click", ".editBtn", function () {
        var id = $(this).data('id');
        var editUrl = "{{ route('form_detail.edit', ':id') }}".replace(':id', id);

        $.ajax({
            url: editUrl,
            type: "GET",
            success: function (response) {
                $("#edit_id").val(response.id);

                let formattedDate = response.created_at
                    ? response.created_at.replace(" ", "T")
                    : new Date().toISOString().slice(0, 16);

                $("#edit_date").val(formattedDate);

                flatpickr("#edit_date", {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    defaultDate: formattedDate
                });

                $("#editModal").modal('show');
            }
        });
    });

    $("#editForm").submit(function (e) {
        e.preventDefault();
        var id = $("#edit_id").val();
        var updateUrl = "{{ route('form_detail.update', ':id') }}".replace(':id', id);
        var formData = $(this).serialize();

        $.ajax({
            url: updateUrl,
            type: "POST",
            data: formData,
            success: function (response) {
                toastr.success(response.message);
                $("#editModal").modal('hide');
                location.reload();
            },
            error: function (xhr) {
                toastr.error("Something went wrong!");
                console.log(xhr.responseText);
            }
        });
    });

});
</script>

<script>
    $(document).ready(function () {
        $(".sortable").on("click", function () {
            var columnIndex = $(this).index();
            var table = $("#sortableTable tbody");
            var rows = table.find("tr").toArray();
            var isAscending = $(this).data("order") === "asc";

            rows.sort(function (a, b) {
                var aText = $(a).find("td").eq(columnIndex).text().trim();
                var bText = $(b).find("td").eq(columnIndex).text().trim();

                // Date Sorting Check
                if ($(a).find("td").eq(columnIndex).text().includes("-")) {
                    return isAscending
                        ? new Date(aText) - new Date(bText)
                        : new Date(bText) - new Date(aText);
                }

                return isAscending
                    ? aText.localeCompare(bText)
                    : bText.localeCompare(aText);
            });

            $(this).data("order", isAscending ? "desc" : "asc");
            table.empty().append(rows);
        });
        
                $('#selectAllCheckbox').on('change', function () {
            $('.rowCheckbox').prop('checked', $(this).prop('checked'));
        });

        $('.rowCheckbox').on('change', function () {
            $('#selectAllCheckbox').prop('checked', $('.rowCheckbox:checked').length === $('.rowCheckbox').length);
        })
    });
    
    
    
    $(document).ready(function () {
    const $selectAllCheckbox = $('#selectAllCheckbox');
    const $rowCheckboxes = $('.rowCheckbox');
    const $form = $('#sendMailForm');
    const $hiddenInput = $('#selectedIdsInput');

    // Select All logic
    $selectAllCheckbox.on('change', function () {
        const isChecked = $(this).is(':checked');
        $rowCheckboxes.prop('checked', isChecked).each(function () {
            $(this).closest('tr').toggleClass('table-primary', isChecked);
        });
    });

    // Row checkbox change logic
    $rowCheckboxes.on('change', function () {
        $(this).closest('tr').toggleClass('table-primary', $(this).is(':checked'));

        const total = $rowCheckboxes.length;
        const checked = $('.rowCheckbox:checked').length;
        $selectAllCheckbox.prop('checked', total === checked);
    });

    // Form submit logic
    $form.on('submit', function (e) {
        const selected = $('.rowCheckbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one client to send the mail.');
            return;
        }

        $hiddenInput.val(JSON.stringify(selected));
    });
});
</script>



@endsection