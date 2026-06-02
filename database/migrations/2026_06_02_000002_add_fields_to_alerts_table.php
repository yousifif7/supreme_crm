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
        Schema::table('alerts', function (Blueprint $table) {
            $table->unsignedBigInteger('guard_id')->nullable()->after('id');
            $table->text('message')->nullable()->after('guard_id');
            $table->string('priority')->default('normal')->after('message');
            $table->boolean('trigger_alarm')->default(false)->after('priority');
            $table->unsignedBigInteger('sent_by_user_id')->nullable()->after('trigger_alarm');
            
            // Add foreign keys
            $table->foreign('guard_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sent_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropForeign(['guard_id', 'sent_by_user_id']);
            $table->dropColumn(['guard_id', 'message', 'priority', 'trigger_alarm', 'sent_by_user_id']);
        });
    }
};
