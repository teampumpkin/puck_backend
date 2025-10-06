<?php

namespace Database\Factories;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationQuestionFactory extends Factory
{
    protected $model = EvaluationQuestion::class;

    public function definition()
    {
        $questions = [
            'How would you rate the player\'s overall performance?',
            'Rate the player\'s technical abilities.',
            'How well does the player execute under pressure?',
            'Rate the player\'s consistency throughout the game.',
            'How effective is the player in this area?',
            'Rate the player\'s improvement potential.',
            'How well does the player adapt to different situations?',
            'Rate the player\'s effort and dedication.',
            'How coachable is the player?',
            'Rate the player\'s game awareness.',
        ];

        return [
            'category_id' => EvaluationCategory::factory(),
            'title' => $this->faker->randomElement($questions),
            'question' => $this->faker->optional(0.6)->sentence(8),
            'required' => $this->faker->boolean(60),
            'sort_order' => $this->faker->numberBetween(1, 50),
            'active' => $this->faker->boolean(95),
            'meta' => null, // Empty meta data
        ];
    }

    public function active()
    {
        return $this->state(['active' => true]);
    }

    public function inactive()
    {
        return $this->state(['active' => false]);
    }

    public function required()
    {
        return $this->state(['required' => true]);
    }

    public function sortOrder(int $order)
    {
        return $this->state(['sort_order' => $order]);
    }

    public function forCategory($categoryId)
    {
        return $this->state(['category_id' => $categoryId]);
    }
}
