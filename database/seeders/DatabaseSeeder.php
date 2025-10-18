<?php

namespace Database\Seeders;

use App\Models\VisaType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            employeeTypeSeeder::class,
            licenseSeeder::class,
            permissionSeeder::class,
            RestrictionSeeder::class,
            visaTypeSeeder::class,

        ]);
    }
}
