<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        State::truncate();
  
        $json = Storage::disk("local")->get("/json/states.json");
        $states = json_decode($json);
  
        foreach ($states as $key => $value) {
            State::create([
                'id' => $value->id,
                'country_id' => $value->country_id,
                'state_name' => $value->name,
                'state_code' => $value->state_code,
                'status' => 1
            ]);
        }
    }
}
