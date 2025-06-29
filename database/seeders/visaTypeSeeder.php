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
        VisaType::firstOrCreate(['name' => 'Indefinite leave to remain (ILR)']);
        VisaType::firstOrCreate(['name' => 'Limited leave to remain']);
        VisaType::firstOrCreate(['name' => 'Spouse']);
        VisaType::firstOrCreate(['name' => 'Student']);
        VisaType::firstOrCreate(['name' => 'Other']);
        VisaType::firstOrCreate(['name' => 'Skill Worker']);
        VisaType::firstOrCreate(['name' => 'Independant']);
        VisaType::firstOrCreate(['name' => 'Settlement']);
        VisaType::firstOrCreate(['name' => 'British']);
        VisaType::firstOrCreate(['name' => 'Spanish']);
        VisaType::firstOrCreate(['name' => 'Italin']);
    }
}
