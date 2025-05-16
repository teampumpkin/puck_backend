<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Console\Command;

class SyncCountryStateCity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:countries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync country state city';
    /**
     * @var Country
     */
    private $country;
    /**
     * @var State
     */
    private $state;
    /**
     * @var City
     */
    private $city;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->country = new Country();
        $this->state   = new State();
        $this->city    = new City();
    }

    public function handle()
    {
        $count = 0;

        $country_data = json_decode(file_get_contents(asset('countries+states+cities.json')), true);

        foreach ($country_data as $country) {
            $country_obj = $this->country->where('country_name', $country['name'])->first();

            if (!empty($country_obj)) {
                continue;
            }
            $count++;
            $country_obj = $this->country->create([
                'country_name'       => $country['name'],
                'short_name_3_digit' => $country['iso3'],
                'short_name_2_digit' => $country['iso2'],
                'phone_code'         => $country['phone_code'],
                'region'             => $country['region'],
                'emoji'              => $country['emoji'],
                'emoji_code'         => $country['emojiU'],
            ]);

            foreach ($country['states'] as $state_data) {
                $state = $this->state->create([
                    'country_id' => $country_obj->id,
                    'state_name' => $state_data['name'],
                    'state_code' => $state_data['state_code'],
                ]);

                foreach ($state_data['cities'] as $city_data) {
                    $this->city->create([
                        'state_id'  => $state->id,
                        'city_name' => $city_data['name']
                    ]);
                }
            }
        }

        if ($count == 0) {
            $this->line('<fg=red>Nothing to sync</>');
            return 0;
        }
        $this->line('<fg=green>All Countries, States and Cities have been sync in the database</>');
        return 0;
    }
}
