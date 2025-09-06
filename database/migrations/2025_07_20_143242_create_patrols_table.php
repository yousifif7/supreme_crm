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
        Schema::create('patrols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('summary')->nullable();
            $table->integer('total_checkpoints')->nullable();
            $table->integer('completed_checkpoints')->nullable();
            $table->integer('issues_reported')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('start_time')->nullable(); // scheduled patrol start time
            $table->enum('status', ['pending','in_progress','completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrols');
    }
};
