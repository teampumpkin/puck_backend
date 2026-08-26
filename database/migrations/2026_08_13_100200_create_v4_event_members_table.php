<?php
// database/migrations/2026_08_13_100200_create_v4_event_members_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_event_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('v4_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('action'); // join | leave
            $table->timestamps();
            $table->index(['event_id', 'user_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_event_members');
    }
};
