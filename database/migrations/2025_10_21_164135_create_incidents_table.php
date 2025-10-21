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
    Schema::create('incidents', function (Blueprint $table) {
        $table->id();
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('job_title')->nullable();
        $table->string('email_address')->nullable();
        $table->string('phone_number')->nullable();
        $table->string('reportingType')->nullable();
        $table->string('project_location')->nullable();
        $table->string('location')->nullable();
        $table->string('date_occurrence')->nullable();
        $table->string('time_occurrence')->nullable();
        $table->string('date_occurrence2')->nullable();
        $table->text('general_feedback')->nullable();
        $table->string('wasAnyone_injuired')->nullable();
        $table->string('damage_property')->nullable();
        $table->string('damage_caused')->nullable();
        $table->string('damage_cause2')->nullable();
        $table->string('damage_report_to')->nullable();
        $table->string('damage_report_other')->nullable();
        $table->text('damage_report_input')->nullable();
        $table->string('name_of_informed')->nullable();
        $table->string('name_of_formed2')->nullable();
        $table->string('hazardous_involved')->nullable();
        $table->text('hazard_substance')->nullable();
        $table->text('hazard_actions')->nullable();
        $table->text('substance_involved')->nullable();
        $table->text('substance_actions')->nullable();
        $table->string('date_occurrence3')->nullable();
        $table->string('time_occurrence3')->nullable();
        $table->string('incident_type_select')->nullable();
        $table->string('incident_persona_data')->nullable();
        $table->text('parties_informed')->nullable();
        $table->string('event_involved')->nullable();
        $table->text('event_details')->nullable();
        $table->text('event_actions')->nullable();
        $table->text('event_outcome')->nullable();
        $table->text('event_witnesses')->nullable();
        $table->text('event_remedial')->nullable();
        $table->string('event_riddor')->nullable();
        $table->string('damage_caused2')->nullable();
        $table->string('damage_cause3')->nullable();
        $table->string('casuality_name')->nullable();
        $table->string('casuality_last_name')->nullable();
        $table->string('is_casuality')->nullable();
        $table->string('date_of_birth')->nullable();
        $table->string('street_address')->nullable();
        $table->string('address_line_2')->nullable();
        $table->string('city')->nullable();
        $table->string('state_province')->nullable();
        $table->string('zip')->nullable();
        $table->string('email_address_casuality')->nullable();
        $table->string('phone_number_casuality')->nullable();
        $table->string('didThe_inquiry_medical')->nullable();
        $table->text('actions_taken')->nullable();
        $table->string('hospitalisation')->nullable();
        $table->text('hosp_details')->nullable();
        $table->string('injury_cause_4')->nullable();
        $table->string('injury_cause5')->nullable();
        $table->text('actions_taken_2')->nullable();
        $table->string('hospitalisation_2')->nullable();
        $table->text('hosp_details_2')->nullable();
        $table->string('injury_cause_6')->nullable();
        $table->string('notification_number')->nullable();
        $table->string('site_manager_informed')->nullable();
        $table->string('employer_informed')->nullable();
        $table->string('accident_riddor')->nullable();
        $table->string('event_photos')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
