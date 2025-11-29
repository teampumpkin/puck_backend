<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateV4UsersTableWithProviderData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_users', function (Blueprint $table) {
            // Add 'provider' column to store the provider name (default 'email')
            $table->string('provider')->default('email')->after('password');

            // Add 'provider_id' column to store the provider's unique identifier
            $table->string('provider_id')->nullable()->unique()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_users', function (Blueprint $table) {
            // Drop the added columns if the migration is rolled back
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
}
