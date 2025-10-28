<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4ConsultationFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_consultation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_version_id')
                ->constrained('evaluation_submission_versions')
                ->cascadeOnDelete();
            $table->foreignId('evaluator_id')
                ->constrained('v4_users')
                ->cascadeOnDelete();
            $table->text('remarks')->nullable();
            $table->json('urls')->nullable(); // e.g. { "zoom": "...", "recording": "..." }
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_consultation_feedback');
    }
}
