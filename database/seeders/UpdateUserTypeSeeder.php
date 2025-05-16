<?php

namespace Database\Seeders;

use App\Models\PrcUserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $type = DB::getSchemaBuilder()->getColumnType('prc_users', 'type');

        if ($type !== 'integer') {
            $user_types = PrcUserType::where('status', 1)->pluck('id', 'type_name');

            User::where('type', 'player')->update([
                'type' => $user_types['Player']
            ]);

            User::where('type', 'evaluator')->update([
                'type' => $user_types['Evaluator']
            ]);

            User::where('type', 'Developer')->update([
                'type' => $user_types['Developer']
            ]);

            User::where('type', 'team')->update([
                'type' => $user_types['Team']
            ]);

            User::where('type', 'admin')->update([
                'type' => $user_types['Admin']
            ]);

            User::where('type', 'scout')->update([
                'type' => $user_types['Scout']
            ]);

            DB::statement('alter table prc_users alter column type type integer using type::integer');
        }
    }
}
