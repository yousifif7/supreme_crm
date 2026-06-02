<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->text('device_id')->change();
        });

        Schema::table('device_change_requests', function (Blueprint $table) {
            $table->text('old_device_id')->nullable()->change();
            $table->text('new_device_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->string('device_id')->change();
        });

        Schema::table('device_change_requests', function (Blueprint $table) {
            $table->string('old_device_id')->nullable()->change();
            $table->string('new_device_id')->change();
        });
    }
};
