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
        Schema::table('shifts', function (Blueprint $table) {
            //
            $table->foreignId('user_id')->nullable()->after('subcontractor_id')->constrained()->onDelete('set null');   
            $table->decimal('base_rate', 8, 2)->default(0);
            $table->decimal('travel_time', 5, 2)->default(0);
            $table->decimal('travel_rate', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('invoiced')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            //
        });
    }
};
