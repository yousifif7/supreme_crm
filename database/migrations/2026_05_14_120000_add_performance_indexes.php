<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add indexes that the shift-detail page (and other heavy listings) need.
 *
 * Profiling revealed these queries were doing full table scans because the
 * foreign keys had no covering index:
 *   - model_has_roles had NO indexes at all → every Spatie hasRole/role()
 *     check scanned the whole table.
 *   - patrols.shift_id, checkpoint_scans.patrol_id, logs.loggable_*
 *     foreign-key lookups had no index.
 *
 * Each step is wrapped in a try/catch so an already-existing index on a live
 * environment doesn't fail the whole migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('model_has_roles', ['model_id', 'model_type'], 'mhr_model_idx');
        $this->safeIndex('model_has_roles', ['role_id'], 'mhr_role_idx');

        $this->safeIndex('patrols', ['shift_id'], 'patrols_shift_id_idx');

        $this->safeIndex('checkpoint_scans', ['patrol_id'], 'checkpoint_scans_patrol_id_idx');

        $this->safeIndex('logs', ['loggable_id', 'loggable_type'], 'logs_loggable_idx');

        $this->safeIndex('shift_dates', ['shift_id'], 'shift_dates_shift_id_idx');

        $this->safeIndex('check_call_media', ['check_call_id'], 'ccm_check_call_idx');

        $this->safeIndex('patrol_media', ['patrol_id'], 'patrol_media_patrol_idx');

        $this->safeIndex('checkpoint_scan_media', ['checkpoint_scan_id'], 'csm_scan_idx');
    }

    public function down(): void
    {
        $this->safeDrop('model_has_roles', 'mhr_model_idx');
        $this->safeDrop('model_has_roles', 'mhr_role_idx');
        $this->safeDrop('patrols', 'patrols_shift_id_idx');
        $this->safeDrop('checkpoint_scans', 'checkpoint_scans_patrol_id_idx');
        $this->safeDrop('logs', 'logs_loggable_idx');
        $this->safeDrop('shift_dates', 'shift_dates_shift_id_idx');
        $this->safeDrop('check_call_media', 'ccm_check_call_idx');
        $this->safeDrop('patrol_media', 'patrol_media_patrol_idx');
        $this->safeDrop('checkpoint_scan_media', 'csm_scan_idx');
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) return;

        foreach ($columns as $col) {
            if (!Schema::hasColumn($table, $col)) return;
        }

        if ($this->indexExists($table, $name)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (\Throwable $e) {
            // Some DBs may already have an equivalent index under a different name; log and continue.
            \Illuminate\Support\Facades\Log::warning("[perf-index] failed to add {$name}: {$e->getMessage()}");
        }
    }

    private function safeDrop(string $table, string $name): void
    {
        if (!Schema::hasTable($table)) return;
        if (!$this->indexExists($table, $name)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[perf-index] failed to drop {$name}: {$e->getMessage()}");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        try {
            $rows = DB::select('SHOW INDEXES FROM `' . $table . '` WHERE Key_name = ?', [$name]);
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
