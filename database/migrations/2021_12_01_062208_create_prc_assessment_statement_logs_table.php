<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcAssessmentStatementLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_assessment_statement_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id');
            $table->integer('report_id');
            $table->integer('assessment_value_id');
            $table->integer('statement_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prc_assessment_statement_logs');
    }
}
