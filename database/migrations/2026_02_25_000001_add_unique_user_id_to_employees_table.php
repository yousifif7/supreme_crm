<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique index on employees.user_id to prevent the same user from
     * being linked to more than one employee record.
     *
     * Before running this migration you MUST resolve any existing duplicates,
     * otherwise it will fail with a duplicate-key error.  You can identify
     * duplicates with:
     *
     *   SELECT user_id, COUNT(*) as cnt
     *   FROM employees
     *   WHERE deleted_at IS NULL
     *   GROUP BY user_id
     *   HAVING cnt > 1;
     */
    public function up(): void
    {
        // De-duplicate first: keep the employee with the lowest id for each
        // user_id and soft-delete any extras so the unique index can be created.
        $duplicates = DB::table('employees')
            ->select('user_id', DB::raw('MIN(id) as keep_id'))
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('employees')
                ->where('user_id', $row->user_id)
                ->where('id', '!=', $row->keep_id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }

        Schema::table('employees', function (Blueprint $table) {
            // A user should only ever be linked to one (non-deleted) employee.
            // We use a regular unique index here; soft-deleted rows still occupy
            // the index slot, so the application must hard-delete or nullify
            // user_id when removing employees that should be re-creatable.
            $table->unique('user_id', 'employees_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_user_id_unique');
        });
    }
};
