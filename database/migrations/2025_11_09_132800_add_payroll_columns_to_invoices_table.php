<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add payroll-related columns if they don't exist already
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'ssp_amount')) {
                $table->decimal('ssp_amount', 10, 2)->default(0)->after('net_amount');
            }
            if (! Schema::hasColumn('invoices', 'ssp_days')) {
                $table->integer('ssp_days')->default(0)->after('ssp_amount');
            }
            if (! Schema::hasColumn('invoices', 'holiday_amount')) {
                $table->decimal('holiday_amount', 10, 2)->default(0)->after('ssp_days');
            }
            if (! Schema::hasColumn('invoices', 'holiday_hours')) {
                $table->decimal('holiday_hours', 8, 2)->default(0)->after('holiday_amount');
            }
            if (! Schema::hasColumn('invoices', 'unpaid_leave_amount')) {
                $table->decimal('unpaid_leave_amount', 10, 2)->default(0)->after('holiday_hours');
            }
            if (! Schema::hasColumn('invoices', 'unpaid_leave_hours')) {
                $table->decimal('unpaid_leave_hours', 8, 2)->default(0)->after('unpaid_leave_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'unpaid_leave_hours')) {
                $table->dropColumn('unpaid_leave_hours');
            }
            if (Schema::hasColumn('invoices', 'unpaid_leave_amount')) {
                $table->dropColumn('unpaid_leave_amount');
            }
            if (Schema::hasColumn('invoices', 'holiday_hours')) {
                $table->dropColumn('holiday_hours');
            }
            if (Schema::hasColumn('invoices', 'holiday_amount')) {
                $table->dropColumn('holiday_amount');
            }
            if (Schema::hasColumn('invoices', 'ssp_days')) {
                $table->dropColumn('ssp_days');
            }
            if (Schema::hasColumn('invoices', 'ssp_amount')) {
                $table->dropColumn('ssp_amount');
            }
        });
    }
};
