<?php
// tests/Feature/EventShareTest.php

namespace Tests\Feature;

use App\Models\V4Event;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventShareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ], $attrs));
    }

    private function makeEvent(V4User $owner, string $status = 'published', array $attrs = []): V4Event
    {
        return V4Event::forceCreate(array_merge([
            'user_id' => $owner->id,
            'event_type' => 'camp',
            'name' => 'Summer Camp',
            'description' => 'A great camp',
            'start_at' => now()->addDays(10),
            'end_at' => now()->addDays(11),
            'country' => 'CA', 'province' => 'ON', 'city' => 'Toronto',
            'venue' => '55 Rink Rd',
            'latitude' => 43.65, 'longitude' => -79.38,
            'cost_person_cents' => 5000, 'cost_person_currency' => 'CAD',
            'coordinator_name' => 'Jane Coord', 'business_name' => 'Puck Camps',
            'contact_email' => 'secret@camp.io', 'contact_phone' => '+15550000',
            'status' => $status,
        ], $attrs));
    }

    private function authAs(V4User $user): array
    {
        return ['Authorization' => 'Bearer ' . auth('v4api')->login($user)];
    }

    private function mintUrl(V4User $actor, V4Event $event): string
    {
        return $this->withHeaders($this->authAs($actor))
            ->postJson("/api/v4/events/{$event->id}/share")->json('data.url');
    }

    private function tokenFrom(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_owner_mints_event_share_link(): void
    {
        $owner = $this->makeUser();
        $event = $this->makeEvent($owner);

        $res = $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/events/{$event->id}/share");

        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringContainsString('/s/', $res->json('data.url'));
    }

    public function test_non_owner_can_share_published_event(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $event = $this->makeEvent($owner, 'published');

        $this->withHeaders($this->authAs($stranger))
            ->postJson("/api/v4/events/{$event->id}/share")->assertStatus(200);
    }

    public function test_non_owner_cannot_share_unpublished_event(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $event = $this->makeEvent($owner, 'pending_payment');

        $this->withHeaders($this->authAs($stranger))
            ->postJson("/api/v4/events/{$event->id}/share")->assertStatus(403);
    }

    public function test_resolve_published_event(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $event = $this->makeEvent($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $event));

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'event')
            ->assertJsonPath('data.event.id', $event->id);
    }

    public function test_resolve_cancelled_event_blocked(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $event = $this->makeEvent($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $event));
        $event->update(['status' => 'cancelled']);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(403)
            ->assertJsonPath('reason', 'event_cancelled');
    }

    public function test_resolve_pending_event_blocked(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $event = $this->makeEvent($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $event));
        $event->update(['status' => 'payment_requested']);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(403)
            ->assertJsonPath('reason', 'event_unavailable');
    }

    public function test_owner_bypasses_block_on_own_cancelled_event(): void
    {
        $owner = $this->makeUser();
        $event = $this->makeEvent($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $event));
        $event->update(['status' => 'cancelled']);

        $this->withHeaders($this->authAs($owner))
            ->getJson("/api/v4/shared/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'event');
    }

    public function test_deleted_event_token_404s(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $event = $this->makeEvent($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $event));
        $event->delete(); // soft delete

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")->assertStatus(404);
    }

    public function test_preview_published_event_excludes_pii(): void
    {
        $owner = $this->makeUser();
        $event = $this->makeEvent($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $event));

        $res = $this->getJson("/api/v4/shared/{$token}/preview");
        $res->assertStatus(200)
            ->assertJsonPath('data.shareable_type', 'event')
            ->assertJsonPath('data.event.name', 'Summer Camp')
            ->assertJsonPath('data.organizer.name', 'Puck Camps');

        $data = $res->json('data');
        $this->assertArrayNotHasKey('contact_email', $data['event']);
        $this->assertArrayNotHasKey('latitude', $data['event']);
        $this->assertArrayNotHasKey('venue', $data['event']);
        $this->assertStringNotContainsString('secret@camp.io', json_encode($data), 'contact email must not leak in teaser');
        $this->assertStringNotContainsString('55 Rink Rd', json_encode($data), 'exact venue must not leak in teaser');
    }

    public function test_preview_cancelled_event_blocked(): void
    {
        $owner = $this->makeUser();
        $event = $this->makeEvent($owner, 'published');
        $token = $this->tokenFrom($this->mintUrl($owner, $event));
        $event->update(['status' => 'cancelled']);

        $this->getJson("/api/v4/shared/{$token}/preview")
            ->assertStatus(403)->assertJsonPath('reason', 'event_cancelled');
    }

    public function test_owner_revoke_then_token_404s(): void
    {
        $owner = $this->makeUser();
        $receiver = $this->makeUser();
        $event = $this->makeEvent($owner);
        $token = $this->tokenFrom($this->mintUrl($owner, $event));

        $this->withHeaders($this->authAs($owner))
            ->deleteJson("/api/v4/events/{$event->id}/share")->assertStatus(200);

        $this->withHeaders($this->authAs($receiver))
            ->getJson("/api/v4/shared/{$token}")->assertStatus(404);
    }

    public function test_non_owner_cannot_revoke_event_share(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $event = $this->makeEvent($owner);
        $this->mintUrl($owner, $event);

        $this->withHeaders($this->authAs($stranger))
            ->deleteJson("/api/v4/events/{$event->id}/share")->assertStatus(403);
    }
}
