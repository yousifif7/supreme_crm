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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
            $table->string('site_name');
            $table->string('guard_names')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('address');
            $table->string('post_code');
            $table->string('site_code')->nullable();
            $table->longText('note')->nullable();
            $table->unsignedBigInteger('manager_1_id')->nullable();
            $table->unsignedBigInteger('manager_2_id')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('break_time')->nullable();
            $table->decimal('guard_rate', 10, 2)->nullable();
            $table->decimal('office_rate', 10, 2)->nullable();
            $table->string('billable_rate')->nullable();
            $table->string('site_billable_rate_supervisor')->nullable();
            $table->string('payable_rate')->nullable();
            $table->string('site_payable_rate_supervisor')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
