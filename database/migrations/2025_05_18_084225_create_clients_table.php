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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('company_id')
                ->references('id')
                ->on('company')
                ->onDelete('cascade');
            $table->string('client_name');
            $table->string('contact_number')->nullable();
            $table->string('contact_person');
            $table->string('email');
            $table->text('address')->nullable();
            $table->string('invoice_terms')->nullable();
            $table->text('payment_terms')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->decimal('guard_rate', 8, 2)->nullable();
            $table->boolean('vat_registered')->nullable();
            $table->decimal('office_rate', 10, 2)->nullable();
            $table->decimal('charge_supervisor_rate', 8, 2)->nullable();
            $table->string('vat')->nullable();
            $table->string('doc_1')->nullable();
            $table->string('doc_2')->nullable();
            $table->string('doc_3')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
