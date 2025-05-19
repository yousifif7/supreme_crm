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
        Permission::firstOrCreate(['name' => 'Read Users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Users', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Clients', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Sites', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Sites', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Sites', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Sites', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Sites', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Sites', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'Read Shifts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Write Shifts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create Shifts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete Shifts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Import Shifts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Export Shifts', 'guard_name' => 'web']);
    }
}
