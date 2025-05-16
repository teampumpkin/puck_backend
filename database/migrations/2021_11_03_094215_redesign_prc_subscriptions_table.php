<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RedesignPrcSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('prc_subscriptions');

        Schema::create('prc_subscriptions', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('prc_subscriptions');
    }
}
