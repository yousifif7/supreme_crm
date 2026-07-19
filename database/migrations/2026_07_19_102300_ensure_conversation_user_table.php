<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App/models use pivot table `conversation_user`, but the original migration
 * created `conversation_users`. Align production to the name the code expects.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversation_user')) {
            $this->ensureRoleColumn('conversation_user');
            return;
        }

        if (Schema::hasTable('conversation_users')) {
            Schema::rename('conversation_users', 'conversation_user');
            $this->ensureRoleColumn('conversation_user');
            return;
        }

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('unread_count')->default(0);
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'conversation_user_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('conversation_user') && !Schema::hasTable('conversation_users')) {
            Schema::rename('conversation_user', 'conversation_users');
        }
    }

    private function ensureRoleColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'role')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('role')->nullable()->after('unread_count');
        });
    }
};
