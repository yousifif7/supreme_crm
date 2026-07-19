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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shift_dates')->cascadeOnDelete();
            $table->enum('category', ['theft', 'assault', 'fire', 'medical', 'property_damage', 'suspicious_activity', 'other']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('description');
            $table->json('location');
            $table->boolean('police_notified')->default(false);
            $table->string('police_reference')->nullable();
            $table->text('immediate_action_taken')->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
