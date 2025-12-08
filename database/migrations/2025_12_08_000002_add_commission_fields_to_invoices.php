<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->default(0)->after('total_amount');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_percent');
            $table->decimal('staff_amount', 10, 2)->nullable()->after('commission_amount');
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
            $table->dropColumn(['commission_percent', 'commission_amount', 'staff_amount']);
        });
    }
};
