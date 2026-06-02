<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('training_materials', 'implementation_date')) {
                $table->date('implementation_date')->nullable()->after('expiry_date');
            }
            if (!Schema::hasColumn('training_materials', 'deadline')) {
                $table->date('deadline')->nullable()->after('implementation_date');
            }
            if (!Schema::hasColumn('training_materials', 'acknowledge_by_date')) {
                $table->date('acknowledge_by_date')->nullable()->after('deadline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            if (Schema::hasColumn('training_materials', 'implementation_date')) {
                $table->dropColumn('implementation_date');
            }
            if (Schema::hasColumn('training_materials', 'deadline')) {
                $table->dropColumn('deadline');
            }
            if (Schema::hasColumn('training_materials', 'acknowledge_by_date')) {
                $table->dropColumn('acknowledge_by_date');
            }
        });
    }
};
