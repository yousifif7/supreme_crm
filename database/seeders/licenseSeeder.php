<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class licenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create license
        License::create(['name' => 'CCTV']);
        License::create(['name' => 'Close Protection']);
        License::create(['name' => 'Door Supervision']);
        License::create(['name' => 'Key Holding']);
        License::create(['name' => 'Security Guarding']);
        License::create(['name' => 'Other']);
    }
}
