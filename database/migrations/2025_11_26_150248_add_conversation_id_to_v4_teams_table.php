<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConversationIdToV4TeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->string('conversation_id')->nullable()->before('academy_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->dropColumn('conversation_id');
        });
    }
}
