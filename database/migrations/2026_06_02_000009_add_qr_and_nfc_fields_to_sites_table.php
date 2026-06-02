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
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'plus_code')) {
                $table->string('plus_code')->nullable()->after('post_code');
            }
            if (!Schema::hasColumn('sites', 'has_qr')) {
                $table->boolean('has_qr')->default(false)->after('payable_rate');
            }
            if (!Schema::hasColumn('sites', 'nfc_tag')) {
                $table->string('nfc_tag')->nullable()->after('has_qr');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'plus_code')) {
                $table->dropColumn('plus_code');
            }
            if (Schema::hasColumn('sites', 'has_qr')) {
                $table->dropColumn('has_qr');
            }
            if (Schema::hasColumn('sites', 'nfc_tag')) {
                $table->dropColumn('nfc_tag');
            }
        });
    }
};
