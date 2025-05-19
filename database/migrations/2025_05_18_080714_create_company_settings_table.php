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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_postcode')->nullable();
            $table->string('company_contact')->nullable();
            $table->string('company_fax')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_accounttitle')->nullable();
            $table->string('company_accounnumber')->nullable();
            $table->string('company_accountsortcode')->nullable();
            $table->string('company_registration_no')->nullable();
            $table->string('company_vat_no')->nullable();
            $table->string('company_vat_percent')->nullable();
            $table->boolean('company_printbank_details')->default(0);
            $table->string('company_payee_ref')->nullable();
            $table->string('company_start_holiday')->nullable();
            $table->string('company_notify_sia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
