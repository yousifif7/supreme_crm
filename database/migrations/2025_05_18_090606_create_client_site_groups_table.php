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
        Schema::create('client_site_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('site_group_name')->nullable();
            $table->decimal('client_charge_rate_guarding', 8, 2)->nullable();
            $table->decimal('client_charge_rate_supervisor', 8, 2)->nullable();
            $table->decimal('site_group_rate_guarding', 8, 2)->nullable();
            $table->decimal('site_group_rate_supervisor', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_site_groups');
    }
};
