<div class="d-flex align-items-center file-name-icon">
    <div class="ms-2">
        <h6 class="fw-medium">
            <a onclick="viewSiteDetail({{ $site->id }})">
                {{ $site->client?->first_name ?? 'N/A' }}
            </a>
        </h6>
    </div>
</div>
