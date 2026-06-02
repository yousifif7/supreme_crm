<?php

namespace Database\Seeders;

use App\Models\VisaType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

     /* RoleSeeder::class,
            UserSeeder::class,
            employeeTypeSeeder::class,
            licenseSeeder::class,
            permissionSeeder::class,
            RestrictionSeeder::class,
            visaTypeSeeder::class,*/
    public function run(): void
    {
        $this->call([
            // ApplicationForm::class,
            // ApplicationFormUndertaking::class,
            // DigitalForm::class,
            // DigitalFormSubmit::class,
            // Incident::class,
            // DynamicInput::class,
            // PageSeeder::class,
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
