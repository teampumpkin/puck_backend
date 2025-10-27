<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConversationIdToV4PostSharesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_post_shares', function (Blueprint $table) {
            $table->string('conversation_id')->nullable()->index()->after('post_id');

            // Drop the old unique constraint (if it exists)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('v4_post_shares');

            // Safely remove old unique index on (user_id, post_id)
            if (array_key_exists('v4_post_shares_user_id_post_id_unique', $indexes)) {
                $table->dropUnique('v4_post_shares_user_id_post_id_unique');
            } else {
                // Some DBs use a shorter auto-generated name
                try {
                    $table->dropUnique(['user_id', 'post_id']);
                } catch (\Throwable $e) {
                    // ignore if not present
                }
            }

            // Add new unique constraint with conversation_id
            $table->unique(
                ['user_id', 'post_id', 'conversation_id'],
                'v4_post_shares_user_post_conversation_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_post_shares', function (Blueprint $table) {
            $table->dropColumn('conversation_id');

            // Drop the new unique index
            $table->dropUnique('v4_post_shares_user_post_conversation_unique');

            // Recreate the old one
            $table->unique(['user_id', 'post_id'], 'v4_post_shares_user_id_post_id_unique');
        });
    }
}
