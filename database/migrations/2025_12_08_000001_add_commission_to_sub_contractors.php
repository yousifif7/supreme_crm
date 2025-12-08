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
        if (! Schema::hasColumn('sub_contractors', 'commission')) {
            Schema::table('sub_contractors', function (Blueprint $table) {
                $table->decimal('commission', 5, 2)->default(0)->after('pay_rate')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('sub_contractors', 'commission')) {
            Schema::table('sub_contractors', function (Blueprint $table) {
                $table->dropColumn('commission');
            });
        }
    }
};
