<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcOneTimeSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_one_time_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string("type")->default('evaluation');
            $table->integer('user_id');
            $table->string('subscription_id');
            $table->string('plan_code');
            $table->string('card_id');
            $table->string('start_from');
            $table->string('renew_on');
            $table->longText('extra_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prc_one_time_subscriptions');
    }
}
