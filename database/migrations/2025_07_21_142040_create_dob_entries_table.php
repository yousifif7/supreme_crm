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
        Schema::create('dob_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->enum('entry_type', ['incident', 'observation', 'maintenance', 'visitor', 'other']);
            $table->string('title');
            $table->text('description');
            $table->json('location');
            $table->timestamp('timestamp');
            $table->string('admin_comments')->nullable();
            $table->boolean('edit_requested')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dob_entries');
    }
};
