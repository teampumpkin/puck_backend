<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEvaluationSubmissionsAndVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add foreign key from evaluation_submissions to evaluation_submission_versions
        Schema::table('evaluation_submissions', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('evaluation_submission_versions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the foreign key constraints
        Schema::table('evaluation_submissions', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
    }
}