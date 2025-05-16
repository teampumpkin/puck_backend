<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcReportsToAddNewColumnModifiedSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_reports', function (Blueprint $table) {
            $table->longText('modified_skills')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_reports', function (Blueprint $table) {
            $table->dropColumn('modified_skills');
        });
    }
}
