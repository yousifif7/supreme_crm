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
        Schema::create('vehicle_compliances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('mot_certificate_number');
            $table->date('mot_expiry_date');

            $table->string('insurance_provider');
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();

            $table->string('vehicle_tax_status')->nullable();
            $table->date('tax_expiry_date')->nullable();
            $table->string('tax_class')->nullable();

            $table->string('v5c_logbook_reference_number')->nullable();
            $table->boolean('lez_ulez_compliant')->nullable(); // true = compliant
            $table->string('tachograph_certificate_number')->nullable();
            $table->date('tachograph_calibration_expiry')->nullable();
            $table->timestamps();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_compliances');
    }
};
