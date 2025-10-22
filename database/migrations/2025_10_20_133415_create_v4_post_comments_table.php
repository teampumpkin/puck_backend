<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PostCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_post_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('post_id')->index();
            $table->text('body');
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // nested reply
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('v4_users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('v4_posts')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('v4_post_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_post_comments');
    }
}
