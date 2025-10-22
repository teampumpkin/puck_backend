<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PostMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_post_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id')->index();
            $table->string('type'); // 'image' or 'video'
            $table->string('url'); // storage path or URL
            $table->string('mime_type')->nullable();
            $table->integer('order')->default(0);
            $table->json('meta')->nullable(); // optional for things like thumbnail, duration
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('v4_post_media');
    }
}
