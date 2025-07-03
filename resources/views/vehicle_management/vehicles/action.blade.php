<div class="action-icon d-inline-flex">
    <a onclick="editVehicle({{ $vehicle->id }})" class="me-2">
        <i class="ti ti-edit"></i>
    </a>
    <a href="javascript:void(0);" onclick="deleteVehicle({{ $vehicle->id }})">
        <i class="ti ti-trash"></i>
    </a>
</div>
