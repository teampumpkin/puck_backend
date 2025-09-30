<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlockedUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('blocked_id')->constrained('v4_users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->timestamp('blocked_at');
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blocked_users');
    }
}
