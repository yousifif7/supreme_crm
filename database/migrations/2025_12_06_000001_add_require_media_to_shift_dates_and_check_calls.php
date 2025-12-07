<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_dates', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_dates', 'require_media')) {
                $table->boolean('require_media')->default(false)->after('is_assign');
            }
        });

        Schema::table('check_calls', function (Blueprint $table) {
            if (!Schema::hasColumn('check_calls', 'require_media')) {
                $table->boolean('require_media')->default(false)->after('method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('check_calls', function (Blueprint $table) {
            if (Schema::hasColumn('check_calls', 'require_media')) {
                $table->dropColumn('require_media');
            }
        });

        Schema::table('shift_dates', function (Blueprint $table) {
            if (Schema::hasColumn('shift_dates', 'require_media')) {
                $table->dropColumn('require_media');
            }
        });
    }
};
