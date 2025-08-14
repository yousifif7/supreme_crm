<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained();
            $table->foreignId('shift_date_id')->nullable()->constrained('shift_dates');
            $table->foreignId('security_staff_id')->nullable()->constrained('users');
            $table->foreignId('site_id')->nullable()->constrained();
            $table->date('date');
            $table->string('description');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 8, 2);
            $table->decimal('break_hours', 8, 2)->default(0);
            $table->decimal('book_on_hours', 8, 2)->default(0);
            $table->decimal('book_off_hours', 8, 2)->default(0);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
