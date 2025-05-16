<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        City::truncate();
  
        $json = Storage::disk("local")->get("/json/cities.json");
        $cities = json_decode($json);
  
        foreach ($cities as $key => $value) {
            City::create([
                'id' => $value->id,
                'state_id' => $value->state_id,
                'city_name' => $value->name,
                'status' => 1
            ]);
        }
    }
}
