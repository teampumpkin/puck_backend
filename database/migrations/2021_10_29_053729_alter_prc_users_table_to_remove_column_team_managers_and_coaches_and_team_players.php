<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcUsersTableToRemoveColumnTeamManagersAndCoachesAndTeamPlayers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->dropColumn('team_managers');
            $table->dropColumn('coaches');
            $table->dropColumn('team_players');
            //$table->dropColumn('team_id');

//            $table->integer('team_id')->nullable();
            $table->integer('academy_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->text('team_managers')->nullable();
            $table->text('coaches')->nullable();
            $table->text('team_players')->nullable();

            $table->dropColumn('team_id');
            $table->dropColumn('academy_id');
        });
    }
}
