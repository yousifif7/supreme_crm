<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    public function collection()
    {
        return User::whereDoesntHave('roles', function($query) {
            $query->whereIn('name', ['client', 'subcontractor', 'security_staff']);
        })->select('id', 'name', 'email', 'created_at')->get();
    }
}
