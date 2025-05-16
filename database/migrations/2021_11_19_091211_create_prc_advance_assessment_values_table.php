<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrcAdvanceAssessmentValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prc_advance_assessment_values', function (Blueprint $table) {
            $table->id();
            $table->integer('skill_id');
            $table->float('rating');
            $table->string('key_word')->nullable();
            $table->string('rubric_classification')->nullable();
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
        Schema::dropIfExists('prc_advance_assessment_values');
    }
}
