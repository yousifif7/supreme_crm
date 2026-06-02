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
        Schema::table('shift_dates', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_dates', 'training_id')) {
                $table->unsignedBigInteger('training_id')->nullable()->after('shift_id');
            }
            if (!Schema::hasColumn('shift_dates', 'status')) {
                $table->integer('status')->default(0)->after('is_assign');
            }
            if (!Schema::hasColumn('shift_dates', 'invoiced')) {
                $table->boolean('invoiced')->default(false)->after('status');
            }
            if (!Schema::hasColumn('shift_dates', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('invoiced');
                $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            }
            if (!Schema::hasColumn('shift_dates', 'require_media')) {
                $table->boolean('require_media')->nullable()->after('invoice_id');
            }
            if (!Schema::hasColumn('shift_dates', 'guard_rate')) {
                $table->decimal('guard_rate', 10, 2)->nullable()->after('require_media');
            }
            if (!Schema::hasColumn('shift_dates', 'site_rate')) {
                $table->decimal('site_rate', 10, 2)->nullable()->after('guard_rate');
            }
            if (!Schema::hasColumn('shift_dates', 'subcontractor_id')) {
                $table->unsignedBigInteger('subcontractor_id')->nullable()->after('site_rate');
                $table->foreign('subcontractor_id')->references('id')->on('sub_contractors')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_dates', function (Blueprint $table) {
            if (Schema::hasColumn('shift_dates', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
            }
            if (Schema::hasColumn('shift_dates', 'subcontractor_id')) {
                $table->dropForeign(['subcontractor_id']);
            }
            $table->dropColumn([
                'training_id',
                'status',
                'invoiced',
                'invoice_id',
                'require_media',
                'guard_rate',
                'site_rate',
                'subcontractor_id'
            ]);
        });
    }
};
