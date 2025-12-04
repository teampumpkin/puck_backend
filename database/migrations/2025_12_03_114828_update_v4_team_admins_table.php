<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateV4TeamAdminsTable extends Migration
{
    public function up()
    {
        Schema::table('v4_team_admins', function (Blueprint $table) {

            // Make admin_id nullable
            $table->unsignedBigInteger('admin_id')->nullable()->change();

            // Add new fields
            $table->string('designation')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
        });
    }

    public function down()
    {
        Schema::table('v4_team_admins', function (Blueprint $table) {
            $table->dropColumn([
                'designation',
                'profile_photo',
                'name',
                'email',
                'phone',
                'location',
            ]);

            $table->unsignedBigInteger('admin_id')->nullable(false)->change();
        });
    }
}
