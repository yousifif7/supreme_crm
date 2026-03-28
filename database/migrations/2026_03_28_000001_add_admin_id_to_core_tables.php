<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables that need an admin_id column.
     * admin_id references the user ID of the "admin" role user who owns these records.
     * When null, the record belongs to the global (superadmin-managed) dataset.
     */
    private array $tables = [
        'employees',
        'clients',
        'sites',
        'shifts',
        'invoices',
        'vehicles',
        'patrols',
        'incident_reports',
        'leave_requests',
        'documentation_uploads',
        'alert_reminders',
        'pay_rolls',
        'sub_contractors',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'admin_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                    $table->foreign('admin_id')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'admin_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName . '_admin_id_foreign']);
                    $table->dropColumn('admin_id');
                });
            }
        }
    }
};
