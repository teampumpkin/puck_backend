<?php
// database/migrations/2026_07_10_000002_create_v4_share_link_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_share_link_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->constrained('v4_share_links')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('v4_users')->nullOnDelete();
            $table->string('action'); // 'created' | 'shared' | 'revoked' | 'opened'
            $table->string('ref_code', 8)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_share_link_logs');
    }
};
