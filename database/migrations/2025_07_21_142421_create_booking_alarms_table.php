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
        Schema::create('booking_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shift_dates')->onDelete('cascade');
            $table->enum('type', ['book_on', 'book_off']);
            $table->timestamp('scheduled_time')->nullable();
            $table->timestamp('alarm_time')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_alarms');
    }
};
