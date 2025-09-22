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
        Schema::create('restriction_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // who overrode
            $table->unsignedBigInteger('entity_id'); // which employee/shift
            $table->string('restriction_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restriction_overrides');
    }
};
