<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['jobs', 'failed_jobs'] as $table) {
            try {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
            } catch (\Throwable $e) {
                logger()->warning("fix_queue_table_autoincrement: skipped `{$table}`.id — " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Not reversed automatically.
    }
};