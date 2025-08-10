<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // e.g., App\Models\Employee
            $table->string('restriction_type'); // e.g., expiry_check, required_field_check
            $table->string('field_name'); // e.g., 'sia_expiry', 'profile_photo'
            $table->string('error_message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restrictions');
    }
};
