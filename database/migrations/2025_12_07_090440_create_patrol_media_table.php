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
        Schema::create('patrol_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patrol_id');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('patrol_id')->references('id')->on('patrols')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_media');
    }
};
