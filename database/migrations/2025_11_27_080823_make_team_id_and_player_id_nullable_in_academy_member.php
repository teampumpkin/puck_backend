<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTeamIdAndPlayerIdNullableInAcademyMember extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academy_member', function (Blueprint $table) {
            // Drop foreign keys first (if they exist)
            try {
                $table->dropForeign(['team_id']);
            } catch (Exception $e) {
            }
            try {
                $table->dropForeign(['player_id']);
            } catch (Exception $e) {
            }

            // Modify columns to nullable
            $table->unsignedBigInteger('team_id')->nullable()->change();
            $table->unsignedBigInteger('player_id')->nullable()->change();

            // Re-add foreign keys (nullable allowed)
            $table->foreign('team_id')
                ->references('id')
                ->on('v4_teams')
                ->onDelete('cascade');

            $table->foreign('player_id')
                ->references('id')
                ->on('v4_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_member', function (Blueprint $table) {
            // Drop updated foreign keys
            try {
                $table->dropForeign(['team_id']);
            } catch (Exception $e) {
            }
            try {
                $table->dropForeign(['player_id']);
            } catch (Exception $e) {
            }

            // Make columns NOT nullable again
            $table->unsignedBigInteger('team_id')->nullable(false)->change();
            $table->unsignedBigInteger('player_id')->nullable(false)->change();

            // Re-add original constraints
            $table->foreign('team_id')
                ->references('id')
                ->on('v4_teams')
                ->onDelete('cascade');

            $table->foreign('player_id')
                ->references('id')
                ->on('v4_users')
                ->onDelete('cascade');
        });
    }
}
