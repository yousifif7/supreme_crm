<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shifts', 'staff_id')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('id');
            });
        }

        try {
            Schema::table('shifts', function (Blueprint $table) {
                $table->foreign('staff_id')->references('id')->on('users')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // FK may already exist
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('shifts', 'staff_id')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            try {
                $table->dropForeign(['staff_id']);
            } catch (\Throwable $e) {
            }
            $table->dropColumn('staff_id');
        });
    }
};
