<?php

namespace Database\Factories;

use App\Models\EvaluationQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationQuestionOptionFactory extends Factory
{
    protected $model = EvaluationQuestionOption::class;

    public function definition()
    {
        return [
            'question_id' => null, // Always set in seeder
            'title' => '',
            'option' => '',
            'rating' => 0,
            'sort_order' => 0,
            'meta' => null,
        ];
    }

    public function forQuestion($questionId)
    {
        return $this->state(['question_id' => $questionId]);
    }

    public function withOption(string $title, string $option, float $rating, int $sortOrder)
    {
        return $this->state([
            'title' => $title,
            'option' => $option,
            'rating' => $rating,
            'sort_order' => $sortOrder,
        ]);
    }
}
