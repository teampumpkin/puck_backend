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
    }
}
