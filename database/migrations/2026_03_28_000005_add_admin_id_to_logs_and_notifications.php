<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logs') && !Schema::hasColumn('logs', 'admin_id')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'admin_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logs') && Schema::hasColumn('logs', 'admin_id')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            });
        }

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'admin_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            });
        }
    }
};
