<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PlayerAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_player_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')
                ->constrained('v4_users')
                ->onDelete('cascade')
                ->comment('Reference to player in v4_users table');
            $table->string('title')->comment('Achievement title');
            $table->string('file_path')->comment('File path for achievement document/image');
            $table->text('details')->nullable()->comment('Achievement details/description');
            $table->json('meta')->nullable()->comment('Additional metadata in JSON format');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('player_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_player_achievements');
    }
}