<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4BannedUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_banned_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->foreignId('reason_id')->constrained('v4_ban_reasons')->restrictOnDelete();
            $table->text('message')->nullable();
            $table->timestamp('banned_at')->useCurrent();
            $table->timestamp('unbanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_banned_users');
    }
}
