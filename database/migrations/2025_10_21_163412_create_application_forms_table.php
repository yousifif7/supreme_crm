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
    Schema::create('application_forms', function (Blueprint $table) {
        $table->id();
        $table->string('position_applied', 191);
        $table->string('attached_doc', 191);
        $table->string('passport_brp_others', 191)->nullable();
        $table->string('min_letter_proof_other', 191)->nullable();
        $table->string('profile_image', 191);
        $table->string('name', 191);
        $table->string('surname', 191);
        $table->string('forename', 191);
        $table->string('surname_of_birth', 191);
        $table->string('date_of_birth', 191);
        $table->longText('current_address');
        $table->longText('post_code');
        $table->longText('from');
        $table->longText('to');
        $table->longText('previous_address');
        $table->longText('post_code_prev');
        $table->longText('from_prev');
        $table->longText('to_prev');
        $table->longText('from_last');
        $table->longText('to_last');
        $table->longText('email');
        $table->longText('mobile');
        $table->longText('telephone');
        $table->longText('nationality');
        $table->longText('date_and_place_enter_in_uk');
        $table->longText('visa_type');
        $table->longText('national_insurance_no');
        $table->longText('passport_number');
        $table->longText('sia_license_sect');
        $table->longText('sia_license_no');
        $table->longText('sia_license_expiry');
        $table->string('type_driving_license', 191);
        $table->string('own_passport', 191);
        $table->string('driving_license_no', 191);
        $table->string('dvla_license_check_code', 191);
        $table->string('disquilifed', 191);
        $table->string('motoring_', 191);
        $table->string('offence', 191);
        $table->string('equal_opportunities', 191);
        $table->string('purpose_job_accept', 191);
        $table->string('application_name', 191);
        $table->string('ni_number', 191);
        $table->string('applicant_signature', 191);
        $table->string('appli_date', 191);
        $table->string('employee_name', 191);
        $table->string('job_title', 191);
        $table->string('employee_id', 191);
        $table->string('photogrphs', 191)->nullable();
        $table->string('i_name', 191);
        $table->string('employee_signature', 191);
        $table->string('employee_date', 191);
        $table->string('agreement_company', 191);
        $table->string('agreement_employee', 191);
        $table->string('staff_name', 191);
        $table->string('staff_sign', 191);
        $table->string('staff_date', 191);
        $table->string('company_represent_name', 191);
        $table->string('company_represent_sign', 191);
        $table->string('stacompany_represent_date', 191);
        $table->string('ethnic', 191);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_forms');
    }
};
