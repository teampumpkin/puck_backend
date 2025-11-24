<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToTeamProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('team_profiles', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->nullable()
                ->after('id')
                ->constrained('v4_teams')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('team_profiles', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
}
