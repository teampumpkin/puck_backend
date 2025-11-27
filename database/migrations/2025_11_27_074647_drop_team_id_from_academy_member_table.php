<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTeamIdFromAcademyMemberTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academy_member', function (Blueprint $table) {

            // Drop foreign key first (if exists)
            try {
                $table->dropForeign(['team_id']);
            } catch (Exception $e) {
                // Ignore if fk doesn't exist
            }

            // Drop the column
            if (Schema::hasColumn('academy_member', 'team_id')) {
                $table->dropColumn('team_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_member', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('team_id')->nullable();

            // Add foreign key again
            $table->foreign('team_id')
                ->references('id')
                ->on('v4_teams')
                ->onDelete('cascade');
        });
    }
}
