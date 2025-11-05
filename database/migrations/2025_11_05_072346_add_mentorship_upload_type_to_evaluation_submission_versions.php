<?php

use App\Constants\MarketplaceTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMentorshipUploadTypeToEvaluationSubmissionVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluation_submission_versions', function (Blueprint $table) {
            $table->enum('mentorship_upload_type', ['submitted_video', 'requested_video'])
                ->nullable()
                ->after('mentorship_weekday')
                ->comment('Type of mentorship video upload: submitted by player or requested by evaluator');
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
            $table->dropColumn('mentorship_upload_type');
        });
    }
}