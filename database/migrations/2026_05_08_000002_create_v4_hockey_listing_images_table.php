<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4HockeyListingImagesTable extends Migration
{
    public function up()
    {
        Schema::create('v4_hockey_listing_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('listing_id')
                ->constrained('v4_hockey_listings')
                ->onDelete('cascade');

            $table->string('image_url', 500);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['listing_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_hockey_listing_images');
    }
}
