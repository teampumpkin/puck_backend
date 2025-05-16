<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcUsersTableToAddNewColumnCountryShortName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->string('country_short_name')->default('CA');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->dropColumn('country_short_name');
        });
    }
}
