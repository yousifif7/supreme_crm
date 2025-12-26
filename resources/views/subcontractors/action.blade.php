<div class="action-icon d-inline-flex">
    {{--<button class="sites_action-btn"
        onclick="viewLogs({{ $subcontractor->id }})">Logs</button>--}}
<a href="#" class="me-2" onclick="viewSubcontractorDetail({{ $subcontractor->id }})">
    <i class="ti ti-eye"></i>
</a>
    <a href="#" class="me-2"
        onclick="editSubcontractor({{ $subcontractor->id }})"><i
            class="ti ti-edit"></i></a>
                {{-- <a href="#" class="me-2" onclick="generateInvoice({{ $subcontractor->user_id }})"><i class="ti ti-receipt"></i></a> --}}

    <a href="javascript:void(0);"
        onclick="deleteSubcontractor({{ $subcontractor->id }})"><i
            class="ti ti-trash"></i></a>
</div>
