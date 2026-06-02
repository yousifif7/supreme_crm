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
        Schema::table('checkpoint_scans', function (Blueprint $table) {
            if (!Schema::hasColumn('checkpoint_scans', 'patrol_id')) {
                $table->unsignedBigInteger('patrol_id')->nullable()->after('id');
                $table->foreign('patrol_id')->references('id')->on('patrols')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('checkpoint_scans', 'patrol_checkpoint_id')) {
                $table->unsignedBigInteger('patrol_checkpoint_id')->nullable()->after('patrol_id');
                $table->foreign('patrol_checkpoint_id')->references('id')->on('patrol_check_points')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkpoint_scans', function (Blueprint $table) {
            if (Schema::hasColumn('checkpoint_scans', 'patrol_id')) {
                $table->dropForeign(['patrol_id']);
                $table->dropColumn('patrol_id');
            }
            if (Schema::hasColumn('checkpoint_scans', 'patrol_checkpoint_id')) {
                $table->dropForeign(['patrol_checkpoint_id']);
                $table->dropColumn('patrol_checkpoint_id');
            }
        });
    }
};
