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
        Schema::table('conversation_users', function (Blueprint $table) {
            if (!Schema::hasColumn('conversation_users', 'role')) {
                $table->string('role')->nullable()->after('unread_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_users', function (Blueprint $table) {
            if (Schema::hasColumn('conversation_users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
