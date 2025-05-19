<?php

namespace App\Exports;

use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\FromCollection;

class RolesExport implements FromCollection
{
    public function collection()
    {
        return Role::select('id', 'name', 'guard_name', 'created_at')->get();
    }
}
