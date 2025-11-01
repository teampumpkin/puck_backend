<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRequestVideoStatusToEvaluationSubmissions extends Migration
{
    public function up()
    {
        // Drop the old constraint if it exists
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

        // Add new constraint including 'request_video'
        DB::statement("
            ALTER TABLE evaluation_submissions
            ADD CONSTRAINT evaluation_submissions_status_check
            CHECK (status IN (
                'pending',
                'uploaded',
                'assigned',
                'in_progress',
                'rejected',
                'completed',
                'request_video'
            ))
        ");
    }

    public function down()
    {
        // Revert to previous constraint (without 'request_video')
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

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
}
