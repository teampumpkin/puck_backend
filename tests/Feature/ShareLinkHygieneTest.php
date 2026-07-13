<?php
// tests/Feature/ShareLinkHygieneTest.php

namespace Tests\Feature;

use App\Models\APILog;
use App\Models\EvaluationSubmission;
use App\Models\V4ShareLinkLog;
use App\Models\V4ShareLink;
use App\Models\V4PlayerPortfolio;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkHygieneTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): V4User
    {
        return V4User::forceCreate([
            'first_name' => 'U' . Str::random(4),
            'email' => Str::random(8) . '@test.io',
            'role' => 'player',
        ]);
    }

    private function makePortfolio(V4User $owner): V4PlayerPortfolio
    {
        $submission = EvaluationSubmission::forceCreate(['player_id' => $owner->id]);
        return V4PlayerPortfolio::create([
            'player_id' => $owner->id, 'submission_id' => $submission->id,
            'title' => 'T', 'is_public' => true,
        ]);
    }

    public function test_share_routes_not_written_to_api_logs(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $jwt = auth('v4api')->login($owner);

        $this->withHeaders(['Authorization' => "Bearer {$jwt}"])
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share")->assertStatus(200);
        $token = V4ShareLink::first()->token;
        $this->withHeaders(['Authorization' => "Bearer {$jwt}"])
            ->getJson("/api/v4/shared/{$token}")->assertStatus(200);
        // third path: public open endpoint (no auth)
        $this->postJson("/api/v4/share-links/{$token}/open")->assertStatus(204);

        $this->assertSame(0, APILog::where('url', 'like', '%share%')->count());
        $this->assertSame(0, APILog::where('url', 'like', "%{$token}%")->count());
    }

    public function test_prune_command_deletes_only_old_opened_rows(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $link = V4ShareLink::create([
            'token' => Str::random(32), 'shareable_type' => 'portfolio',
            'shareable_id' => $portfolio->id, 'created_by' => $owner->id,
        ]);

        V4ShareLinkLog::create(['share_link_id' => $link->id, 'user_id' => $owner->id,
            'action' => 'created', 'created_at' => now()->subYears(6)]);
        V4ShareLinkLog::create(['share_link_id' => $link->id, 'user_id' => null,
            'action' => 'opened', 'created_at' => now()->subYears(6)]);
        V4ShareLinkLog::create(['share_link_id' => $link->id, 'user_id' => null,
            'action' => 'opened', 'created_at' => now()->subMonths(2)]);

        $this->artisan('share-links:prune-open-logs')->assertExitCode(0);

        $this->assertSame(0, V4ShareLinkLog::where('action', 'opened')
            ->where('created_at', '<', now()->subYears(5))->count());
        $this->assertSame(1, V4ShareLinkLog::where('action', 'opened')->count());
        $this->assertSame(1, V4ShareLinkLog::where('action', 'created')->count()); // audit rows kept

        // Soft-delete, not hard: the pruned row is trashed, still recoverable
        $this->assertSame(1, V4ShareLinkLog::onlyTrashed()->where('action', 'opened')->count());
        $this->assertSame(2, V4ShareLinkLog::withTrashed()->where('action', 'opened')->count());
    }
}
