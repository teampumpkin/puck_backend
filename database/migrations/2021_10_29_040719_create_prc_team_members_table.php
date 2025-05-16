<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcTeamMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_team_members', function (Blueprint $table) {
            $table->id();
            $table->integer('team_id');
            $table->integer('user_id')->nullable()->default(0);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->longText('profile_picture')->nullable();
            $table->string('email');
            $table->string('type');
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
        Schema::dropIfExists('prc_team_members');
    }
}
