<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationQuestionOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('rating', 3, 1); // Allows values like 4.5, 3.0, etc.
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
            $table->index(['question_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_question_options');
    }
}