<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavouriteUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('favourite_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('favourite_id');

            $table->softDeletes();
            $table->timestamps();

            // Foreign keys → v4_users
            $table->foreign('user_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

            $table->foreign('favourite_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

            // Prevent duplicate favourite entries
            $table->unique(['user_id', 'favourite_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favourite_users');
    }
}
