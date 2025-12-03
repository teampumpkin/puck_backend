<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateV4AcademyAdminsTable extends Migration
{
    public function up(): void
    {
        Schema::table('v4_academy_admins', function (Blueprint $table) {

            // Make admin_id nullable
            $table->unsignedBigInteger('admin_id')->nullable()->change();

            // Add new fields
            $table->string('designation')->nullable()->after('admin_id');
            $table->string('name')->nullable()->after('designation');
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('location')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('v4_academy_admins', function (Blueprint $table) {
            // Reverse changes
            $table->unsignedBigInteger('admin_id')->nullable(false)->change();

            $table->dropColumn([
                'designation',
                'name',
                'email',
                'phone',
                'location',
            ]);
        });
    }
}
