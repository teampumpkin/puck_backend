<?php
// tests/Unit/ShareLinkModelTest.php

namespace Tests\Unit;

use App\Models\V4PlayerPortfolio;
use App\Models\V4ShareLink;
use App\Models\V4User;
use App\Models\EvaluationSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShareLinkModelTest extends TestCase
{
    use RefreshDatabase;

    private function makePortfolio(): V4PlayerPortfolio
    {
        $user = V4User::forceCreate([
            'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ]);
        $submission = EvaluationSubmission::forceCreate([
            'player_id' => $user->id,
        ]);
        return V4PlayerPortfolio::create([
            'player_id' => $user->id, 'submission_id' => $submission->id,
            'title' => 'Test', 'is_public' => true,
        ]);
    }

    public function test_share_link_stores_portfolio_alias_not_fqcn(): void
    {
        $portfolio = $this->makePortfolio();
        $link = V4ShareLink::create([
            'token' => Str::random(32),
            'shareable_type' => $portfolio->getMorphClass(),
            'shareable_id' => $portfolio->id,
            'created_by' => $portfolio->player_id,
        ]);
        $this->assertSame('portfolio', $link->fresh()->shareable_type);
        $this->assertTrue($link->shareable->is($portfolio));
    }

    public function test_partial_unique_index_allows_second_active_link_only_after_revoke(): void
    {
        $portfolio = $this->makePortfolio();
        $base = ['shareable_type' => 'portfolio', 'shareable_id' => $portfolio->id, 'created_by' => $portfolio->player_id];
        V4ShareLink::create($base + ['token' => Str::random(32)]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        V4ShareLink::create($base + ['token' => Str::random(32)]);
    }

    public function test_revoked_link_frees_the_partial_unique_index(): void
    {
        $portfolio = $this->makePortfolio();
        $base = ['shareable_type' => 'portfolio', 'shareable_id' => $portfolio->id, 'created_by' => $portfolio->player_id];
        $first = V4ShareLink::create($base + ['token' => Str::random(32)]);
        $first->update(['revoked_at' => now(), 'revoked_by' => $portfolio->player_id]);

        $second = V4ShareLink::create($base + ['token' => Str::random(32)]);
        $this->assertNotNull($second->id);
        $this->assertCount(1, V4ShareLink::active()->where('shareable_id', $portfolio->id)->get());
    }
}
