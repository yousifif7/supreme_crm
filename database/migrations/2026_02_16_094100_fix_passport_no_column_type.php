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
            // Change passport_no from bigInteger to string to support alphanumeric passport numbers
            $table->string('passport_no', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert back to bigInteger if needed (may cause data loss for alphanumeric values)
            $table->bigInteger('passport_no')->nullable()->change();
        });
    }
};
