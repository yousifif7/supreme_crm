<div class="action-icon d-inline-flex">
    {{--<button class="sites_action-btn"
        onclick="viewLogs({{ $user->id }})">Logs</button>--}}
    <a href="#" class="me-2"
        onclick="viewUserDetail({{ $user->id }})"><i
            class="ti ti-eye"></i></a>
    <a onclick="editUser({{ $user->id }})" class="me-2"><i
            class="ti ti-edit"></i></a>
    <a href="javascript:void(0);" onclick="deleteUser({{ $user->id }})"><i
            class="ti ti-trash"></i></a>
</div>
