<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure the migrations table has an AUTO_INCREMENT primary key on `id`.
        // Some older installs may have `id` without auto-increment which prevents inserting migration records.
        try {
            DB::statement("ALTER TABLE `migrations` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
        } catch (\Exception $e) {
            // If it fails, don't fatal the deploy; provide a helpful log entry.
            logger()->warning('Failed to modify migrations.id to AUTO_INCREMENT: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op: reverting this change may be destructive on some setups.
    }
};
