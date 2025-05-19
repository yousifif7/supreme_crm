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
        Schema::create('pay_rolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guardGroup_id')->nullable();
            $table->string('payroll_no')->nullable();
            $table->date('payroll_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_hours')->nullable();
            $table->decimal('total_penalties', 8, 2)->default(0.00);
            $table->decimal('total_expenses', 8, 2)->default(0.00);
            $table->decimal('total_payroll_amount', 8, 2)->nullable();
            $table->timestamps();
            $table->foreign('guardGroup_id')
                ->references('id')
                ->on('guard_groups')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_rolls');
    }
};
