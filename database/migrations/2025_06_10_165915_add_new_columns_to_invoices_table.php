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
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->decimal('total_duration_hours', 8, 2)->nullable();
            $table->decimal('total_break_hours', 8, 2)->nullable();
            $table->decimal('rate_per_hour', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('employee_id');
            $table->dropColumn('date_from');
            $table->dropColumn('date_to');
            $table->dropColumn('total_duration_hours');
            $table->dropColumn('total_break_hours');
            $table->dropColumn('rate_per_hour');
        });
    }
};
