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
        Schema::create('shift_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->date('shift_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('total_hours')->nullable();
            $table->string('break_time')->nullable();
            $table->date('absentee_start')->nullable();
            $table->date('absentee_end')->nullable();
            $table->time('absentee_start_time')->nullable();
            $table->integer('is_assign')->default('0');
            $table->time('absentee_end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_dates');
    }
};
