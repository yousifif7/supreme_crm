<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('id');
            $table->unsignedBigInteger('site_id')->nullable()->after('client_id');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['site_id']);
            $table->dropColumn(['client_id', 'site_id']);
        });
    }
};
