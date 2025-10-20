<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4FollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_follows', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Define columns once
            $table->unsignedBigInteger('follower_id');
            $table->unsignedBigInteger('following_id');

            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('follower_id');
            $table->index('following_id');

            // Unique constraint to prevent duplicate follows
            $table->unique(['follower_id', 'following_id']);

            // Foreign key constraints (with names for PostgreSQL)
            $table->foreign('follower_id', 'fk_follows_follower')
                ->references('id')->on('v4_users')->onDelete('cascade');

            $table->foreign('following_id', 'fk_follows_following')
                ->references('id')->on('v4_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_follows');
    }
}
