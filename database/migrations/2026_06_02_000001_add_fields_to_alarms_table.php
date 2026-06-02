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
        Schema::table('alarms', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->text('description')->nullable()->after('user_id');
            $table->timestamp('triggered_at')->nullable()->after('description');
            $table->text('override_reason')->nullable()->after('triggered_at');
            $table->boolean('resolved')->default(false)->after('override_reason');
            
            // Add foreign key for user_id
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarms', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'description', 'triggered_at', 'override_reason', 'resolved']);
        });
    }
};
