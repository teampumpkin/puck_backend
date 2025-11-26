<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4AcademiesTable extends Migration
{
    public function up()
    {
        Schema::create('v4_academies', function (Blueprint $table) {
            $table->id();

            // Strings
            $table->string('academy_name')->nullable();
            $table->string('administrator_first_name')->nullable();
            $table->string('administrator_last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('country')->nullable();

            // JSON
            $table->json('leagues')->nullable();

            // Integer
            $table->integer('academy_years_running')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_academies');
    }
}
