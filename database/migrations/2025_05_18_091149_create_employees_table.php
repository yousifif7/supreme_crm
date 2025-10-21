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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable();
            $table->string('pin')->nullable();
            $table->string('fore_name')->nullable();
            $table->string('sur_name')->nullable();
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->string('ni_number')->nullable();
            $table->string('sia_licence')->nullable();
            $table->date('sia_expiry')->nullable();
            $table->string('portal_login')->nullable();
            $table->string('contact')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('post_code')->nullable();
            $table->string('sort_code')->nullable();
            $table->date('entry_date')->nullable();
            $table->date('dob')->nullable();
            $table->string('employee_type')->nullable();
            $table->string('current_endorsement')->nullable();
            $table->boolean('driving_license')->nullable();
            $table->boolean('vehicle_in_use')->nullable();
            $table->boolean('visa_to_work')->nullable();
            $table->string('address_group')->nullable();
            $table->string('address_group_additional')->nullable();
            $table->string('collar')->nullable();
            $table->string('waist')->nullable();
            $table->string('jacket')->nullable();
            $table->string('shoe')->nullable();
            $table->string('inseam')->nullable();
            $table->string('signature')->nullable();
            $table->string('nationality')->nullable();
            $table->string('service_type')->nullable();
            $table->string('guard_rate')->nullable();
            $table->string('payment_period')->nullable();
            $table->string('fixed_pay')->nullable();
            $table->text('account_name')->nullable();
            $table->text('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->text('other_info')->nullable();
            $table->date('holiday_from')->nullable();
            $table->date('holiday_to')->nullable();
            $table->string('licence_type')->nullable();
            $table->string('place_work')->nullable();
            $table->string('hour_per_week')->nullable();
            $table->bigInteger('passport_no')->nullable(); // changed to bigInteger for large numbers, int(50) invalid in MySQL
            $table->date('passport_expiry')->nullable();
            $table->string('visa_type')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->string('job_title')->nullable();
            $table->string('dbs_confirmed')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_pmva_trained')->default(false);
            $table->date('joining_date')->nullable();
            $table->date('date_of_leaving')->nullable();
            $table->text('leaving_comments')->nullable();
            $table->date('starts_date')->nullable();
            $table->string('profile_picture')->nullable();
            $table->text('other_employement')->nullable();
            $table->text('interest_hobbies')->nullable();
            $table->text('criminal_record')->nullable();
            $table->text('bankruptcy_ccj')->nullable();
            $table->text('shifts_cant_work')->nullable();
            $table->integer('holidays_entitlement')->nullable();
            $table->string('reference_to_emp')->nullable();
            $table->text('holidays_commitment')->nullable();
            $table->boolean('own_transport')->nullable();
            $table->string('transport_type')->nullable();
            $table->boolean('profile')->nullable();
            $table->string('driving_license_type')->nullable();
            $table->text('address')->nullable();
            $table->string('type')->nullable();
            $table->string('additional_type')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('license_number')->nullable();
            $table->text('other_employment')->nullable();
            $table->text('leisure_interests')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('sia_number')->nullable();
            $table->string('additional_sia_number')->nullable();
            $table->unsignedBigInteger('sia_id')->nullable();
            $table->unsignedBigInteger('guardGroup_id')->nullable();
            $table->string('guard_comments')->nullable();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->string('subcontractor')->nullable();
            $table->unsignedBigInteger('kin_id')->nullable();
            $table->string('relation_with_kin')->nullable();
            $table->string('kin_number')->nullable();
            $table->string('kin_address')->nullable();
            $table->string('kin_work_tel')->nullable();
            $table->string('kin_mobile')->nullable();
            $table->string('share_code')->nullable();
            $table->string('biometric_residence_permit')->nullable();
            $table->date('biometric_residence_permit_expiry')->nullable();
            $table->string('brp_status', 50)->nullable();
            $table->string('settlement')->nullable();
            $table->text('tags')->nullable();
            $table->string('pay_rate', 50)->nullable();
            $table->string('employee_password')->nullable();
            $table->boolean('is_permanent')->nullable();
            $table->string('added_from')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('holidays_entitlement_additional')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->text('holiday_from_additional')->nullable();
            $table->text('holiday_to_additional')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
