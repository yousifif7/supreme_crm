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
        Schema::create('training_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_material_id')->constrained('training_materials')->onDelete('cascade');
            $table->timestamp('acknowledged_at');
            $table->integer('completion_time_seconds')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'training_material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_acknowledgements');
    }
};
