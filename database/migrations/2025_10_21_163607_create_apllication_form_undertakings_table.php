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
    Schema::create('apllication_form_undertakings', function (Blueprint $table) {
        $table->id();
        $table->string('application_id', 191);
        $table->string('full_name', 191);
        $table->string('job_title', 191);
        $table->string('date', 191);
        $table->string('date_sign', 191)->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apllication_form_undertakings');
    }
};
