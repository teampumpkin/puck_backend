<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PrcSkillSeeder::class);
        $this->call(CreateAdminSeeder::class);
        $this->call(CreateUserTypeAndModulesSeeder::class);
        $this->call(PrcLeagueSeeder::class);
        $this->call(UpdateUserTypeSeeder::class);
        $this->call(PrcPositionSeeder::class);
        //        $this->call(SyncZohoPlanSeeder::class);
        $this->call(AssessmentDataSeeder::class);
        $this->call(AssessmentStatementSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(StateSeeder::class);
        $this->call(CitySeeder::class);
    }
}
