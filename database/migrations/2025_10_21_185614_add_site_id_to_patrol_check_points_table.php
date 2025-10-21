<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add column to patrol_check_points
        Schema::table('patrol_check_points', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('id');

            // Optional foreign key
            // $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });

        // Add column to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->string('sia_licence_file')->nullable()->after('sia_licence');
        });
    }

    public function down(): void
    {
        // Revert patrol_check_points change
        Schema::table('patrol_check_points', function (Blueprint $table) {
            // $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        // Revert employees change
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('sia_licence_file');
        });
    }
};
