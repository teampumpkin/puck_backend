<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\V4SuspendReason;

class V4SuspendReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $suspendReasons = [
            [
                'title'       => 'Underage Account',
                'description' => 'User is under the allowed age limit',
                'active'      => true,
            ],
            [
                'title'       => 'Incomplete Profile',
                'description' => 'User has not completed mandatory profile information',
                'active'      => true,
            ],
            [
                'title'       => 'Suspicious Activity',
                'description' => 'User activity flagged as suspicious',
                'active'      => true,
            ],
            [
                'title'       => 'Terms Violation',
                'description' => 'User temporarily suspended for violating terms of service',
                'active'      => true,
            ],
        ];

        foreach ($suspendReasons as $reason) {
            // Use title as unique key to prevent duplicates
            V4SuspendReason::updateOrCreate(
                ['title' => $reason['title']],
                $reason
            );
        }
    }
}
