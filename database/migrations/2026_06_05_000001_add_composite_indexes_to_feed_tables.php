<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeIndexesToFeedTables extends Migration
{
    public function up()
    {
        Schema::table('v4_follows', function (Blueprint $table) {
            $table->index(['follower_id', 'status'], 'v4_follows_follower_status_index');
        });

        Schema::table('v4_posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'v4_posts_user_created_index');
        });
    }

    public function down()
    {
        Schema::table('v4_follows', function (Blueprint $table) {
            $table->dropIndex('v4_follows_follower_status_index');
        });

        Schema::table('v4_posts', function (Blueprint $table) {
            $table->dropIndex('v4_posts_user_created_index');
        });
    }
}
