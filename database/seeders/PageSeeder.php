<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query="INSERT INTO `pages` (`id`, `title`, `slug`, `desc`, `longdesc`, `select_form_id`, `created_at`, `updated_at`) VALUES
(3, 'Supreme Protection DOB Report', 'supreme-protection-dob-report', NULL, NULL, '14', '2025-03-25 06:43:09', '2025-03-25 06:43:09'),
(5, 'Supreme Protection Incident Report Form', 'supreme-protection-incident-report-form', '<p><span style=\"color: rgb(32, 33, 36); font-family: docs-Roboto; font-size: 14.6667px; white-space-collapse: preserve;\">This form is to be completed by all Supreme  staff who are involved in any incidents. The below aid-memoire must be used to complete the form.</span></p>', NULL, '16', '2025-03-27 22:35:39', '2025-03-28 09:54:42'),
(6, 'Employee Feedback Form', 'employee-feedback-form', NULL, NULL, '17', '2025-03-27 23:01:40', '2025-03-27 23:01:40'),
(7, 'Site Visit Quality Control and Welfare Check Form', 'site-visit-quality-control-and-welfare-check-form', NULL, NULL, '18', '2025-03-29 19:50:37', '2025-03-29 19:50:37'),
(8, 'Client Feedback Form', 'client-feedback-form', NULL, NULL, '19', '2025-04-03 16:44:05', '2025-04-03 16:44:05'),
(9, 'Consumer Feedback', 'consumer-feedback', NULL, NULL, '20', '2025-04-03 17:05:08', '2025-04-03 17:05:08'),
(10, 'Holiday Request Form', 'holiday-request-form', NULL, NULL, '21', '2025-04-12 16:50:30', '2025-04-12 16:50:30'),
(11, 'Monthly Site Health and Safety Inspection Checklist', 'monthly-site-health-and-safety-inspection-checklist', NULL, NULL, '22', '2025-04-12 21:09:36', '2025-04-12 21:09:36'),
(12, 'Site Induction', 'site-induction', NULL, NULL, '23', '2025-04-12 21:27:53', '2025-04-12 21:27:53'),
(13, 'Uniform Issue Record Form', 'uniform-issue-record-form', NULL, NULL, '24', '2025-04-12 22:14:12', '2025-04-13 22:53:18'),
(14, 'Housekeeping Criteria', 'housekeeping-criteria', NULL, NULL, '22', '2025-04-13 06:16:28', '2025-04-13 06:16:28'),
(15, 'Complaint Registration Form', 'complaint-registration-form', NULL, NULL, '25', '2025-04-13 17:59:14', '2025-04-13 18:17:45'),
(16, 'Suggestion Form', 'sugesstion-form', NULL, NULL, '26', '2025-04-15 01:41:22', '2025-04-15 02:19:07'),
(17, 'Invoice Form', 'invoice-form', NULL, NULL, '27', '2025-05-30 12:55:42', '2025-05-31 08:50:16'),
(18, 'Evaluation Test', 'evaluation-test', NULL, NULL, '28', '2025-07-23 08:08:56', '2025-07-23 08:08:56'),
(19, 'Accident, Incident, First Aid and Safeguarding, Reporting Form', 'accident-incident-first-aid-and-safeguarding-reporting-form', '<p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\">The above also includes any occasions where a near miss is noted, so that measures can be put in place in future to mitigate the risk of an actual occurrence happening.</p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><strong>NB</strong>&nbsp;All reports should be completed within the first&nbsp;<span style=\"text-decoration-line: underline;\">24 hours</span>&nbsp;following the accident/incident taking place.</p>', '<p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><span style=\"font-size: 14pt;\"><strong>Definitions</strong></span></p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px; \"><strong>Accident:</strong>&nbsp;An unexpected event which results in serious injury or illness of an employee and may also result in property damage.</p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><strong>Incident (incl. Security incidents):</strong>&nbsp;An instance of something happening, an unexpected event or occurrence that doesn’t result in serious injury or illness but may result in property damage.</p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><strong>Near Miss:</strong>&nbsp;An event not causing harm, but has the potential to cause injury or ill health.</p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><strong>InfoSec&nbsp;Incident:&nbsp;</strong>A single or a series of unwanted or unexpected information security events that have a significant probability of compromising business operations and threatening information security.&nbsp;<span style=\"color: rgb(234, 50, 35);\">DO NOT SELECT THIS FOR EVENT SECURITY INCIDENTS.&nbsp;</span></p><p style=\"margin: 1em 0px; color: rgb(30, 47, 98); font-family: Inter, sans-serif; font-size: 15px;\"><strong>General:</strong>&nbsp;Feedback</p>', '29', '2025-08-19 16:21:13', '2025-08-19 17:40:53')";

        DB::insert($query);
    }
}
