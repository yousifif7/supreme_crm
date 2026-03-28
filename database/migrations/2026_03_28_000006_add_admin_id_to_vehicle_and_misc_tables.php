<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'training_materials',
            'roadworthiness_checks',
            'dob_entries',
            'vehicle_compliances',
            'vehicle_maintenances',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'admin_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('admin_id')->nullable()->index()->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'training_materials',
            'roadworthiness_checks',
            'dob_entries',
            'vehicle_compliances',
            'vehicle_maintenances',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'admin_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('admin_id');
                });
            }
        }
    }
};
