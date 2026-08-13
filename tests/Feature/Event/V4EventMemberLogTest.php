<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4EventMember;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class V4EventMemberLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): V4User
    {
        return V4User::forceCreate([
            'first_name' => 'U'.Str::random(4),
            'email' => Str::random(8).'@t.io',
            'role' => 'player',
        ]);
    }

    private function makeEvent(V4User $owner): V4Event
    {
        return V4Event::create([
            'user_id' => $owner->id,
            'event_type' => 'ID Camp',
            'name' => 'E',
            'description' => 'd',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
        ]);
    }

    public function test_latest_action_determines_membership(): void
    {
        $owner = $this->makeUser();
        $a = $this->makeUser();
        $b = $this->makeUser();
        $event = $this->makeEvent($owner);

        V4EventMember::create(['event_id' => $event->id, 'user_id' => $a->id, 'action' => 'join']);
        V4EventMember::create(['event_id' => $event->id, 'user_id' => $b->id, 'action' => 'join']);
        V4EventMember::create(['event_id' => $event->id, 'user_id' => $a->id, 'action' => 'leave']);

        $this->assertSame([$b->id], array_values($event->currentMemberIds()));
        $this->assertSame(1, $event->attendeeCount());
        $this->assertSame('leave', $event->latestActionFor($a->id));
        $this->assertSame('join', $event->latestActionFor($b->id));
    }
}
