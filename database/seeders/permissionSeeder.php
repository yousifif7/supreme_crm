<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class permissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'Read Security Board', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Security Board', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Security Board', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Security Board', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Security Board', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Security Board', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read User Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write User Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create User Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete User Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import User Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export User Management', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Clients', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Security Staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Security Staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Security Staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Security Staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Security Staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Security Staff', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Vehicle Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Vehicle Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Vehicle Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Vehicle Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Vehicle Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Vehicle Management', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Invoice Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Invoice Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Invoice Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Invoice Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Invoice Management', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Invoice Management', 'guard_name' => 'web']);
    }
}
