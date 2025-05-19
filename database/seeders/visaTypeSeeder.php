<?php

namespace Database\Seeders;

use App\Models\VisaType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class visaTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VisaType::create(['name' => 'Indefinite leave to remain (ILR)']);
        VisaType::create(['name' => 'Limited leave to remain']);
        VisaType::create(['name' => 'Spouse']);
        VisaType::create(['name' => 'Student']);
    }
}
