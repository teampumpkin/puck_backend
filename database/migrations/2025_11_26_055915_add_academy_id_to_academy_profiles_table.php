<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcademyIdToAcademyProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('academy_profiles', function (Blueprint $table) {

            $table->unsignedBigInteger('academy_id')->nullable()->after('v4_user_id');

            $table->foreign('academy_id')
                ->references('id')
                ->on('v4_academies')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('academy_profiles', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropColumn('academy_id');
        });
    }
}
