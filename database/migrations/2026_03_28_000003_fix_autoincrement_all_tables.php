<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'admin_settings',
        'alarms',
        'alert_reminders',
        'alerts',
        'apllication_form_undertakings',
        'application_forms',
        'bank_details',
        'booking_alarms',
        'booking_media',
        'branches',
        'check_call_media',
        'check_calls',
        'checkpoint_scan_media',
        'checkpoint_scans',
        'cities',
        'client_contacts',
        'client_employee_guards',
        'client_site_groups',
        'clients',
        'company',
        'company_settings',
        'conversation_user',
        'conversations',
        'countries',
        'departments',
        'device_logs',
        'device_tokens',
        'digital_form_submits',
        'digitalforms',
        'dob_entries',
        'dob_media',
        'documentation_uploads',
        'documents',
        'driving_license_types',
        'dynamic_inputs',
        'education',
        'emergency_alerts',
        'emergency_contacts',
        'employee_bans',
        'employee_leaves',
        'employee_terms',
        'employee_type_site',
        'employee_types',
        'employees',
        'guard_availabilities',
        'guard_groups',
        'holidays',
        'incident_media',
        'incident_people',
        'incident_reports',
        'incidents',
        'invoice_items',
        'invoice_reviews',
        'invoices',
        'leave_requests',
        'licenses',
        'locations',
        'logs',
        'message_reads',
        'messages',
        'migrations',
        'nationalities',
        'notifications',
        'pages',
        'patrol_check_points',
        'patrol_media',
        'patrols',
        'pay_rolls',
        'pending_deletes',
        'permissions',
        'personal_access_tokens',
        'previous_employments',
        'profile_change_requests',
        'profiles',
        'restriction_overrides',
        'restrictions',
        'reviews',
        'roadworthiness_checks',
        'roles',
        'security_industry_associations',
        'shift_bookings',
        'shift_checkpoints',
        'shift_dates',
        'shift_notes',
        'shift_trainings',
        'shifts',
        'sia_check_reports',
        'sick_leaves',
        'site_trained_gaurds',
        'site_trained_sub_contractor_gaurds',
        'sites',
        'sites_check_calls',
        'sub_contractor_contacts',
        'sub_contractors',
        'sub_employees',
        'training_acknowledgements',
        'training_materials',
        'user_pinned_conversations',
        'users',
        'vehicle_compliances',
        'vehicle_maintenances',
        'vehicles',
        'visa_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            try {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
            } catch (\Exception $e) {
                logger()->warning("fix_autoincrement: skipped `{$table}`.id — " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Removing AUTO_INCREMENT is destructive and not safe to reverse automatically.
    }
};
