<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The application has always referenced `proof_of_address_file` as the column that
     * stores the first proof-of-address upload, but that column was never actually created
     * (the original migration only added `proof_of_address`). As a result CRM proof-of-address
     * uploads failed with "Unknown column 'proof_of_address_file'".
     *
     * This migration creates `proof_of_address_file` (if missing) so the first upload works,
     * and adds `proof_of_address_file_2` to support a second proof-of-address upload.
     */
    public function up(): void
    {
        // NOTE: `employees` is close to MySQL's 65535-byte inline row-size limit due to its
        // large number of varchar columns, so these are added as TEXT (stored off-page) to
        // avoid "Row size too large" errors. This matches how other document columns
        // (first_aid_certificate, act_certificate) were added.
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'proof_of_address_file')) {
                $table->text('proof_of_address_file')->nullable();
            }
            if (!Schema::hasColumn('employees', 'proof_of_address_file_2')) {
                $table->text('proof_of_address_file_2')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'proof_of_address_file_2')) {
                $table->dropColumn('proof_of_address_file_2');
            }
            // Leave `proof_of_address_file` in place: it is relied upon by existing code
            // and may already have held data before this migration ran.
        });
    }
};
