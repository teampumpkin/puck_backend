<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4EventMember;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrowseEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): V4User
    {
        return V4User::forceCreate(['first_name' => 'U'.Str::random(4), 'email' => Str::random(8).'@t.io', 'role' => 'player']);
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    private function publishedEvent(V4User $owner, array $a = []): V4Event
    {
        return V4Event::create(array_merge([
            'user_id' => $owner->id, 'event_type' => 'ID Camp', 'name' => 'E'.Str::random(3), 'description' => 'd',
            'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'status' => V4Event::STATUS_PUBLISHED, 'published_at' => now(),
        ], $a));
    }

    public function test_browse_excludes_own_and_unpublished(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $this->publishedEvent($other);                                              // shown
        $this->publishedEvent($me);                                                 // hidden (own)
        $this->publishedEvent($other, ['status' => V4Event::STATUS_PENDING_PAYMENT]); // hidden (draft)

        $res = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events');
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_detail_reports_join_flags(): void
    {
        $me = $this->makeUser();
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);
        V4EventMember::create(['event_id' => $event->id, 'user_id' => $me->id, 'action' => 'join']);

        $res = $this->withHeaders($this->authAs($me))->getJson("/api/v4/events/{$event->id}");
        $res->assertStatus(200)
            ->assertJsonPath('data.is_joined', true)
            ->assertJsonPath('data.is_owner', false)
            ->assertJsonPath('data.joined_count', 1);
    }

    public function test_my_events_completed_includes_cancelled(): void
    {
        $me = $this->makeUser();
        $this->publishedEvent($me, ['status' => V4Event::STATUS_CANCELLED, 'cancelled_at' => now()]);

        $res = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events/my-events?status=completed');
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_same_day_event_is_ongoing_not_completed(): void
    {
        // Date-only picker stores end_at at midnight; an event ending "today" must
        // stay ongoing all day, not flip to completed once now() passes midnight.
        $me = $this->makeUser();
        $other = $this->makeUser();
        $this->publishedEvent($other, ['start_at' => today(), 'end_at' => today()]);

        $ongoing = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events?tab=ongoing');
        $ongoing->assertStatus(200);
        $this->assertCount(1, $ongoing->json('data'), 'same-day event should be ongoing');

        $completed = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events?tab=completed');
        $completed->assertStatus(200);
        $this->assertCount(0, $completed->json('data'), 'same-day event must not be completed');
    }

    public function test_my_events_same_day_is_ongoing(): void
    {
        $me = $this->makeUser();
        $this->publishedEvent($me, ['start_at' => today(), 'end_at' => today()]);

        $ongoing = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events/my-events?status=ongoing');
        $ongoing->assertStatus(200);
        $this->assertCount(1, $ongoing->json('data'));

        $completed = $this->withHeaders($this->authAs($me))->getJson('/api/v4/events/my-events?status=completed');
        $completed->assertStatus(200);
        $this->assertCount(0, $completed->json('data'));
    }
}
