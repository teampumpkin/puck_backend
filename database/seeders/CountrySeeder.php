<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Country::truncate();
  
        $json = Storage::disk("local")->get("/json/countries.json");
        $countries = json_decode($json);
  
        foreach ($countries as $key => $value) {
            Country::create([
                'id' => $value->id,
                'country_name' => $value->name,
                'short_name_3_digit' => $value->iso3,
                'short_name_2_digit' => $value->iso2,
                'phone_code' => $value->phone_code,
                'region' => $value->region,
                'emoji' => $value->emoji,
                'emoji_code' => $value->emojiU,
                'status' => 1
            ]);
        }
    }
}
