<?php

namespace Database\Seeders;

use App\Models\EvaluationRejectionReason;
use Illuminate\Database\Seeder;

class EvaluationRejectionReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 15 rejection reasons with factory
        EvaluationRejectionReason::factory(15)->create();

        // Create some specific active rejection reasons with defined sort order
        EvaluationRejectionReason::factory()->active()->sortOrder(1)->create([
            'title' => 'Skills Below Standard',
            'description' => 'Player\'s technical skills are below the required standard for this level.',
        ]);

        EvaluationRejectionReason::factory()->active()->sortOrder(2)->create([
            'title' => 'Attendance Issues',
            'description' => 'Player has poor attendance record during evaluation period.',
        ]);

        EvaluationRejectionReason::factory()->active()->sortOrder(3)->create([
            'title' => 'Communication Problems',
            'description' => 'Player has difficulty communicating effectively with coaches and teammates.',
        ]);
    }
}
