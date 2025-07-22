<div class="action-icon d-inline-flex">
    {{--<button class="sites_action-btn"
        onclick="viewLogs({{ $leave->id }})">Logs</button>--}}
    <a href="#" class="me-2"
        onclick="viewLeaveDetail({{ $leave->id }})"><i
            class="ti ti-eye"></i></a>
    <a onclick="editLeave({{ $leave->id }})" class="me-2"><i
            class="ti ti-edit"></i></a>
    <a href="javascript:void(0);" onclick="deleteLeave({{ $leave->id }})"><i
            class="ti ti-trash"></i></a>
</div>
