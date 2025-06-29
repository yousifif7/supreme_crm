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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->year('year_of_manufacture')->nullable();
            $table->string('colour')->nullable();
            $table->string('body_type')->nullable(); // e.g., Van, Car, HGV
            $table->string('fuel_type')->nullable();
            $table->decimal('engine_size', 5, 2)->nullable(); // in liters, e.g., 2.5
            $table->string('vin')->unique(); // Vehicle Identification Number
            $table->integer('odometer_reading')->nullable(); // in kilometers or miles
            $table->date('first_registration_date')->nullable();
            $table->string('vehicle_category')->nullable(); // e.g., Private, Commercial, Fleet
            $table->string('assigned_to')->nullable(); // Driver or Department
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
