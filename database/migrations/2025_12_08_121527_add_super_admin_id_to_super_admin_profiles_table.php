<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuperAdminIdToSuperAdminProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admin_profiles', function (Blueprint $table) {
            // Add the super_admin_id column
            $table->unsignedBigInteger('super_admin_id')->nullable()->after('v4_user_id');

            // Add foreign key constraint
            $table->foreign('super_admin_id')
                ->references('id')
                ->on('v4_users')
                ->onDelete('cascade'); // optional: cascade delete
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('super_admin_profiles', function (Blueprint $table) {
            $table->dropForeign(['super_admin_id']);
            $table->dropColumn('super_admin_id');
        });
    }
}
