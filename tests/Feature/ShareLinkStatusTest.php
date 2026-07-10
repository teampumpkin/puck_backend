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

    private function makePortfolio(V4User $owner): V4PlayerPortfolio
    {
        $submission = \App\Models\EvaluationSubmission::forceCreate(['player_id' => $owner->id]);
        return V4PlayerPortfolio::create([
            'player_id' => $owner->id, 'submission_id' => $submission->id,
            'title' => 'T', 'is_public' => true,
        ]);
    }

    private function authAs(V4User $user): array
    {
        $token = auth('v4api')->login($user);
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_owner_sees_share_link_block_but_stranger_does_not(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        $ownerView = $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $ownerView->assertJsonPath('data.share_link.active', true);
        $this->assertNotNull($ownerView->json('data.share_link.shared_at'));

        $strangerView = $this->withHeaders($this->authAs($stranger))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");
        $this->assertNull($strangerView->json('data.share_link'));
    }
}
