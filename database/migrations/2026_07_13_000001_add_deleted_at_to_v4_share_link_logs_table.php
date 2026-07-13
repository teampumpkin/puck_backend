<?php
// database/migrations/2026_07_13_000001_add_deleted_at_to_v4_share_link_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('v4_share_link_logs', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('v4_share_link_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
