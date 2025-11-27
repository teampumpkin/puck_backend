<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueKeysFromMultipleTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favourite_users', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['user_id', 'favourite_id']);
        });

        Schema::table('team_members', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['team_id', 'player_id']);
        });

        Schema::table('academy_member', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['academy_id', 'team_id']);
        });

        Schema::table('v4_academy_admins', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['academy_id', 'admin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favourite_users', function (Blueprint $table) {
            // Restore the composite unique constraint
            $table->unique(['user_id', 'favourite_id']);
        });

        Schema::table('team_members', function (Blueprint $table) {
            // Restore the composite unique constraint
            $table->unique(['team_id', 'player_id']);
        });

        Schema::table('academy_member', function (Blueprint $table) {
            // Restore the composite unique constraint
            $table->unique(['academy_id', 'team_id']);
        });

        Schema::table('v4_academy_admins', function (Blueprint $table) {
            // Restore the composite unique constraint
            $table->unique(['academy_id', 'admin_id']);
        });
    }
}
