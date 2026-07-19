<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retarget shift_id FKs from shifts → shift_dates.
 * Avoids information_schema (blocked on some Hostinger plans).
 */
return new class extends Migration
{
    /** @var array<string, bool> table => nullable shift_id */
    private array $tables = [
        'shift_bookings' => false,
        'dob_entries' => false,
        'incident_reports' => false,
        'booking_alarms' => true,
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $nullable) {
            $this->retargetShiftIdForeignKey($table, $nullable);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'shift_id')) {
                continue;
            }

            $this->tryDropForeign($table, 'shift_id');

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('shift_id')
                    ->references('id')
                    ->on('shifts')
                    ->cascadeOnDelete();
            });
        }
    }

    private function retargetShiftIdForeignKey(string $table, bool $nullable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'shift_id')) {
            return;
        }

        $this->tryDropForeign($table, 'shift_id');
        $this->cleanupOrphans($table, $nullable);

        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('shift_id')
                    ->references('id')
                    ->on('shift_dates')
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // FK may already point at shift_dates from a partial previous run.
            if (!str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw $e;
            }
        }
    }

    private function tryDropForeign(string $table, string $column): void
    {
        // Laravel default name — no information_schema lookup.
        $name = "{$table}_{$column}_foreign";

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropForeign($name);
            });
        } catch (\Throwable $e) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            } catch (\Throwable $ignored) {
                // Already dropped or never existed.
            }
        }
    }

    private function cleanupOrphans(string $table, bool $nullable): void
    {
        if ($nullable) {
            DB::statement(
                "UPDATE `{$table}` AS t
                 LEFT JOIN `shift_dates` sd ON sd.id = t.shift_id
                 SET t.shift_id = NULL
                 WHERE t.shift_id IS NOT NULL AND sd.id IS NULL"
            );

            return;
        }

        DB::statement(
            "DELETE t FROM `{$table}` AS t
             LEFT JOIN `shift_dates` sd ON sd.id = t.shift_id
             WHERE t.shift_id IS NOT NULL AND sd.id IS NULL"
        );
    }
};
