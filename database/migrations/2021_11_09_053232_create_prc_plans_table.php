<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_plans', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('plan_name');
            $table->string('plan_code');
            $table->double('plan_price');
            $table->integer('interval');
            $table->string('interval_unit');
            $table->longText('plan_description')->nullable();
            $table->longText('extra_data')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('prc_plans');
    }
}
