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
        Schema::create('check_call_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('check_call_id');
            $table->string('file_path'); // or however you're storing media
            $table->timestamps();

            $table->foreign('check_call_id')->references('id')->on('check_calls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_call_media');
    }
};
