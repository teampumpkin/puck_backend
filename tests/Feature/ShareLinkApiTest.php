<?php
// tests/Feature/ShareLinkApiTest.php

namespace Tests\Feature;

use App\Models\EvaluationSubmission;
use App\Models\V4PlayerPortfolio;
use App\Models\V4PlayerPortfolioSub;
use App\Models\V4ShareLink;
use App\Models\V4ShareLinkLog;
use App\Models\V4UploadedMedia;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ], $attrs));
    }

    private function makePortfolio(V4User $owner, bool $public = true): V4PlayerPortfolio
    {
        $submission = EvaluationSubmission::forceCreate(['player_id' => $owner->id]);
        return V4PlayerPortfolio::create([
            'player_id' => $owner->id, 'submission_id' => $submission->id,
            'title' => 'T', 'is_public' => $public,
        ]);
    }

    private function authAs(V4User $user): array
    {
        $token = auth('v4api')->login($user);
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_owner_mints_share_link(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $response = $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringContainsString('/s/', $response->json('data.url'));
    }

    public function test_stranger_cannot_share_private_portfolio(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $portfolio = $this->makePortfolio($owner, false);

        $this->withHeaders($this->authAs($stranger))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share")
            ->assertStatus(403);
    }

    public function test_only_owner_or_parent_can_revoke(): void
    {
        $parent = $this->makeUser(['role' => 'parent']);
        $owner = $this->makeUser();
        $owner->parent_id = $parent->id;
        $owner->save();
        $sharer = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $this->withHeaders($this->authAs($sharer))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share")->assertStatus(200);

        $this->withHeaders($this->authAs($sharer))
            ->deleteJson("/api/v4/portfolios/{$portfolio->id}/share")->assertStatus(403);

        $this->withHeaders($this->authAs($parent))
            ->deleteJson("/api/v4/portfolios/{$portfolio->id}/share")->assertStatus(200);
    }

    public function test_shared_token_resolves_private_portfolio_for_any_signed_in_user(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $portfolio = $this->makePortfolio($owner, false); // private!

        $mint = $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share")->json('data.url');
        parse_str(parse_url($mint, PHP_URL_QUERY), $query);
        $token = basename(parse_url($mint, PHP_URL_PATH));

        $response = $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}?r={$query['r']}");

        $response->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'portfolio')
            ->assertJsonPath('data.portfolio.id', $portfolio->id);

        $open = V4ShareLinkLog::where('action', 'opened')->first();
        $this->assertSame($query['r'], $open->ref_code);
        $this->assertSame($receiver->id, $open->user_id);
    }

    public function test_revoked_token_404s_without_detail(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");
        $token = V4ShareLink::first()->token;
        $this->withHeaders($this->authAs($owner))
            ->deleteJson("/api/v4/portfolios/{$portfolio->id}/share");

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(404);
    }

    public function test_public_open_endpoint_returns_204_for_valid_and_unknown_tokens(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");
        $token = V4ShareLink::first()->token;

        $this->postJson("/api/v4/share-links/{$token}/open", ['r' => 'AAAAbbbb'])->assertStatus(204);
        $this->postJson('/api/v4/share-links/' . Str::random(32) . '/open')->assertStatus(204);

        $this->assertSame(1, V4ShareLinkLog::where('action', 'opened')->count());
    }

    public function test_open_endpoint_skips_bot_user_agents(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share");
        $token = V4ShareLink::first()->token;

        $this->postJson("/api/v4/share-links/{$token}/open", [], ['User-Agent' => 'facebookexternalhit/1.1'])
            ->assertStatus(204);

        $this->assertSame(0, V4ShareLinkLog::where('action', 'opened')->count());
    }

    public function test_resolve_shared_returns_nested_video_keys(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $portfolio = $this->makePortfolio($owner, false);

        // Add a video sub to exercise build()'s media branch and verify nested key shape
        $media = V4UploadedMedia::forceCreate([
            'user_id' => $owner->id,
            'file_path' => 'uploads/test.mp4',
        ]);
        V4PlayerPortfolioSub::create([
            'portfolio_id' => $portfolio->id,
            'subable_id' => $media->id,
            'subable_type' => V4UploadedMedia::class,
        ]);

        $mint = $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/portfolios/{$portfolio->id}/share")->json('data.url');
        $token = basename(parse_url($mint, PHP_URL_PATH));

        $response = $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'portfolio')
            ->assertJsonPath('data.portfolio.id', $portfolio->id);

        $videos = $response->json('data.portfolio.videos');
        $this->assertNotEmpty($videos, 'videos must be non-empty when video subs exist');
        $this->assertArrayHasKey('id', $videos[0]);
        $this->assertArrayHasKey('file_path', $videos[0]);
    }
}
