<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            // Add nullable added_by column
            $table->unsignedBigInteger('added_by')
                ->after('player_id');

            // Add foreign key constraint
            $table->foreign('added_by')
                ->references('id')->on('v4_users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn('added_by');
        });
    }
};
