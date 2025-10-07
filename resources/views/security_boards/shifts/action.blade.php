<div class="action-icon d-inline-flex">
<a href="{{ route('shiftDates.view', $shiftDate) }}" 
   class="me-2" 
   target="_blank">
    <i class="ti ti-eye"></i>
</a>
    </a>
    <a href="javascript:void(0);" class="me-2" onclick="editShift({{ $shiftDate->id }})">
        <i class="ti ti-edit"></i>
    </a>
    <a href="javascript:void(0);" onclick="deleteShift({{ $shiftDate->id }})">
        <i class="ti ti-trash"></i>
    </a>
</div>
