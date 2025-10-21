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
    Schema::create('education', function (Blueprint $table) {
        $table->id();
        $table->string('application_id', 191);
        $table->string('types_of_institute', 191);
        $table->string('name_of_institute', 191);
        $table->string('address_institute', 191);
        $table->string('from', 191);
        $table->string('to', 191);
        $table->string('grade', 191);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('education');
    }
};
