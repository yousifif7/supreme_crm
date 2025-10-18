<div class="action-icon d-inline-flex">
    {{--<button onclick="viewLogs({{ $site->id }})"
        class="sites_action-btn">Logs</button>--}}
    <a href="#" class="me-2 site-view" data-id="{{ $site->id }}"><i class="ti ti-eye"></i></a>
    <a href="#" class="me-2 site-edit" data-id="{{ $site->id }}"><i class="ti ti-edit"></i></a>
    <a href="#" class="site-delete text-danger" data-id="{{ $site->id }}"><i class="ti ti-trash"></i></a>
</div>
