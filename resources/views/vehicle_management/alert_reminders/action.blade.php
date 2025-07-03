<div class="action-icon d-inline-flex">
    <a href="#" class="me-2" onclick="editReminder({{ $row->id }})">
        <i class="ti ti-edit"></i>
    </a>
    <a href="javascript:void(0);" onclick="deleteReminder({{ $row->id }})">
        <i class="ti ti-trash"></i>
    </a>
</div>
