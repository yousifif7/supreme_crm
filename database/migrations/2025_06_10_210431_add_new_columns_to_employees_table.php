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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('sia_licence')->nullable();
            $table->string('passport')->nullable();
            $table->string('proof_of_address')->nullable();
            $table->string('ni_letter')->nullable();
            $table->string('first_aid_certificate')->nullable();
            $table->string('act_certificate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('sia_licence');
            $table->dropColumn('passport');
            $table->dropColumn('proof_of_address');
            $table->dropColumn('ni_letter');
            $table->dropColumn('first_aid_certificate');
            $table->dropColumn('act_certificate');
        });
    }
};
