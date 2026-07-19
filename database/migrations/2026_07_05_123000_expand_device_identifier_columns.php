<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index including new_device_id cannot exist on TEXT without a prefix.
        $this->dropIndexIfExists('device_change_requests', 'device_change_user_device_status_idx');

        Schema::table('device_logs', function (Blueprint $table) {
            $table->text('device_id')->change();
        });

        Schema::table('device_change_requests', function (Blueprint $table) {
            $table->text('old_device_id')->nullable()->change();
            $table->text('new_device_id')->change();
        });

        // Keep a usable lookup index without the TEXT column.
        if (!$this->indexExists('device_change_requests', 'device_change_user_status_idx')) {
            Schema::table('device_change_requests', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'device_change_user_status_idx');
            });
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('device_change_requests', 'device_change_user_status_idx');

        Schema::table('device_logs', function (Blueprint $table) {
            $table->string('device_id')->change();
        });

        Schema::table('device_change_requests', function (Blueprint $table) {
            $table->string('old_device_id')->nullable()->change();
            $table->string('new_device_id')->change();
        });

        if (!$this->indexExists('device_change_requests', 'device_change_user_device_status_idx')) {
            Schema::table('device_change_requests', function (Blueprint $table) {
                $table->index(['user_id', 'new_device_id', 'status'], 'device_change_user_device_status_idx');
            });
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (!$this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        $db = DB::getDatabaseName();
        try {
            $row = DB::selectOne(
                'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$db, $table, $name]
            );
            return ((int) ($row->c ?? 0)) > 0;
        } catch (\Throwable $e) {
            // Hostinger may block information_schema — try drop and ignore failure later.
            return true;
        }
    }
};
