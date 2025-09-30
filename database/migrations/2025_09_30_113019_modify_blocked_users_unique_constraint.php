<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyBlockedUsersUniqueConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the existing unique constraint
        Schema::table('blocked_users', function (Blueprint $table) {
            $table->dropUnique(['blocker_id', 'blocked_id']);
        });

        // Add a partial unique index that only applies when unblocked_at is null
        DB::statement('CREATE UNIQUE INDEX blocked_users_active_unique ON blocked_users (blocker_id, blocked_id) WHERE unblocked_at IS NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the partial unique index
        Schema::table('blocked_users', function (Blueprint $table) {
            DB::statement('DROP INDEX IF EXISTS blocked_users_active_unique');
            $table->unique(['blocker_id', 'blocked_id']);
        });
    }
}
