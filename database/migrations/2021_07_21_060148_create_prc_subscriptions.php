<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->text('features');
            $table->text('description');
            $table->string('status');
            $table->string('type');
            $table->string('user_type');
            $table->float('price');
            $table->string('promotion');
            $table->string('stripe_id');
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
