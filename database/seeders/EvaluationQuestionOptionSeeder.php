<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestionOption;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;

class EvaluationQuestionOptionSeeder extends Seeder
{
    public function run()
    {
        // Get all questions
        $questions = EvaluationQuestion::all();

        if ($questions->isEmpty()) {
            $this->command->warn('No evaluation questions found. Please run EvaluationQuestionSeeder first.');
            return;
        }

        // Create standard 5-point rating scale for each question
        foreach ($questions as $question) {
            $this->createRatingScale($question->id);
        }
    }

    private function createRatingScale($questionId)
    {
        $ratingOptions = [
            ['title' => 'Excellent', 'description' => 'Exceptional performance', 'rating' => 5.0, 'sort_order' => 1],
            ['title' => 'Very Good', 'description' => 'Above average performance', 'rating' => 4.0, 'sort_order' => 2],
            ['title' => 'Good', 'description' => 'Meets expectations', 'rating' => 3.0, 'sort_order' => 3],
            ['title' => 'Fair', 'description' => 'Below expectations', 'rating' => 2.0, 'sort_order' => 4],
            ['title' => 'Poor', 'description' => 'Needs improvement', 'rating' => 1.0, 'sort_order' => 5],
        ];

        foreach ($ratingOptions as $option) {
            EvaluationQuestionOption::factory()
                ->forQuestion($questionId)
                ->sortOrder($option['sort_order'])
                ->withRating($option['rating'], $option['title'], $option['description'])
                ->create();
        }
    }
}
