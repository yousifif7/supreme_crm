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
        Schema::create('check_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->references('id')->on('shift_dates')->onDelete('cascade');
            $table->timestamp('scheduled_time');
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending');
            $table->enum('method', ['app', 'phone']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_calls');
    }
};
