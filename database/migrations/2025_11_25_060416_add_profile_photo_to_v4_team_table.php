<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfilePhotoToV4TeamTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('team_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->dropColumn('profile_photo');
        });
    }
}
