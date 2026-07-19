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
        $table = $this->pivotTable();
        if ($table === null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (!Schema::hasColumn($table, 'role')) {
                $blueprint->string('role')->nullable()->after('unread_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = $this->pivotTable();
        if ($table === null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (Schema::hasColumn($table, 'role')) {
                $blueprint->dropColumn('role');
            }
        });
    }

    private function pivotTable(): ?string
    {
        if (Schema::hasTable('conversation_user')) {
            return 'conversation_user';
        }

        if (Schema::hasTable('conversation_users')) {
            return 'conversation_users';
        }

        return null;
    }
};
