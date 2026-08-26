<?php
// database/migrations/2026_08_17_000000_create_v4_event_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_event_types');
    }
};
