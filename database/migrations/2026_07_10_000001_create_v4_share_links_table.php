<?php
// database/migrations/2026_07_10_000001_create_v4_share_links_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_share_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->morphs('shareable');
            $table->foreignId('created_by')->nullable()->constrained('v4_users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('v4_users')->nullOnDelete();
            $table->timestamps();
        });

        // One ACTIVE link per shared entity (Postgres partial unique index)
        DB::statement(
            'CREATE UNIQUE INDEX v4_share_links_active_unique
             ON v4_share_links (shareable_type, shareable_id)
             WHERE revoked_at IS NULL'
        );
    }

    public function down()
    {
        Schema::dropIfExists('v4_share_links');
    }
};
