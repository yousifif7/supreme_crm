<div class="action-icon d-inline-flex">
    <button class="sites_action-btn" onclick="assignManager({{ $client->id }})">Managers</button>
    {{--<button class="sites_action-btn" onclick="viewLogs({{ $client->id }})">Logs</button>--}}
    <a href="#" class="me-2" onclick="viewClientDetail({{ $client->id }})"><i class="ti ti-eye"></i></a>
    <a href="#" class="me-2" onclick="editClient({{ $client->id }})"><i class="ti ti-edit"></i></a>

    <a href="#" class="me-2" onclick="generateInvoice({{ $client->user_id }})"><i class="ti ti-receipt"></i></a>

    <a href="javascript:void(0);" onclick="deleteClient({{ $client->id }})"><i class="ti ti-trash"></i></a>
</div>
