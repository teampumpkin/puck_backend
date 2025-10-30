<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMentorshipWeekdayToEvaluationSubmissionVersions extends Migration
{
    public function up()
    {
        Schema::table('evaluation_submission_versions', function (Blueprint $table) {
            $table->enum('mentorship_weekday', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable()->after('consultation_time');
        });
    }

    public function down()
    {
        Schema::table('evaluation_submission_versions', function (Blueprint $table) {
            $table->dropColumn('mentorship_weekday');
        });
    }
}