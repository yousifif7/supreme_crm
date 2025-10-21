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
    Schema::create('previous_employments', function (Blueprint $table) {
        $table->id();
        $table->string('application_id', 191);
        $table->string('name', 191);
        $table->string('position', 191);
        $table->string('from', 191);
        $table->string('to', 191);
        $table->longText('reason_leaving');
        $table->string('address_postcode', 191);
        $table->string('manager', 191);
        $table->string('tel_no', 191);
        $table->string('Salary', 191);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_employments');
    }
};
