<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent repair for schema gaps that break chat, leaves, check-calls,
 * and pinned conversations on Hostinger / partially-migrated DBs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix legacy typo table name if present
        if (Schema::hasTable('check_point_scans') && !Schema::hasTable('checkpoint_scans')) {
            Schema::rename('check_point_scans', 'checkpoint_scans');
        }

        $this->repairMessages();
        $this->repairMessageReads();
        $this->createUserPinnedConversations();
        $this->repairLeaveRequests();
        $this->repairCheckCalls();
        $this->repairClientsUserFk();
    }

    public function down(): void
    {
        // Non-destructive repair — intentional no-op on down.
    }

    private function repairMessages(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'conversation_id')) {
                $table->unsignedBigInteger('conversation_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('messages', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->nullable()->after('conversation_id');
            }
            if (!Schema::hasColumn('messages', 'message')) {
                $table->text('message')->nullable();
            }
            if (!Schema::hasColumn('messages', 'type')) {
                $table->string('type')->nullable();
            }
            if (!Schema::hasColumn('messages', 'attachment')) {
                $table->string('attachment')->nullable();
            }
            if (!Schema::hasColumn('messages', 'deleted')) {
                $table->boolean('deleted')->default(false);
            }
        });

        $this->tryForeign('messages', 'conversation_id', 'conversations', 'messages_conversation_id_foreign');
        $this->tryForeign('messages', 'sender_id', 'users', 'messages_sender_id_foreign');
    }

    private function repairMessageReads(): void
    {
        if (!Schema::hasTable('message_reads')) {
            return;
        }

        if (!Schema::hasColumn('message_reads', 'created_at')) {
            Schema::table('message_reads', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    private function createUserPinnedConversations(): void
    {
        if (Schema::hasTable('user_pinned_conversations')) {
            return;
        }

        Schema::create('user_pinned_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'conversation_id'], 'user_pinned_conversations_unique');
        });
    }

    private function repairLeaveRequests(): void
    {
        if (!Schema::hasTable('leave_requests')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('employee_id');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_hours')) {
                $table->decimal('approved_hours', 8, 2)->nullable()->after('hours');
            }
        });

        $this->tryForeign('leave_requests', 'shift_id', 'shift_dates', 'leave_requests_shift_id_foreign', true);
    }

    private function repairCheckCalls(): void
    {
        if (!Schema::hasTable('check_calls')) {
            return;
        }

        Schema::table('check_calls', function (Blueprint $table) {
            if (!Schema::hasColumn('check_calls', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('check_calls', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    private function repairClientsUserFk(): void
    {
        if (!Schema::hasTable('clients') || !Schema::hasColumn('clients', 'user_id')) {
            return;
        }

        $this->tryForeign('clients', 'user_id', 'users', 'clients_user_id_foreign', true);
    }

    private function tryForeign(
        string $table,
        string $column,
        string $references,
        string $name,
        bool $nullOnDelete = false
    ): void {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $name, $nullOnDelete) {
                $fk = $blueprint->foreign($column, $name)->references('id')->on($references);
                if ($nullOnDelete) {
                    $fk->nullOnDelete();
                } else {
                    $fk->cascadeOnDelete();
                }
            });
        } catch (\Throwable $e) {
            // Already exists or Hostinger duplicate-name — ignore.
        }
    }
};
