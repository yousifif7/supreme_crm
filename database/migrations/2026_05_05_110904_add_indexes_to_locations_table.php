<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes directly target the queries responsible for "Creating sort index"
     * (full filesort) on the locations table that are causing high CPU / iowait:
     *
     *  1. (user_id, timestamp)       — covers WHERE user_id=? ORDER BY timestamp [DESC]
     *  2. (shiftdate_id, timestamp)  — covers WHERE shiftdate_id=? AND accuracy<=? ORDER BY timestamp
     *  3. (shiftdate_id, created_at) — covers WHERE patrol_id=? AND shiftdate_id=? ORDER BY created_at
     *  4. (patrol_id, shiftdate_id)  — covers WHERE patrol_id=? AND shiftdate_id=? lookups
     *  5. (user_id, created_at)      — covers dashboard "latest per user" subquery
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['user_id', 'timestamp'],       'locations_user_timestamp_idx');
            $table->index(['shiftdate_id', 'timestamp'],  'locations_shiftdate_timestamp_idx');
            $table->index(['shiftdate_id', 'created_at'], 'locations_shiftdate_created_idx');
            $table->index(['patrol_id', 'shiftdate_id'],  'locations_patrol_shiftdate_idx');
            $table->index(['user_id', 'created_at'],      'locations_user_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_user_timestamp_idx');
            $table->dropIndex('locations_shiftdate_timestamp_idx');
            $table->dropIndex('locations_shiftdate_created_idx');
            $table->dropIndex('locations_patrol_shiftdate_idx');
            $table->dropIndex('locations_user_created_idx');
        });
    }
};
