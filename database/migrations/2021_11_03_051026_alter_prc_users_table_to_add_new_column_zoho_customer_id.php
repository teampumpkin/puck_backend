<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcUsersTableToAddNewColumnZohoCustomerId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->string('zoho_customer_id', 255)->nullable();
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
            $table->dropColumn('zoho_customer_id');
        });
    }
}
