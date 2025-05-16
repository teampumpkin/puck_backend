<?php

namespace App\Console;

use App\Console\Commands\GenerateSubscriptionForUsers;
use App\Console\Commands\SyncAdvanceAssessmentSkill;
use App\Console\Commands\SyncCountryCountryFlag;
use App\Console\Commands\SyncCountryStateCity;
use App\Console\Commands\SyncRouteModules;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncRouteModules::class,
        SyncAdvanceAssessmentSkill::class,
        SyncCountryStateCity::class,
        SyncCountryCountryFlag::class,
        GenerateSubscriptionForUsers::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
