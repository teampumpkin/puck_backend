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
        $this->call(V4SuperAdminSeeder::class);
        $this->call(PrcSkillSeeder::class);
        $this->call(CreateAdminSeeder::class);
        $this->call(CreateUserTypeAndModulesSeeder::class);
        $this->call(PrcLeagueSeeder::class);
        $this->call(UpdateUserTypeSeeder::class);
        $this->call(PrcPositionSeeder::class);
        //        $this->call(SyncZohoPlanSeeder::class);
        $this->call(AssessmentDataSeeder::class);
        $this->call(AssessmentStatementSeeder::class);
        // $this->call(CountrySeeder::class);
        // $this->call(StateSeeder::class);
        // $this->call(CitySeeder::class);
        $this->call(EvaluationRejectionReasonSeeder::class);
        $this->call(V4UserReportReasonSeeder::class);
        $this->call(EvaluationCategorySeeder::class);
        $this->call(EvaluationQuestionSeeder::class);
        $this->call(EvaluationQuestionOptionSeeder::class);
        $this->call(V4InAppPurchaseSeeder::class);
        $this->call(V4BanReasonSeeder::class);
        $this->call(V4SuspendReasonSeeder::class);
        $this->call(HockeyListingFeeSeeder::class);
        $this->call(V4EventTypeSeeder::class);
    }
}
