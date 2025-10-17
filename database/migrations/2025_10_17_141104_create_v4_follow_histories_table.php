<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4FollowHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_follow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('v4_users')->onDelete('cascade');
            $table->enum('action', ['follow', 'unfollow', 'request', 'accept', 'reject']);
            $table->json('meta')->nullable(); // optional: store device info, IP, etc.
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_follow_histories');
    }
}
