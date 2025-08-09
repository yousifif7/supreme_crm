<button class="sites_action-btn" onclick="viewPermissions({{ $role->id }})">permissions</button>
<a onclick="editRole({{ $role->id }})" class="me-2"><i class="ti ti-edit"></i></a>

@php
    $protectedRoles = ['superadmin', 'subcontractor', 'security_staff', 'client'];
@endphp

@if (!in_array(strtolower($role->name), $protectedRoles))
    <a onclick="deleteRole({{ $role->id }})"><i class="ti ti-trash"></i></a>
@endif
