<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\V4BanReason;

class V4BanReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banReasons = [
            [
                'title'       => 'Spam',
                'description' => 'User sent spam messages or advertisements',
                'active'      => true,
            ],
            [
                'title'       => 'Harassment',
                'description' => 'User engaged in harassment or abusive behavior',
                'active'      => true,
            ],
            [
                'title'       => 'Violation of Terms',
                'description' => 'User violated platform terms of service',
                'active'      => true,
            ],
            [
                'title'       => 'Multiple Accounts',
                'description' => 'User created multiple accounts to abuse the system',
                'active'      => true,
            ],
        ];

        foreach ($banReasons as $reason) {
            // Use title as unique key to prevent duplicates
            V4BanReason::updateOrCreate(
                ['title' => $reason['title']],
                $reason
            );
        }
    }
}
