<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationSubmissionVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('evaluation_submissions')->onDelete('cascade');
            $table->string('file_path');
            $table->json('file_meta')->nullable(); // size, type, duration, etc.
            $table->foreignId('uploaded_by')->constrained('v4_users')->onDelete('cascade');
            $table->timestamp('uploaded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['submission_id', 'created_at']);
            $table->index('uploaded_by');
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_submission_versions');
    }
}