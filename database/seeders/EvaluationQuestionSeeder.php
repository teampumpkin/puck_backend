<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    public function run()
    {
        // Get all categories
        $categories = EvaluationCategory::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No evaluation categories found. Please run EvaluationCategorySeeder first.');
            return;
        }

        // Create questions for each category
        foreach ($categories as $category) {
            $questionCount = rand(3, 6); // 3-6 questions per category

            for ($i = 1; $i <= $questionCount; $i++) {
                EvaluationQuestion::factory()
                    ->active()
                    ->required()
                    ->sortOrder($i)
                    ->forCategory($category->id)
                    ->create([
                        'title' => "How would you rate the player's {$category->name}?",
                        'description' => "Evaluate the player's performance in {$category->name}."
                    ]);
            }
        }

        // Create additional random questions
        EvaluationQuestion::factory(10)->create();

        // Create some inactive questions
        EvaluationQuestion::factory(3)->inactive()->create();
    }
}
