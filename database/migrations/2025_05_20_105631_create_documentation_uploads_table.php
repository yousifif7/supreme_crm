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
        Schema::create('documentation_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');

            $table->string('mot_certificate_path');
            $table->string('insurance_certificate_path');
            $table->string('v5c_logbook_path');
            $table->string('tax_confirmation_path');
            $table->string('tachograph_certificate_path');
            $table->string('service_report_path');
            $table->string('inspection_report_path');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_uploads');
    }
};
