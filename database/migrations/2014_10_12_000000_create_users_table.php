<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('dob')->nullable();
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('team_id')->nullable();
            $table->string('position')->nullable();
            $table->string('handedness')->nullable();
            $table->integer('weight')->default(0);
            $table->string('height')->nullable();
            $table->integer('rating_count')->default(0);
            $table->float('rating_point')->nullable();
            $table->longText('profile_picture')->nullable();
            $table->string('status')->nullable();
            $table->string('setting')->nullable();
            $table->string('league')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('token')->nullable();
            $table->integer('password_reset_pin')->nullable();
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
        Schema::dropIfExists('prc_users');
    }
}
