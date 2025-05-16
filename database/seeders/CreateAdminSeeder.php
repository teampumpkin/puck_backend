<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user      = User::where('type', 8)->first();
        $developer = User::where('type', 1)->first();

        if (empty($developer)) {
            User::create([
                'first_name' => 'Pinak',
                'last_name'  => 'Mehta',
                'email'      => 'pinakamehta@gmail.com',
                'password'   => Hash::make('P!n@k@2021'),
                'type'       => 1,
                'status'     => 'Active'
            ]);
        }

        if (empty($user)) {
            User::insert([
                [
                    'first_name' => 'Hashmukh',
                    'last_name'  => 'Barochiya',
                    'email'      => 'hasmukhb@gmail.com',
                    'password'   => Hash::make('123456'),
                    'type'       => 8,
                    'status'     => 'Active'
                ],
                [
                    'first_name' => 'Suresh',
                    'last_name'  => 'Dobariya',
                    'email'      => 'sureshd@gmail.com',
                    'password'   => Hash::make('123456'),
                    'type'       => 8,
                    'status'     => 'Active'
                ],
                [
                    'first_name' => 'Ankit',
                    'last_name'  => 'Makwana',
                    'email'      => 'ankitm@gmail.com',
                    'password'   => Hash::make('123456'),
                    'type'       => 8,
                    'status'     => 'Active'
                ],
            ]);
        }
    }
}
