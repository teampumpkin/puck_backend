<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\V4User;
use App\Models\SuperAdminProfile;

class V4SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $createSuperAdmin = function ($data, $superAdminId = null) {
            $user = V4User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'           => $data['email'],
                    'role'            => $data['role'],
                    'password'   => Hash::make($data['password']),
                ]
            );

            $profile = SuperAdminProfile::firstOrNew(['v4_user_id' => $user->id]);
            $profile->super_admin_id = $superAdminId; // link to main super admin
            $profile->is_verified = true;
            $profile->save();

            $this->command->info("Processed SuperAdmin: {$user->email}");

            return $user;
        };

        // 1. Main Super Admin (no super_admin_id)
        $mainAdmin = $createSuperAdmin([
            'first_name' => 'Puck',
            'last_name'  => 'Recruiter',
            'email'      => 'main-admin@gmail.com',
            'role'       => 'super-admin',
            'password'   => 'password123',
        ]);

        // 2. admin connected to main
        $createSuperAdmin([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => 'admin@gmail.com',
            'role'       => 'super-admin',
            'password'   => 'password123',
        ], $mainAdmin->id);

        // 3. admin26 connected to main
        $createSuperAdmin([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => 'admin26@gmail.com',
            'role'       => 'super-admin',
            'password'   => 'password123',
        ], $mainAdmin->id);

        // 4. core-admin connected to main
        $createSuperAdmin([
            'first_name' => 'Co-Super',
            'last_name'  => 'Admin',
            'email'      => 'core-admin@gmail.com',
            'role'       => 'super-admin',
            'password'   => 'password123',
        ], $mainAdmin->id);
    }
}
