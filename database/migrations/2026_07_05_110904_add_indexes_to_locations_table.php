<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotent indexes for locations queries (safe to re-run on Hostinger).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('locations', 'patrol_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedBigInteger('patrol_id')->nullable()->after('shiftdate_id');
            });
        }

        $this->safeIndex('locations', ['user_id', 'timestamp'], 'locations_user_timestamp_idx');
        $this->safeIndex('locations', ['shiftdate_id', 'timestamp'], 'locations_shiftdate_timestamp_idx');
        $this->safeIndex('locations', ['shiftdate_id', 'created_at'], 'locations_shiftdate_created_idx');
        $this->safeIndex('locations', ['patrol_id', 'shiftdate_id'], 'locations_patrol_shiftdate_idx');
        $this->safeIndex('locations', ['user_id', 'created_at'], 'locations_user_created_idx');
    }

    public function down(): void
    {
        $this->safeDrop('locations', 'locations_user_timestamp_idx');
        $this->safeDrop('locations', 'locations_shiftdate_timestamp_idx');
        $this->safeDrop('locations', 'locations_shiftdate_created_idx');
        $this->safeDrop('locations', 'locations_patrol_shiftdate_idx');
        $this->safeDrop('locations', 'locations_user_created_idx');
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    private function safeDrop(string $table, string $name): void
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
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $name]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }
};
