<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcScoutRequestsToAddNewColumnMediaId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_scout_requests', function (Blueprint $table) {
            $table->integer('media_id')->nullable();
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
            $table->dropColumn('media_id');
        });
    }
}
