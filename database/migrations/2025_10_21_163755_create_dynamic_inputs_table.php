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
    Schema::create('dynamic_inputs', function (Blueprint $table) {
        $table->id();
        $table->longText('title')->nullable();
        $table->longText('info')->nullable();
        $table->longText('placeholder')->nullable();
        $table->string('child_id', 255)->default('0');
        $table->string('parent_id', 255)->default('0');
        $table->string('type', 255)->default('0');
        $table->boolean('min_limit_check')->default(0);
        $table->integer('min_limit_input')->nullable();
        $table->boolean('max_limit_check')->default(0);
        $table->integer('max_limit_input')->nullable();
        $table->boolean('send_email')->default(0);
        $table->boolean('required')->default(0);
        $table->boolean('unique')->default(0);
        $table->longText('options')->nullable(); // JSON-compatible
        $table->integer('order')->default(0);
        $table->integer('label_status')->default(0);
        $table->longText('desc')->nullable();
        $table->integer('others')->default(0);
        $table->string('header_status', 1000)->default('0');
        $table->string('set_design', 1000)->nullable();
        $table->string('value', 1000)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_inputs');
    }
};
