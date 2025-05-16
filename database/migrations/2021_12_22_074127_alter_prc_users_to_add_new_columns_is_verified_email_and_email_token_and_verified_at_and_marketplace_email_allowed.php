<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *
 */
class AlterPrcUsersToAddNewColumnsIsVerifiedEmailAndEmailTokenAndVerifiedAtAndMarketplaceEmailAllowed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_users', function (Blueprint $table) {
            $table->tinyInteger('is_email_verified')->default(true);
            $table->string('email_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('marketplace_email_allowed')->default(true);
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
            $table->dropColumn('is_email_verified');
            $table->dropColumn('email_token');
            $table->dropColumn('email_verified_at');
            $table->dropColumn('marketplace_email_allowed');
        });
    }
}
