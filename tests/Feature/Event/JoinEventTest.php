<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4EventMember;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JoinEventTest extends TestCase
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
            'user_id' => $owner->id, 'event_type' => 'ID Camp', 'name' => 'E', 'description' => 'd',
            'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'status' => V4Event::STATUS_PUBLISHED, 'published_at' => now(),
        ], $a));
    }

    public function test_owner_cannot_join_own_event(): void
    {
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);
        $this->withHeaders($this->authAs($owner))->postJson("/api/v4/events/{$event->id}/join")
            ->assertStatus(403);
    }

    public function test_cannot_join_ended_or_unpublished(): void
    {
        $me = $this->makeUser();
        $ended = $this->publishedEvent($this->makeUser(), ['start_at' => now()->subDays(3), 'end_at' => now()->subDay()]);
        $this->withHeaders($this->authAs($me))->postJson("/api/v4/events/{$ended->id}/join")->assertStatus(409);

        $draft = $this->publishedEvent($this->makeUser(), ['status' => V4Event::STATUS_PENDING_PAYMENT]);
        $this->withHeaders($this->authAs($me))->postJson("/api/v4/events/{$draft->id}/join")->assertStatus(409);
    }

    public function test_join_then_rejoin_is_idempotent_and_leave_works(): void
    {
        $me = $this->makeUser();
        $event = $this->publishedEvent($this->makeUser());
        $h = $this->authAs($me);

        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/join")
            ->assertStatus(200)->assertJsonPath('data.joined_count', 1)->assertJsonPath('data.is_joined', true);

        // rejoin (latest already join) -> still one join row, still count 1
        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/join")->assertJsonPath('data.joined_count', 1);
        $this->assertSame(1, V4EventMember::where('event_id', $event->id)->where('action', 'join')->count());

        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/leave")
            ->assertStatus(200)->assertJsonPath('data.joined_count', 0);
        $this->assertSame('leave', $event->latestActionFor($me->id));
    }

    public function test_can_join_same_day_event_with_today_deadline(): void
    {
        // All-day event happening today, registration deadline today: date-only
        // rules keep it joinable all day (was wrongly 409 once now() passed midnight).
        $me = $this->makeUser();
        $event = $this->publishedEvent($this->makeUser(), [
            'start_at' => today(), 'end_at' => today(), 'registration_deadline' => today(),
        ]);
        $this->withHeaders($this->authAs($me))->postJson("/api/v4/events/{$event->id}/join")
            ->assertStatus(200)->assertJsonPath('data.is_joined', true);
    }

    public function test_non_owner_cannot_view_members(): void
    {
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);
        $this->withHeaders($this->authAs($this->makeUser()))->getJson("/api/v4/events/{$event->id}/members")
            ->assertStatus(403);
        $this->withHeaders($this->authAs($owner))->getJson("/api/v4/events/{$event->id}/members")
            ->assertStatus(200);
    }
}
