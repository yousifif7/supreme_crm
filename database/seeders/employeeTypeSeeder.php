<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class employeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create employeetype
        EmployeeType::firstOrCreate(['name' => 'Alarm Response']);
        EmployeeType::firstOrCreate(['name' => 'Doghandlers']);
        EmployeeType::firstOrCreate(['name' => 'Event Staff']);
        EmployeeType::firstOrCreate(['name' => 'Keyholding']);
        EmployeeType::firstOrCreate(['name' => 'Mobile Petrol']);
        EmployeeType::firstOrCreate(['name' => 'Static Guards']);
    }
}
