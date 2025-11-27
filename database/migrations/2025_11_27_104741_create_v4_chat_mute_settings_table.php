<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4ChatMuteSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_chat_mute_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('v4_users')->onDelete('cascade');
            $table->string('chat_id');
            $table->integer('duration')->nullable();
            $table->timestamp('muted_until')->nullable(); // Expiry time for mute
            $table->boolean('active')->default(true); // Is mute active?
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_chat_mute_settings');
    }
}
