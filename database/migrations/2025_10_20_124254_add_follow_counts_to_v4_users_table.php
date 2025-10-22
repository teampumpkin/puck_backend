<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFollowCountsToV4UsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_users', function (Blueprint $table) {
            if (!Schema::hasColumn('v4_users', 'followers_count')) {
                $table->unsignedInteger('followers_count')->default(0)->after('is_private');
            }
            if (!Schema::hasColumn('v4_users', 'followings_count')) {
                $table->unsignedInteger('followings_count')->default(0)->after('followers_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_users', function (Blueprint $table) {
            if (Schema::hasColumn('v4_users', 'followers_count')) {
                $table->dropColumn('followers_count');
            }
            if (Schema::hasColumn('v4_users', 'followings_count')) {
                $table->dropColumn('followings_count');
            }
        });
    }
}
