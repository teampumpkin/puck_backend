<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRequestVideoStatusToEvaluationSubmissions extends Migration
{
    public function up()
    {
        // For MySQL, we need to modify the enum column
        DB::statement("ALTER TABLE evaluation_submissions MODIFY COLUMN status ENUM('pending', 'in_progress', 'uploaded', 'assigned', 'rejected', 'completed', 'request_video') NOT NULL DEFAULT 'uploaded'");
    }

    public function down()
    {
        // Remove the 'request_video' status
        DB::statement("ALTER TABLE evaluation_submissions MODIFY COLUMN status ENUM('pending', 'in_progress', 'uploaded', 'assigned', 'rejected', 'completed') NOT NULL DEFAULT 'uploaded'");
    }
}