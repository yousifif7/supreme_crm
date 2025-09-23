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
        Schema::create('shift_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_date_id');
            $table->enum('note_type', ['guard', 'control', 'both']);
            $table->text('note');
            $table->unsignedBigInteger('user_id')->nullable(); // who wrote it
            $table->timestamps();

            $table->foreign('shift_date_id')->references('id')->on('shift_dates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_notes');
    }
};
