<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_bans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('site_id');
            $table->index('client_id');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            // site_id and client_id are optional; foreign keys may be omitted for simplicity
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_bans');
    }
};
