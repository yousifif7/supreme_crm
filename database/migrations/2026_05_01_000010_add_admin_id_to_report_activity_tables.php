<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sia_check_reports') && !Schema::hasColumn('sia_check_reports', 'admin_id')) {
            Schema::table('sia_check_reports', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('login_activities') && !Schema::hasColumn('login_activities', 'admin_id')) {
            Schema::table('login_activities', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sia_check_reports') && Schema::hasColumn('sia_check_reports', 'admin_id')) {
            Schema::table('sia_check_reports', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            });
        }

        if (Schema::hasTable('login_activities') && Schema::hasColumn('login_activities', 'admin_id')) {
            Schema::table('login_activities', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            });
        }
    }
};
