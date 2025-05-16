<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcScoutRequestsToAddNewColumnLeagueId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_scout_requests', function (Blueprint $table) {
            $table->integer('league_id')->nullable();
            $table->string('one_time_subscription_id', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_scout_requests', function (Blueprint $table) {
            $table->dropColumn('league_id');
            $table->dropColumn('one_time_subscription_id');
        });
    }
}
