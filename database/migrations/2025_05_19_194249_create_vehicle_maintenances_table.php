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
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');

            // Service Schedule
            $table->date('last_service_date');
            $table->date('next_service_due_date');

            // Maintenance Logs
            $table->string('work_type');
            $table->date('maintenance_date');
            $table->string('garage_provider');

            // Defect Reports
            $table->string('reported_by');
            $table->date('date_reported');
            $table->string('resolution_status');

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
        Schema::dropIfExists('vehicle_maintenances');
    }
};
