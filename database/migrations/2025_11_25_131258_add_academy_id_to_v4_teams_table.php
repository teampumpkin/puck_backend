<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcademyIdToV4TeamsTable extends Migration
{
    public function up(): void
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')
                ->nullable()
                ->after('id');

            $table->foreign('academy_id')
                ->references('id')
                ->on('v4_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('v4_teams', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropColumn('academy_id');
        });
    }
}
