<?php

namespace Database\Seeders;

use App\Models\EvaluationCategory;
use Illuminate\Database\Seeder;

class EvaluationCategorySeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        EvaluationCategory::truncate();

        // Create only the 4 core hockey evaluation categories
        $coreCategories = [
            'Skating',
            'Compete',
            'Hockey IQ',
            'Skills',
        ];

        foreach ($coreCategories as $index => $categoryName) {
            EvaluationCategory::factory()
                ->active()
                ->sortOrder($index + 1)
                ->withName($categoryName)
                ->create();
        }
    }
}
