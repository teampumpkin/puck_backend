<?php

namespace Database\Factories;

use App\Models\EvaluationRejectionReason;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationRejectionReasonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EvaluationRejectionReason::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $rejectionReasons = [
            [
                'title' => 'Insufficient Skill Level',
                'description' => 'Player does not demonstrate the required skill level for their position or age group.',
            ],
            [
                'title' => 'Poor Game Understanding',
                'description' => 'Player lacks tactical awareness and understanding of game situations.',
            ],
            [
                'title' => 'Inconsistent Performance',
                'description' => 'Player shows inconsistent performance throughout the evaluation period.',
            ],
            [
                'title' => 'Attitude Issues',
                'description' => 'Player demonstrates poor attitude, lack of coachability, or negative behavior.',
            ],
            [
                'title' => 'Physical Limitations',
                'description' => 'Player has physical limitations that prevent them from performing at the required level.',
            ],
            [
                'title' => 'Technical Deficiencies',
                'description' => 'Player has significant technical skills gaps that need improvement.',
            ],
            [
                'title' => 'Lack of Commitment',
                'description' => 'Player shows insufficient dedication or commitment to training and improvement.',
            ],
            [
                'title' => 'Team Chemistry Issues',
                'description' => 'Player does not work well with teammates or disrupts team dynamics.',
            ],
            [
                'title' => 'Conditioning Concerns',
                'description' => 'Player lacks the physical conditioning required for competitive play.',
            ],
            [
                'title' => 'Age/Experience Mismatch',
                'description' => 'Player is not at the appropriate level for their age or experience.',
            ],
        ];

        $reason = $this->faker->randomElement($rejectionReasons);

        return [
            'title' => $reason['title'],
            'description' => $reason['description'],
            'active' => $this->faker->boolean(85), // 85% chance of being active
            'sort_order' => $this->faker->numberBetween(1, 100),
            'meta' => [
                'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
                'category' => $this->faker->randomElement(['technical', 'tactical', 'physical', 'mental']),
                'requires_follow_up' => $this->faker->boolean(30),
                'created_by' => $this->faker->randomElement(['admin', 'evaluator', 'coach']),
            ],
        ];
    }

    /**
     * Indicate that the rejection reason should be active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => true,
            ];
        });
    }

    /**
     * Indicate that the rejection reason should be inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }

    /**
     * Set a specific sort order.
     *
     * @param int $order
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function sortOrder(int $order)
    {
        return $this->state(function (array $attributes) use ($order) {
            return [
                'sort_order' => $order,
            ];
        });
    }
}
