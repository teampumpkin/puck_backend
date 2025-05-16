<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcSubscriptionsTableToAddNewColumnIsCancelled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_subscriptions', function (Blueprint $table) {
            $table->tinyInteger('is_cancelled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_subscriptions', function (Blueprint $table) {
            $table->dropColumn('is_cancelled');
        });
    }
}
