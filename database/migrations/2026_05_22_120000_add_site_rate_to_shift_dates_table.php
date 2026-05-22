<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_dates', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_dates', 'site_rate')) {
                $table->decimal('site_rate', 8, 2)->nullable()->after('guard_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shift_dates', function (Blueprint $table) {
            if (Schema::hasColumn('shift_dates', 'site_rate')) {
                $table->dropColumn('site_rate');
            }
        });
    }
};
