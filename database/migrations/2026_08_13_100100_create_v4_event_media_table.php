<?php
// database/migrations/2026_08_13_100100_create_v4_event_media_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_event_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('v4_events')->cascadeOnDelete();
            $table->string('media_type'); // image | video
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('event_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_event_media');
    }
};
