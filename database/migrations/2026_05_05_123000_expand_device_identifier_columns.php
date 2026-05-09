<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->string('device_id', 512)->change();
        });

        Schema::table('device_change_requests', function (Blueprint $table) {
            $table->string('old_device_id', 512)->nullable()->change();
            $table->string('new_device_id', 512)->change();
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
