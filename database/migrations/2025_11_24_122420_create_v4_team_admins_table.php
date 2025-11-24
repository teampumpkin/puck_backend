<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4TeamAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_team_admins', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('team_id');   // FK to v4_teams
            $table->unsignedBigInteger('admin_id');  // FK to v4_users

            $table->foreign('team_id')
                ->references('id')->on('v4_teams')
                ->onDelete('cascade');

            $table->foreign('admin_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

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
        Schema::dropIfExists('v4_team_admins');
    }
}
