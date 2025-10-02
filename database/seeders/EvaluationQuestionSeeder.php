<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationCategory;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing questions
        EvaluationQuestion::truncate();

        // Get all categories
        $categories = EvaluationCategory::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No evaluation categories found. Please run EvaluationCategorySeeder first.');
            return;
        }

        // Define specific titles and questions for each category
        $categoryQuestions = [
            'Skating' => [
                [
                    'title' => 'Skating Mechanics',
                    'question' => "What do you rate the player's skating mechanics?"
                ],
                [
                    'title' => 'Skating Control',
                    'question' => "What do you rate the player's skating control?"
                ],
                [
                    'title' => 'Skating Speed',
                    'question' => "What do you rate the player's skating speed?"
                ],
            ],
            'Compete' => [
                [
                    'title' => 'Compete Engagement',
                    'question' => "What do you rate the player's compete engagement?"
                ],
                [
                    'title' => 'Compete Technique',
                    'question' => "What do you rate the player's compete technique?"
                ],
                [
                    'title' => 'Compete Persistence',
                    'question' => "What do you rate the player's compete persistence?"
                ],
            ],
            'Skills' => [
                [
                    'title' => 'Skills Puck Handling',
                    'question' => "What do you rate the player's skill puck handling?"
                ],
                [
                    'title' => 'Skills Passing',
                    'question' => "What do you rate the player's skills passing?"
                ],
                [
                    'title' => 'Skills Shooting',
                    'question' => "What do you rate the player's skills shooting?"
                ],
            ],
            'Hockey IQ' => [
                [
                    'title' => 'Hockey IQ Vision',
                    'question' => "What do you rate the player's hockey IQ vision?"
                ],
                [
                    'title' => 'Hockey IQ Position',
                    'question' => "What do you rate the player's hockey IQ position?"
                ],
                [
                    'title' => 'Hockey IQ Execution',
                    'question' => "What do you rate the player's hockey IQ execution?"
                ],
            ],
        ];

        // Create questions for each category
        foreach ($categories as $category) {
            if (isset($categoryQuestions[$category->name])) {
                $questions = $categoryQuestions[$category->name];

                foreach ($questions as $index => $q) {
                    EvaluationQuestion::factory()
                        ->active()
                        ->required()
                        ->sortOrder($index + 1)
                        ->forCategory($category->id)
                        ->create([
                            'title' => $q['title'],
                            'question' => $q['question'],
                        ]);
                }
            }
        }

        $this->command->info('Created specific questions with proper titles and question text for all categories.');
    }
}
