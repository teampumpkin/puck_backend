<?php
// tests/Feature/HockeyListingShareTest.php

namespace Tests\Feature;

use App\Models\V4HockeyListing;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HockeyListingShareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'Sel', 'last_name' => 'Ler',
            'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ], $attrs));
    }

    private function makeListing(V4User $owner, string $status = 'published', array $attrs = []): V4HockeyListing
    {
        return V4HockeyListing::forceCreate(array_merge([
            'user_id' => $owner->id,
            'name' => 'Bauer Stick',
            'description' => 'Barely used',
            'price_cents' => 12000, 'currency' => 'CAD',
            'category' => 'player_sticks', 'condition' => 'like_new',
            'latitude' => 43.65, 'longitude' => -79.38, 'address' => '123 Secret St',
            'postal_code' => 'M5V2T6',
            'city' => 'Toronto', 'state' => 'ON', 'country' => 'CA',
            'status' => $status,
        ], $attrs));
    }

    private function authAs(V4User $user): array
    {
        return ['Authorization' => 'Bearer ' . auth('v4api')->login($user)];
    }

    private function mintUrl(V4User $actor, V4HockeyListing $listing): string
    {
        return $this->withHeaders($this->authAs($actor))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/share")->json('data.url');
    }

    private function tokenFrom(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_owner_mints_listing_share_link(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $res = $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/share");

        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringContainsString('/s/', $res->json('data.url'));
    }

    public function test_non_owner_can_share_published_listing(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $listing = $this->makeListing($owner, 'published');

        $this->withHeaders($this->authAs($stranger))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/share")->assertStatus(200);
    }

    public function test_non_owner_cannot_share_draft_listing(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $listing = $this->makeListing($owner, 'draft');

        $this->withHeaders($this->authAs($stranger))
            ->postJson("/api/v4/hockey-listings/{$listing->id}/share")->assertStatus(403);
    }

    public function test_resolve_published_listing_excludes_location(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $listing = $this->makeListing($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));

        $res = $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}");

        $res->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'hockey_listing')
            ->assertJsonPath('data.hockey_listing.id', $listing->id);

        $block = $res->json('data.hockey_listing');
        $this->assertArrayNotHasKey('latitude', $block);
        $this->assertArrayNotHasKey('longitude', $block);
        $this->assertArrayNotHasKey('address', $block);
        $this->assertArrayNotHasKey('postal_code', $block);
        $this->assertStringNotContainsString('123 Secret St', json_encode($res->json('data')), 'address must not leak');
        $this->assertNotNull($block['user'], 'seller present');
    }

    public function test_resolve_sold_listing_blocked(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $listing = $this->makeListing($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));
        $listing->update(['status' => 'sold']);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(403)
            ->assertJsonPath('reason', 'listing_sold');
    }

    public function test_resolve_draft_listing_blocked(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $listing = $this->makeListing($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));
        $listing->update(['status' => 'draft']);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(403)
            ->assertJsonPath('reason', 'listing_unavailable');
    }

    public function test_deleted_listing_token_404s(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $listing = $this->makeListing($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));
        $listing->delete(); // soft delete

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")->assertStatus(404);
    }

    public function test_preview_published_listing_excludes_location(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));

        $res = $this->getJson("/api/v4/shared/{$token}/preview");
        $res->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'hockey_listing')
            ->assertJsonPath('data.listing.name', 'Bauer Stick')
            ->assertJsonPath('data.seller.name', 'Sel Ler');

        $data = $res->json('data');
        $this->assertArrayNotHasKey('latitude', $data['listing']);
        $this->assertArrayNotHasKey('address', $data['listing']);
        $this->assertArrayNotHasKey('description', $data['listing']);
        $this->assertStringNotContainsString('123 Secret St', json_encode($data), 'address must not leak in teaser');
    }

    public function test_preview_sold_listing_blocked(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));
        $listing->update(['status' => 'sold']);

        $this->getJson("/api/v4/shared/{$token}/preview")
            ->assertStatus(403)->assertJsonPath('reason', 'listing_sold');
    }

    public function test_owner_revoke_then_token_404s(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $listing = $this->makeListing($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $listing));

        $this->withHeaders($this->authAs($owner))
            ->deleteJson("/api/v4/hockey-listings/{$listing->id}/share")->assertStatus(200);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")->assertStatus(404);
    }

    public function test_non_owner_cannot_revoke_listing_share(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $listing = $this->makeListing($owner);
        $this->mintUrl($owner, $listing);

        $this->withHeaders($this->authAs($stranger))
            ->deleteJson("/api/v4/hockey-listings/{$listing->id}/share")->assertStatus(403);
    }
}
