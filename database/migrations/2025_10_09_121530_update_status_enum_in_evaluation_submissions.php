<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateStatusEnumInEvaluationSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop old check constraint
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

        // Add new check constraint with updated enum values
        DB::statement("
            ALTER TABLE evaluation_submissions
            ADD CONSTRAINT evaluation_submissions_status_check
            CHECK (status IN ('uploaded', 'assigned', 'rejected', 'completed', 'pending'))
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE evaluation_submissions DROP CONSTRAINT IF EXISTS evaluation_submissions_status_check");

        DB::statement("
            ALTER TABLE evaluation_submissions
            ADD CONSTRAINT evaluation_submissions_status_check
            CHECK (status IN ('uploaded', 'assigned', 'evaluating', 'rejected', 'accepted', 'completed'))
        ");
    }
}
