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
    Schema::create('digitalforms', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->longText('desc')->nullable();
        $table->string('success_message')->nullable();
        $table->string('failure_message')->nullable();
        $table->string('receiver_mail')->nullable();
        $table->integer('order')->nullable();
        $table->string('parent_id')->default('0');
        $table->integer('paginate_status')->default(0);
        $table->integer('last_footer')->default(0);
        $table->longText('mail_desc')->nullable();
        $table->string('header_status', 3)->default('0');
        $table->string('invoice_to', 1000)->nullable();
        $table->string('invoice_from', 1000)->nullable();
        $table->string('sia', 1000)->nullable();
        $table->string('vat', 1000)->nullable();
        $table->string('tax_date', 1000)->nullable();
        $table->string('invoice_number', 1000)->nullable();
        $table->string('terms', 1000)->nullable();
        $table->string('due_date', 1000)->nullable();
        $table->string('invoice_date', 1000)->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digitalforms');
    }
};
