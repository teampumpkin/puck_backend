<?php

namespace Database\Factories;

use App\Models\EvaluationQuestionOption;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationQuestionOptionFactory extends Factory
{
    protected $model = EvaluationQuestionOption::class;

    public function definition()
    {
        $options = [
            ['title' => 'Excellent', 'description' => 'Exceptional performance', 'rating' => 5.0],
            ['title' => 'Very Good', 'description' => 'Above average performance', 'rating' => 4.0],
            ['title' => 'Good', 'description' => 'Meets expectations', 'rating' => 3.0],
            ['title' => 'Fair', 'description' => 'Below expectations', 'rating' => 2.0],
            ['title' => 'Poor', 'description' => 'Needs significant improvement', 'rating' => 1.0],
        ];

        $option = $this->faker->randomElement($options);

        return [
            'question_id' => EvaluationQuestion::factory(),
            'title' => $option['title'],
            'description' => $option['description'],
            'rating' => $option['rating'],
            'sort_order' => $this->faker->numberBetween(1, 10),
            'meta' => null, // Empty meta data
        ];
    }

    public function forQuestion($questionId)
    {
        return $this->state(['question_id' => $questionId]);
    }

    public function withRating(float $rating, string $title, string $description = null)
    {
        return $this->state([
            'rating' => $rating,
            'title' => $title,
            'description' => $description ?? "Rating option with value {$rating}",
        ]);
    }

    public function sortOrder(int $order)
    {
        return $this->state(['sort_order' => $order]);
    }
}
