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
        Schema::table('leave_requests', function (Blueprint $table) {
            // Link directly to employee for payroll calculations
            $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');

            // Track whether this leave was automatically split (paid/unpaid)
            $table->boolean('auto_split')->default(false);

            // Track actual SSP days paid for sick leave
            $table->integer('ssp_paid_days')->nullable();

            // Track unpaid days (waiting days or excess leave)
            $table->integer('unpaid_days')->nullable();

            // Optional: store processed payroll amount for this leave
            $table->decimal('amount_paid', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'auto_split',
                'ssp_paid_days',
                'unpaid_days',
                'amount_paid',
            ]);
        });
    }
};
