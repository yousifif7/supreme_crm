<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sia_check_reports', function (Blueprint $table) {
            $table->id();
            $table->string('run_id')->index();           // UUID grouping all entries per run
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('employee_name')->nullable();
            $table->string('sia_licence')->nullable();
            $table->string('status_before')->nullable();  // Active / Inactive / null (unknown)
            $table->string('status_after')->nullable();   // Active / Inactive
            $table->boolean('changed')->default(false);
            $table->text('error')->nullable();            // any error message
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sia_check_reports');
    }
};
