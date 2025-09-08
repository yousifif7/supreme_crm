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
            // Payroll / hours tracking
            $table->decimal('hours', 5, 2)->nullable()->after('end_date');
            $table->boolean('paid')->default(false)->after('hours'); // true = paid, false = unpaid
            $table->boolean('processed_by_payroll')->default(false)->after('paid');

            // Sick pay / statutory leave
            $table->integer('ssp_days')->nullable()->after('processed_by_payroll');

            // Holiday tracking
            $table->decimal('holiday_days_used', 5, 2)->nullable()->after('ssp_days');

            // Unpaid leave tracking
            $table->decimal('unpaid_days', 5, 2)->nullable()->after('holiday_days_used');

            // Approval / management info
            $table->unsignedBigInteger('approved_by')->nullable()->after('unpaid_days');
            $table->dateTime('approval_date')->nullable()->after('approved_by');
            $table->text('notes')->nullable()->after('approval_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'hours',
                'paid',
                'processed_by_payroll',
                'ssp_days',
                'holiday_days_used',
                'unpaid_days',
                'approved_by',
                'approval_date',
                'notes',
            ]);
        });
    }
};
