<?php
// tests/Unit/ShareLinkServiceTest.php

namespace Tests\Unit;

use App\Models\V4PlayerPortfolio;
use App\Models\V4ShareLink;
use App\Models\V4ShareLinkLog;
use App\Models\EvaluationSubmission;
use App\Models\V4User;
use App\Services\ShareLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShareLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.share_link.base_url' => 'https://link.drafthouselabs.com']);
        $this->service = app(ShareLinkService::class);
    }

    private function makeUser(array $attrs = []): V4User
    {
        // ponytail: 'name' is a virtual accessor on V4User, not a DB column — omit from insert
        return V4User::forceCreate(array_merge([
            'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ], $attrs));
    }

    private function makePortfolio(V4User $owner, bool $public = true): V4PlayerPortfolio
    {
        // ponytail: brief used 'user_id' but the column is 'player_id' — matches ShareLinkModelTest pattern
        $submission = EvaluationSubmission::forceCreate(['player_id' => $owner->id]);
        return V4PlayerPortfolio::create([
            'player_id' => $owner->id, 'submission_id' => $submission->id,
            'title' => 'T', 'is_public' => $public,
        ]);
    }

    public function test_mint_creates_link_and_logs_created_plus_shared(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $result = $this->service->mint($portfolio, $owner);

        $this->assertTrue($result['was_created']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $result['token']);
        $this->assertSame(
            "https://link.drafthouselabs.com/s/{$result['token']}?r={$result['ref_code']}",
            $result['url']
        );
        $this->assertSame(
            ['created', 'shared'],
            V4ShareLinkLog::orderBy('id')->pluck('action')->all()
        );
    }

    public function test_second_mint_reuses_token_with_fresh_ref_code(): void
    {
        $owner = $this->makeUser();
        $sharer = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $first = $this->service->mint($portfolio, $owner);
        $second = $this->service->mint($portfolio, $sharer);

        $this->assertFalse($second['was_created']);
        $this->assertSame($first['token'], $second['token']);
        $this->assertNotSame($first['ref_code'], $second['ref_code']);
        $this->assertSame(2, V4ShareLinkLog::where('action', 'shared')->count());
        $this->assertSame(1, V4ShareLinkLog::where('action', 'created')->count());
    }

    public function test_revoke_then_mint_issues_new_token(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        $first = $this->service->mint($portfolio, $owner);
        $this->assertTrue($this->service->revoke($portfolio, $owner));
        $this->assertNull($this->service->resolve($first['token']));

        $second = $this->service->mint($portfolio, $owner);
        $this->assertTrue($second['was_created']);
        $this->assertNotSame($first['token'], $second['token']);
    }

    public function test_revoke_returns_false_when_no_active_link(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);

        // Never shared: revoke on a portfolio with no link returns false.
        $this->assertFalse($this->service->revoke($portfolio, $owner));

        // After a mint + revoke, a second revoke also returns false.
        $this->service->mint($portfolio, $owner);
        $this->assertTrue($this->service->revoke($portfolio, $owner));
        $this->assertFalse($this->service->revoke($portfolio, $owner));
    }

    public function test_resolve_returns_null_for_soft_deleted_portfolio(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $minted = $this->service->mint($portfolio, $owner);

        $portfolio->delete(); // soft delete

        $this->assertNull($this->service->resolve($minted['token']));
    }

    public function test_log_open_validates_ref_code_and_ignores_unknown_token(): void
    {
        $owner = $this->makeUser();
        $portfolio = $this->makePortfolio($owner);
        $minted = $this->service->mint($portfolio, $owner);

        $this->service->logOpen($minted['token'], $minted['ref_code'], null);
        $this->service->logOpen($minted['token'], '<script>', null);   // invalid r → logged without ref
        $this->service->logOpen(Str::random(32), 'AAAAAAAA', null);    // unknown token → no row

        $opens = V4ShareLinkLog::where('action', 'opened')->orderBy('id')->get();
        $this->assertCount(2, $opens);
        $this->assertSame($minted['ref_code'], $opens[0]->ref_code);
        $this->assertNull($opens[1]->ref_code);
    }

    public function test_permissions(): void
    {
        $owner = $this->makeUser();
        $parent = $this->makeUser(['role' => 'parent']);
        $owner->parent_id = $parent->id;
        $owner->save();
        $stranger = $this->makeUser();

        $private = $this->makePortfolio($owner, false);

        $this->assertTrue($this->service->canViewPortfolio($private, $owner));
        $this->assertFalse($this->service->canViewPortfolio($private, $stranger));

        $public = $this->makePortfolio($owner, true);
        $this->assertTrue($this->service->canViewPortfolio($public, $stranger));

        $this->assertTrue($this->service->canRevokePortfolio($private, $owner));
        $this->assertTrue($this->service->canRevokePortfolio($private, $parent));
        $this->assertFalse($this->service->canRevokePortfolio($private, $stranger));
    }
}
