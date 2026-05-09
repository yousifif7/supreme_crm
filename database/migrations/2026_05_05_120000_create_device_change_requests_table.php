<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('old_device_id')->nullable();
            $table->string('new_device_id');
            $table->string('new_device_name')->nullable();
            $table->string('new_os')->nullable();
            $table->string('new_app_version')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'new_device_id', 'status'], 'device_change_user_device_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_change_requests');
    }
};
