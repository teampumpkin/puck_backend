<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\V4UserReportReason;
use Illuminate\Support\Str;

class V4UserReportReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $reasons = [
            [
                'reason' => 'Spam or Advertising',
                'slug' => Str::slug('Spam or Advertising'),
                'description' => 'The user is spamming or posting unsolicited advertisements.',
                'sort_order' => 1,
                'active' => true,
                'meta' => null
            ],
            [
                'reason' => 'Harassment or Bullying',
                'slug' => Str::slug('Harassment or Bullying'),
                'description' => 'The user is engaging in harassing or bullying behavior.',
                'sort_order' => 2,
                'active' => true,
                'meta' => null
            ],
            [
                'reason' => 'Inappropriate Content',
                'slug' => Str::slug('Inappropriate Content'),
                'description' => 'The user is posting inappropriate or offensive content.',
                'sort_order' => 3,
                'active' => true,
                'meta' => null
            ],
            [
                'reason' => 'Impersonation',
                'slug' => Str::slug('Impersonation'),
                'description' => 'The user is pretending to be someone else or a public figure.',
                'sort_order' => 4,
                'active' => true,
                'meta' => null
            ],
            [
                'reason' => 'Hate Speech',
                'slug' => Str::slug('Hate Speech'),
                'description' => 'The user is engaging in hate speech or discriminatory behavior.',
                'sort_order' => 5,
                'active' => true,
                'meta' => null
            ],
            [
                'reason' => 'Fraud or Scams',
                'slug' => Str::slug('Fraud or Scams'),
                'description' => 'The user is attempting fraudulent or scam activities.',
                'sort_order' => 6,
                'active' => true,
                'meta' => null
            ]
        ];

        foreach ($reasons as $reason) {
            V4UserReportReason::create([
                'reason' => $reason['reason'],
                'slug' => $reason['slug'],
                'description' => $reason['description'],
                'sort_order' => $reason['sort_order'],
                'active' => $reason['active'],
                'meta' => $reason['meta']
            ]);
        }
    }
}
