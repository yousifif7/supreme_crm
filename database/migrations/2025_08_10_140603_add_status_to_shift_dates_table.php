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
        Schema::table('shift_dates', function (Blueprint $table) {
            $table->enum('status', [
                'booked_on',
                'booked_off',
                'pending',
                'accepted',
                'declined'
            ])->default('pending')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_dates', function (Blueprint $table) {
            //
        });
    }
};
