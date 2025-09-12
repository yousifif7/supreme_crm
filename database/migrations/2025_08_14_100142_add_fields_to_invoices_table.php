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
            // Optional additional info for payroll integration
            $table->decimal('total_hours', 7, 2)->nullable()->after('total_amount'); // total hours worked for invoice
            $table->decimal('total_sick_pay', 8, 2)->nullable()->after('total_hours'); // SSP included
            $table->decimal('total_holiday_pay', 8, 2)->nullable()->after('total_sick_pay'); // holiday pay
            $table->decimal('total_unpaid_leave', 8, 2)->nullable()->after('total_holiday_pay'); // unpaid leave deduction
            $table->boolean('processed_by_payroll')->default(false)->after('total_unpaid_leave'); // ensure invoice is processed only once
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'total_hours',
                'total_sick_pay',
                'total_holiday_pay',
                'total_unpaid_leave',
                'processed_by_payroll',
            ]);
        });
    }
};
