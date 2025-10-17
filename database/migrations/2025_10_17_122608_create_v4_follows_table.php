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
            // follower and followed using foreignId with constraints
            $table->foreignId('follower_id')
                ->constrained('v4_users')
                ->cascadeOnDelete()
                ->index(); // who initiated the follow

            $table->foreignId('following_id')
                ->constrained('v4_users')
                ->cascadeOnDelete()
                ->index(); // who is being followed
            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // prevent duplicates
            $table->unique(['follower_id', 'followed_id']);
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
