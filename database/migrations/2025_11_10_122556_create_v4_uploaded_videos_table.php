<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4UploadedVideosTable extends Migration
{
    public function up(): void
    {
        Schema::create('v4_uploaded_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('v4_users')
                ->onDelete('cascade')
                ->comment('Reference to user in v4_users table');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v4_uploaded_videos');
    }
}
;
