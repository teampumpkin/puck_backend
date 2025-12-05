<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4SuspendedUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_suspended_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->foreignId('reason_id')->constrained('v4_suspend_reasons')->restrictOnDelete();
            $table->text('message')->nullable();
            $table->timestamp('suspended_at')->useCurrent();
            $table->timestamp('unsuspended_at')->nullable();
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
        Schema::dropIfExists('v4_suspended_users');
    }
}
