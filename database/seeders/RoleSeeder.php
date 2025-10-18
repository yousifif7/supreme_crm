<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin role if not exists
        $role = Role::firstOrCreate(['name' => 'superadmin']);
        $role1 = Role::firstOrCreate(['name' => 'client']);
        $role2 = Role::firstOrCreate(['name' => 'security_staff']);
        $role2 = Role::firstOrCreate(['name' => 'subcontractor']);

        // Assign all existing permissions to superadmin
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);
    }
}
