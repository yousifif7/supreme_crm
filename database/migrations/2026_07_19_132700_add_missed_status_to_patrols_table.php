<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cron marks overdue pending patrols as `missed`, but the original enum
 * only allowed pending/in_progress/completed — so saves were rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('patrols') || !Schema::hasColumn('patrols', 'status')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `patrols`
             MODIFY COLUMN `status`
             ENUM('pending','in_progress','completed','missed')
             NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('patrols') || !Schema::hasColumn('patrols', 'status')) {
            return;
        }

        // Move any missed rows back before shrinking the enum.
        DB::table('patrols')->where('status', 'missed')->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE `patrols`
             MODIFY COLUMN `status`
             ENUM('pending','in_progress','completed')
             NOT NULL DEFAULT 'pending'"
        );
    }
};
