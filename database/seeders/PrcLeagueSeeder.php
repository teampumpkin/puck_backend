<?php

namespace Database\Seeders;

use App\Models\PrcLeague;
use Illuminate\Database\Seeder;

class PrcLeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $league = PrcLeague::first();

        if (empty($league)) {
            PrcLeague::create([
                'league_name' => 'Generic'
            ]);
        }
    }
}
