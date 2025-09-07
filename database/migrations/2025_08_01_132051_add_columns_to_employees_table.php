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

        Schema::table('employees', function (Blueprint $table) {
            // Tracks accrued holiday hours for each employee
            $table->decimal('holiday_balance', 8, 2)->default(0)->after('fixed_pay');

            // Stores weekly pay for SSP eligibility calculation (optional if calculated dynamically)
            $table->decimal('weekly_pay', 8, 2)->nullable()->after('holiday_balance');

            // Optional: tracks total sick days taken (for reporting / SSP max limit)
            $table->integer('sick_days_taken')->default(0)->after('weekly_pay');

            // Optional: store detailed leave balances for each type as JSON
            $table->json('leave_balance')->nullable()->after('sick_days_taken');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'holiday_balance',
                'weekly_pay',
                'sick_days_taken',
                'leave_balance',
            ]);
        });
    }
};
