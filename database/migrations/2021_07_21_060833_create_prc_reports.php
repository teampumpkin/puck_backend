<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('player_user_id');
            $table->integer('scout_user_id');
            $table->text('game')->nullable();
            $table->text('skills');
            $table->string('long_range_potential')->nullable();
            $table->string('scout_comment')->nullable();
            $table->string('recommendation')->nullable();
            $table->boolean('published')->default(0);
            $table->integer('scout_request_id')->nullable();
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
        Schema::dropIfExists('prc_reports');
    }
}
