<div class="action-icon d-inline-flex">
    {{--<button onclick="viewLogs({{ $site->id }})"
        class="sites_action-btn">Logs</button>--}}
    <a href="#" class="me-2"
        onclick="viewSiteDetail({{ $site->id }})"><i
            class="ti ti-eye"></i></a>
    <a href="#" class="me-2" onclick="editSite({{ $site->id }})"><i
            class="ti ti-edit"></i></a>
    <a onclick="deleteSite({{ $site->id }})"><i
            class="ti ti-trash"></i></a>
</div>
