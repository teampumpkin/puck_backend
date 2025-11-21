<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('v4_users', function (Blueprint $table) {

            // Remove unique constraint from email
            $table->dropUnique(['email']);

            // Add deleted_at for soft delete
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('v4_users', function (Blueprint $table) {

            // Restore unique constraint
            $table->unique('email');

            // Remove soft deletes column
            $table->dropSoftDeletes();
        });
    }
};
