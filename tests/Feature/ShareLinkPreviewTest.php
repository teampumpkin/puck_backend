<?php

namespace Tests\Feature;

use App\Models\EvaluationSubmission;
use App\Models\V4PlayerPortfolio;
use App\Models\V4UploadedMedia;
use App\Models\V4User;
use App\Services\ShareLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkPreviewTest extends TestCase
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

    private function mintToken(V4PlayerPortfolio $portfolio, V4User $owner): string
    {
        $result = app(ShareLinkService::class)->mint($portfolio, $owner);
        return basename(parse_url($result['url'], PHP_URL_PATH));
    }

    public function test_preview_is_public_and_returns_allowlist_only(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $media = V4UploadedMedia::forceCreate(['user_id' => $owner->id, 'file_path' => 'videos/secret.mp4']);
        $portfolio->subs()->create(['subable_type' => V4UploadedMedia::class, 'subable_id' => $media->id]);
        $token = $this->mintToken($portfolio, $owner);

        // no Authorization header — endpoint must be public
        $res = $this->getJson("/api/v4/shared/{$token}/preview");

        $res->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'shareable_type',
                'player' => ['name', 'avatar_url'],
                'portfolio' => ['title', 'thumbnail_url'],
                'counts' => ['videos', 'evaluations', 'achievements'],
            ],
        ]);
        $this->assertSame(1, $res->json('data.counts.videos'));
        $this->assertSame('portfolio', $res->json('data.shareable_type'));

        // allowlist: raw payload must not leak content or identifiers
        $raw = $res->getContent();
        $this->assertStringNotContainsString('file_path', $raw);
        $this->assertStringNotContainsString('secret.mp4', $raw);
        $this->assertStringNotContainsString('player_id', $raw);
        $this->assertStringNotContainsString($owner->email, $raw);
    }

    public function test_preview_404s_identically_for_revoked_and_unknown(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $token = $this->mintToken($portfolio, $owner);
        app(ShareLinkService::class)->revoke($portfolio, $owner);

        $revoked = $this->getJson("/api/v4/shared/{$token}/preview");
        $unknown = $this->getJson('/api/v4/shared/' . Str::random(32) . '/preview');

        $revoked->assertStatus(404);
        $unknown->assertStatus(404);
        $this->assertSame($revoked->getContent(), $unknown->getContent());
    }
}
