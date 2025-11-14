<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('v4_player_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('submission_id')->constrained('evaluation_submissions')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v4_player_portfolios');
    }
};
