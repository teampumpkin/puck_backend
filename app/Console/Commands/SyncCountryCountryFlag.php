<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;

class SyncCountryCountryFlag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:country-flags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync country flags';
    /**
     * @var Country
     */
    private $country;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->country = new Country();
    }

    public function handle()
    {
        $count = 0;

        $country_data = json_decode(file_get_contents(asset('countries-flag.json')), true);

        foreach ($country_data as $country) {
            $country_obj = $this->country->where('short_name_2_digit', $country['countryCode'])->first();

            if (empty($country_obj)) {
                continue;
            }
            $count++;
            $country_obj->country_flag = $country['flag'];
            $country_obj->save();
        }

        if ($count == 0) {
            $this->line('<fg=red>Nothing to sync</>');
            return 0;
        }
        $this->line('<fg=green>All Country flags have been sync in the database</>');
        return 0;
    }
}
