<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4TeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_teams', function (Blueprint $table) {
            $table->id();

            $table->string('team_name');
            $table->string('administrator_first_name')->nullable();
            $table->string('administrator_last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('leagues')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->integer('team_years_running')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_teams');
    }
}
