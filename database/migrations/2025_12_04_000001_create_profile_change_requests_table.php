<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('requested_email')->nullable();
            $table->string('old_email')->nullable();
            $table->string('status')->default('pending'); // pending, approved, denied
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            // Some MySQL hosts (or older setups) disallow foreign key creation (engine/collation differences).
            // Use indexes instead of strict foreign key constraints to avoid ERRNO 150 on constrained hosts.
            $table->index('user_id');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');
    }
};
