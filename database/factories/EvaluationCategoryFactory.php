<?php

namespace Database\Factories;

use App\Models\EvaluationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EvaluationCategoryFactory extends Factory
{
    protected $model = EvaluationCategory::class;

    public function definition()
    {
        $categories = [
            'Skating',
            'Skills',
            'Hockey IQ',
            'Compete',
            'Physical',
            'Character',
            'Goaltending',
            'Offensive Play',
            'Defensive Play',
            'Special Teams',
            'Leadership',
            'Teamwork',
            'Communication',
            'Mental Toughness',
            'Conditioning'
        ];

        $name = $this->faker->randomElement($categories) . ' ' . $this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(10),
            'active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(1, 100),
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

    public function sortOrder(int $order)
    {
        return $this->state(['sort_order' => $order]);
    }

    public function withName(string $name)
    {
        return $this->state([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
