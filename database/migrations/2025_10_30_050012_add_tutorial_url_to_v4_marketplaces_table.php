<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTutorialUrlToV4MarketplacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_marketplaces', function (Blueprint $table) {
            $table->string('tutorial_url')->nullable()->after('header_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_marketplaces', function (Blueprint $table) {
            $table->dropColumn('tutorial_url');
        });
    }
}
