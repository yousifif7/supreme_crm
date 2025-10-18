<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Security Board
            'Read Security Board', 'Write Security Board', 'Create Security Board', 'Delete Security Board', 'Import Security Board', 'Export Security Board',

            // User Management
            'Read User Management', 'Write User Management', 'Create User Management', 'Delete User Management', 'Import User Management', 'Export User Management',

            // Clients
            'Read Clients', 'Write Clients', 'Create Clients', 'Delete Clients', 'Import Clients', 'Export Clients',

            // Security Staff
            'Read Security Staff', 'Write Security Staff', 'Create Security Staff', 'Delete Security Staff', 'Import Security Staff', 'Export Security Staff',

            // Vehicle Management
            'Read Vehicle Management', 'Write Vehicle Management', 'Create Vehicle Management', 'Delete Vehicle Management', 'Import Vehicle Management', 'Export Vehicle Management',

            // Invoice Management
            'Read Invoice Management', 'Write Invoice Management', 'Create Invoice Management', 'Delete Invoice Management', 'Import Invoice Management', 'Export Invoice Management',

            // Restrictions
            'Read Restrictions', 'Write Restrictions', 'Create Restrictions', 'Delete Restrictions', 'Import Restrictions', 'Export Restrictions',

            // Holiday Management
            'Read Holiday Managment', 'Write Holiday Managment', 'Create Holiday Managment', 'Delete Holiday Managment', 'Import Holiday Managment', 'Export Holiday Managment',

            // HR Management
            'Read HR Managment', 'Write HR Managment', 'Create HR Managment', 'Delete HR Managment', 'Import HR Managment', 'Export HR Managment',

            // Reports Management
            'Read Reports Managment', 'Write Reports Managment', 'Create Reports Managment', 'Delete Reports Managment', 'Import Reports Managment', 'Export Reports Managment',

            // Chat
            'Read Chat', 'Write Chat', 'Create Chat', 'Delete Chat', 'Import Chat', 'Export Chat',
        ];

        // Create permissions if not exist
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Create or get admin role
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // Assign all permissions to admin
        $adminRole->syncPermissions(Permission::all());
    }
}
