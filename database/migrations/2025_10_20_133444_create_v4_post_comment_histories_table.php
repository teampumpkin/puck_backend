<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PostCommentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_post_comment_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id')->index();
            $table->unsignedBigInteger('comment_id')->index();
            $table->unsignedBigInteger('user_id')->index(); // who did the action
            $table->enum('action', ['created', 'edited', 'deleted']);
            $table->text('old_body')->nullable();
            $table->timestamps();

            $table->foreign('comment_id')->references('id')->on('v4_post_comments')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('v4_posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_post_comment_histories');
    }
}
