<?php
// tests/Feature/PortfolioPayloadRegressionTest.php

namespace Tests\Feature;

use App\Contracts\ErrorTrackerInterface;
use App\Models\EvaluationSubmission;
use App\Models\V4PlayerPortfolio;
use App\Models\V4User;
use App\Services\LogErrorTracker;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortfolioPayloadRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Swap real error tracker (Sentry) for the log-based one — no external deps
        $this->app->bind(ErrorTrackerInterface::class, LogErrorTracker::class);

        // Mock NotificationService so the Firebase service account file is never touched
        $this->mock(NotificationService::class);
    }

    private function authAs(V4User $user): array
    {
        $token = auth('v4api')->login($user);
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeOwnerWithPortfolio(): array
    {
        $owner = V4User::forceCreate([
            'email' => Str::random(8) . '@test.io',
            'role' => 'player',
        ]);
        $submission = EvaluationSubmission::forceCreate([
            'player_id' => $owner->id,
        ]);
        $portfolio = V4PlayerPortfolio::create([
            'player_id' => $owner->id,
            'submission_id' => $submission->id,
            'title' => 'Regression Portfolio',
            'description' => 'Test description',
            'is_public' => true,
        ]);
        return [$owner, $portfolio];
    }

    public function test_get_hockey_portfolio_payload_keys_unchanged(): void
    {
        [$owner, $portfolio] = $this->makeOwnerWithPortfolio();

        $response = $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertIsArray($data, 'data must be an array');

        $expectedKeys = [
            'id', 'player_id', 'player_name', 'title', 'description', 'meta',
            'thumbnail_path', 'is_public', 'created_at', 'updated_at',
            'evaluations', 'achievements', 'videos',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Payload is missing key: {$key}");
        }

        // Pin types for critical fields
        $this->assertIsInt($data['id'], 'id must be int');
        $this->assertIsInt($data['player_id'], 'player_id must be int');
        $this->assertIsBool($data['is_public'], 'is_public must be bool');
        $this->assertIsArray($data['evaluations'], 'evaluations must be array');
        $this->assertIsArray($data['achievements'], 'achievements must be array');
        $this->assertIsArray($data['videos'], 'videos must be array');

        // Pin exact values from the created portfolio
        $this->assertSame($portfolio->id, $data['id']);
        $this->assertSame($owner->id, $data['player_id']);
        $this->assertSame('Regression Portfolio', $data['title']);
        $this->assertTrue($data['is_public']);
    }

    public function test_get_hockey_portfolio_returns_403_for_non_owner_of_private_portfolio(): void
    {
        [$owner, $portfolio] = $this->makeOwnerWithPortfolio();

        // Make portfolio private
        $portfolio->update(['is_public' => false]);

        $other = V4User::forceCreate([
            'email' => Str::random(8) . '@test.io',
            'role' => 'player',
        ]);

        $response = $this->withHeaders($this->authAs($other))
            ->getJson("/api/v4/evaluation/get-hockey-portfolio/{$portfolio->id}");

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
    }

    public function test_get_hockey_portfolio_returns_404_for_missing_portfolio(): void
    {
        $owner = V4User::forceCreate([
            'email' => Str::random(8) . '@test.io',
            'role' => 'player',
        ]);

        $response = $this->withHeaders($this->authAs($owner))
            ->getJson('/api/v4/evaluation/get-hockey-portfolio/99999999');

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }
}
