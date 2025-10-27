<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Incident extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query="INSERT INTO `incidents` (`id`, `first_name`, `last_name`, `job_title`, `email_address`, `phone_number`, `reportingType`, `project_location`, `location`, `date_occurrence`, `time_occurrence`, `date_occurrence2`, `general_feedback`, `wasAnyone_injuired`, `damage_property`, `damage_caused`, `damage_cause2`, `damage_report_to`, `damage_report_other`, `damage_report_input`, `name_of_informed`, `name_of_formed2`, `hazardous_involved`, `hazard_substance`, `hazard_actions`, `substance_involved`, `substance_actions`, `date_occurrence3`, `time_occurrence3`, `incident_type_select`, `incident_persona_data`, `parties_informed`, `event_involved`, `event_details`, `event_actions`, `event_outcome`, `event_witnesses`, `event_remedial`, `event_riddor`, `damage_caused2`, `damage_cause3`, `casuality_name`, `casuality_last_name`, `is_casuality`, `date_of_birth`, `street_address`, `address_line_2`, `city`, `state_province`, `zip`, `email_address_casuality`, `phone_number_casuality`, `didThe_inquiry_medical`, `actions_taken`, `hospitalisation`, `hosp_details`, `injury_cause_4`, `injury_cause5`, `actions_taken_2`, `hospitalisation_2`, `hosp_details_2`, `injury_cause_6`, `notification_number`, `site_manager_informed`, `employer_informed`, `accident_riddor`, `event_photos`, `created_at`, `updated_at`) VALUES
(1, 'Kadeem', 'Bailey', 'Ut ipsa nulla aliqu', 'dote@mailinator.com', '+1 (714) 536-8173', 'An Accident', 'Canada Day', 'qwerty', '1977-12-25', '05:58', NULL, NULL, 'No', 'Yes', NULL, NULL, 'No', NULL, NULL, NULL, NULL, 'Yes', 'What substance was involved?', 'What actions were taken to contain the substance?', 'What substance was involved?', 'What actions were taken to contain the substance?', '1997-09-23', '03:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-24 15:48:45', '2025-08-24 15:48:45')";

        DB::insert($query);
    }
}
