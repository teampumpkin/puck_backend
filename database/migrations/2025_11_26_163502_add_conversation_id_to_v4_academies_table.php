<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConversationIdToV4AcademiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_academies', function (Blueprint $table) {
            $table->string('conversation_id')->nullable()->after('academy_years_running');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_academies', function (Blueprint $table) {
            $table->dropColumn('conversation_id');
        });
    }
}
