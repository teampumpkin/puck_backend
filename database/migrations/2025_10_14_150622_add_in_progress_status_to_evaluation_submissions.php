<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddInProgressStatusToEvaluationSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

        // Add new constraint with 'in_progress' included
        DB::statement("
            ALTER TABLE evaluation_submissions
            ADD CONSTRAINT evaluation_submissions_status_check
            CHECK (status IN (
                'pending',
                'uploaded',
                'assigned',
                'in_progress',
                'rejected',
                'completed'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

        DB::statement("
            ALTER TABLE evaluation_submissions
            ADD CONSTRAINT evaluation_submissions_status_check
            CHECK (status IN (
                'pending',
                'uploaded',
                'assigned',
                'rejected',
                'completed'
            ))
        ");
    }
}
