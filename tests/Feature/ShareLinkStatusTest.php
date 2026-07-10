<?php
// tests/Feature/ShareLinkStatusTest.php

namespace Tests\Feature;

use App\Contracts\ErrorTrackerInterface;
use App\Models\V4PlayerPortfolio;
use App\Models\V4User;
use App\Services\LogErrorTracker;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(ErrorTrackerInterface::class, LogErrorTracker::class);
        $this->mock(NotificationService::class);
    }

    private function makeUser(array $attrs = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ], $attrs));
    }

    private function makePortfolio(V4User $owner, array $attrs = []): V4PlayerPortfolio
    {
        $submission = \App\Models\EvaluationSubmission::forceCreate(['player_id' => $owner->id]);
        return V4PlayerPortfolio::create(array_merge([
            'player_id' => $owner->id, 'submission_id' => $submission->id,
            'title' => 'T', 'is_public' => true,
        ], $attrs));
    }

    private function authAs(V4User $user): array
    {
        $token = auth('v4api')->login($user);
        return ['Authorization' => "Bearer {$token}"];
    }

    /** Finding 2 + 5: stranger gets no key; owner sees null before share, block after */
    public function test_owner_sees_share_link_block_but_stranger_does_not(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        // Finding 5: owner sees key present with null value BEFORE any share
        $noShareView = $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $this->assertArrayHasKey('share_link', $noShareView->json('data'));
        $this->assertNull($noShareView->json('data.share_link'));

        // Create the share link as owner
        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        // Owner sees the active block
        $ownerView = $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $ownerView->assertJsonPath('data.share_link.active', true);
        $this->assertNotNull($ownerView->json('data.share_link.shared_at'));

        // Finding 2: stranger gets key ABSENT entirely (not null)
        $strangerView = $this->withHeaders($this->authAs($stranger))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $this->assertArrayNotHasKey('share_link', $strangerView->json('data'));
    }

    /** Finding 3: parent user sees the share_link block */
    public function test_parent_user_sees_share_link_block(): void
    {
        $parent = $this->makeUser();
        $player = $this->makeUser(['parent_id' => $parent->id]);
        $portfolio = $this->makePortfolio($player);

        // Create share as player/owner
        $this->withHeaders($this->authAs($player))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        $parentView = $this->withHeaders($this->authAs($parent))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $this->assertArrayHasKey('share_link', $parentView->json('data'));
        $parentView->assertJsonPath('data.share_link.active', true);
    }

    /** Finding 4: shared_by reflects the most recent 'shared' log actor */
    public function test_shared_by_reflects_latest_shared_log_actor(): void
    {
        $owner = $this->makeUser(['first_name' => 'Owner']);
        $second = $this->makeUser(['first_name' => 'Second']);
        // Portfolio must be public so second user can share it
        $portfolio = $this->makePortfolio($owner, ['is_public' => true]);

        // Owner creates the link first
        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        // Second user (who can view public portfolio) calls share — logs a 'shared' row for second user
        // Revoke first so second can mint a fresh link
        $this->withHeaders($this->authAs($owner))
            ->deleteJson("/api/v4/portfolios/{$portfolio->id}/share");

        $this->withHeaders($this->authAs($second))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        // Owner views portfolio — shared_by should be second user's name (latest 'shared' log)
        $ownerView = $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");

        $this->assertEquals($second->name, $ownerView->json('data.share_link.shared_by'));
    }
}
