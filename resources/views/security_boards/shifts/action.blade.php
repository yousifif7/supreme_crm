<div class="action-icon d-inline-flex">
    <a href="javascript:void(0);" class="me-2" data-toggle="ajax-modal" data-title="Rota Detail" data-href="{{ route('shifts.show', $shiftDate->id) }}" data-width="80%">
        <i class="ti ti-eye"></i>
    </a>
    <a href="javascript:void(0);" class="me-2" onclick="editShift({{ $shiftDate->id }})">
        <i class="ti ti-edit"></i>
    </a>
    <a href="javascript:void(0);" onclick="deleteShift({{ $shiftDate->id }})">
        <i class="ti ti-trash"></i>
    </a>
</div>
