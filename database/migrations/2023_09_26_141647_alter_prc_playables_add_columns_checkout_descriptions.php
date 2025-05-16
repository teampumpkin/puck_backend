<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcPlayablesAddColumnsCheckoutDescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_playables', function (Blueprint $table) {
            $table->string('checkout_title_description')->nullable();
            $table->text('checkout_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_playables', function (Blueprint $table) {
            $table->dropColumn('checkout_title_description');
            $table->dropColumn('checkout_description');
        });
    }
}
