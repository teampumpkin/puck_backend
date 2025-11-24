<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (Both users)
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('player_id');

            // Soft delete column
            $table->softDeletes();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('team_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

            $table->foreign('player_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

            // Optional: unique rule to prevent duplicates
            $table->unique(['team_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
