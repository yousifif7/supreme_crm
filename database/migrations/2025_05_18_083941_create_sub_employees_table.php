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
        Schema::create('sub_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subcontractor_id')->nullable();
            $table->unsignedBigInteger('visa_id')->nullable();
            $table->unsignedBigInteger('sia_id')->nullable();
            $table->foreign('subcontractor_id')
                ->references('id')
                ->on('sub_contractors')
                ->onDelete('cascade');
            $table->foreign('visa_id')
                ->references('id')
                ->on('visa_types')
                ->onDelete('cascade');
            $table->foreign('sia_id')
                ->references('id')
                ->on('security_industry_associations')
                ->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('sia_number')->nullable();
            $table->date('sia_expiry_date')->nullable();
            $table->date('dob')->nullable();
            $table->string('ni_number')->nullable();
            $table->string('training_name')->nullable();
            $table->string('certification_number')->nullable();
            $table->text('address')->nullable();
            $table->boolean('pmva_officer')->default(0);
            $table->date('visa_expiry')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_employees');
    }
};
