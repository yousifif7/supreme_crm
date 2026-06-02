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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
                $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('conversations', 'icon_path')) {
                $table->string('icon_path')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::hasColumn('conversations', 'icon_path')) {
                $table->dropColumn('icon_path');
            }
        });
    }
};
