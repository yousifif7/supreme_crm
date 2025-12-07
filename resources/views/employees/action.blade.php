<div class="action-icon d-inline-flex">
    {{-- <button class="sites_action-btn"
        onclick="viewLogs({{ $employee->id }})">Logs</button> --}}
    <a href="#" class="me-2" onclick="viewEmployeeDetail({{ $employee->id }})"><i class="ti ti-eye"></i></a>
    <a class="me-2" onclick="editEmployee({{ $employee->id }})">
        <i class="ti ti-edit"></i>
    </a>

    {{-- @if (empty($employee->subcontractor))
        <a href="#" class="me-2" onclick="generatePayroll({{ $employee->user_id }})"><i
                class="ti ti-receipt"></i></a>
    @endif --}}
    <a onclick="deleteEmployee({{ $employee->id }})">
        <i class="ti ti-trash"></i>
    </a>
    <a href="{{ route('employees.print', $employee->id) }}" target="_blank">
        <i class="ti ti-printer"></i>
    </a>
</div>
