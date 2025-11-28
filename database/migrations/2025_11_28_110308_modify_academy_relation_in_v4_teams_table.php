<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAcademyRelationInV4TeamsTable extends Migration
{
    public function up()
    {
        // Drop old academy_id (FK to v4_users)
        Schema::table('v4_teams', function (Blueprint $table) {
            // Drop FK only if column exists
            if (Schema::hasColumn('v4_teams', 'academy_id')) {
                $table->dropForeign(['academy_id']);
                $table->dropColumn('academy_id');
            }
        });

        // Add new academy_id (FK to v4_academies)
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')
                ->nullable()
                ->after('id');

            $table->foreign('academy_id')
                ->references('id')
                ->on('v4_academies')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        // Reverse changes

        // Drop new FK to v4_academies
        Schema::table('v4_teams', function (Blueprint $table) {
            if (Schema::hasColumn('v4_teams', 'academy_id')) {
                $table->dropForeign(['academy_id']);
                $table->dropColumn('academy_id');
            }
        });

        // Restore old FK to v4_users
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')
                ->nullable()
                ->after('id');

            $table->foreign('academy_id')
                ->references('id')
                ->on('v4_users')
                ->cascadeOnDelete();
        });
    }
}
