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
        Schema::create('sick_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('reported_sick_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('unpaid_days')->default(0);
            $table->integer('paid_days')->default(0);
            $table->decimal('ssp_amount', 8, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sick_leaves');
    }
};
