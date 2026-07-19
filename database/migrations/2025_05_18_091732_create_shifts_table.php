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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->time('start_shift')->nullable();
            $table->time('end_shift')->nullable();
            $table->string('break-mins_shift')->nullable(); // Note: field name has a hyphen
            $table->string('number_shift')->nullable();
            $table->string('site_rate')->nullable();
            $table->string('service_type_1')->nullable();
            $table->string('service_type_2')->nullable();
            $table->date('from_shift')->nullable();
            $table->date('to_shift')->nullable();
            $table->longText('days')->collation('utf8mb4_bin')->nullable();

            $table->unsignedBigInteger('subcontractor_id')->nullable();
            $table->bigInteger('manager_1_id')->nullable();
            $table->bigInteger('manager_2_id')->nullable();
            $table->boolean('restrict_start_time')->nullable();
            $table->boolean('enforce_picture_check')->nullable();
            $table->boolean('restrict_location_check')->nullable();
            $table->decimal('po_rate', 8, 2)->nullable();
            $table->unsignedBigInteger('subemployee_id')->nullable();
            $table->string('payable')->nullable();
            $table->string('employee_rate')->nullable();
            $table->time('start')->nullable();
            $table->time('end')->nullable();
            $table->decimal('site_payable_rate', 8, 2)->nullable();
            $table->decimal('shift_expenses', 8, 2)->nullable();
            $table->string('billable')->nullable();
            $table->decimal('site_billable_rate', 8, 2)->nullable();
            $table->decimal('extra_company_cost', 8, 2)->nullable();
            $table->string('shift_penalty')->nullable();

            $table->boolean('unpaid_shift')->default(0);
            $table->boolean('training_shift')->default(0);
            $table->boolean('confirm_shift')->default(0);
            $table->boolean('unconfirm_shift')->default(0);

            $table->time('book_in_time')->nullable();
            $table->time('book_off_time')->nullable();
            $table->string('po_number')->nullable();
            $table->text('comments')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_assign')->default(0);
            $table->string('lost_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
