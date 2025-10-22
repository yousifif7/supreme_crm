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
            $table->string('driving_licence_expiry',50)->nullable()->after('driving_licence_number');

             $table->date('employment_start_date')->nullable();
             $table->date('employment_end_date')->nullable();

        });
         Schema::table('sites', function (Blueprint $table) {
            $table->string('has_qr',50)->nullable()->after('id');
          });

          Schema::table('check_calls', function (Blueprint $table) {
            $table->string('name',50)->nullable()->after('id');
          });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('driving_licence_expiry');
             $table->dropColumn('employment_start_date');
             $table->dropColumn('employment_end_date');
             
        });

         Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('has_qr');
        });
         Schema::table('check_calls', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
