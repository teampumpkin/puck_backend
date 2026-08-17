<?php

namespace Database\Seeders;

use App\Constants\EventTypes;
use App\Models\V4EventType;
use Illuminate\Database\Seeder;

class V4EventTypeSeeder extends Seeder
{
    public function run()
    {
        foreach (EventTypes::all() as $i => $name) {
            V4EventType::updateOrCreate(
                ['name' => $name],
                ['sort_order' => $i, 'active' => true]
            );
        }
    }
}
