<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsultationFieldsToEvaluationSubmissionVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluation_submission_versions', function (Blueprint $table) {
            $table->foreignId('report_id')
                ->nullable()
                ->constrained('evaluations')
                ->nullOnDelete();
            $table->date('consultation_date')->nullable();
            $table->time('consultation_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluation_submission_versions', function (Blueprint $table) {
            //
        });
    }
}
