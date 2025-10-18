<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('type')->default('client'); // client, subcontractor, security_staff
            $table->foreignId('client_id')->nullable()->constrained('users');
            $table->foreignId('subcontractor_id')->nullable()->constrained('users');
            $table->foreignId('security_staff_id')->nullable()->constrained('users');
            $table->foreignId('site_id')->nullable()->constrained();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('status')->default('draft'); // draft, sent, paid, overdue
            $table->text('notes')->nullable();
            $table->text('payment_note')->nullable();
            $table->decimal('total_shift_hours', 10, 2)->nullable();
            $table->decimal('total_deductions_hours', 10, 2)->nullable();
            $table->decimal('gross_amount', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
