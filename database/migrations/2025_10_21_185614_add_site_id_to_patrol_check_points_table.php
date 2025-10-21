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
        Schema::table('patrol_check_points', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('id');

            // If it references another table (e.g., `sites`), add a foreign key
            // Uncomment if applicable:
            // $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrol_check_points', function (Blueprint $table) {
            // Drop foreign key first if added
            // $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
