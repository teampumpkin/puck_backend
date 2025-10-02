<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->foreignId('question_option_id')->nullable()->constrained('evaluation_question_options')->onDelete('set null');
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 to 5.00 with half steps
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['evaluation_id', 'question_id']);
            $table->index(['question_id', 'rating']);
            $table->index('question_option_id');
            $table->index('rating');

            // Unique constraint to prevent duplicate answers for same question in same evaluation
            $table->unique(['evaluation_id', 'question_id'], 'unique_evaluation_question');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_answers');
    }
}