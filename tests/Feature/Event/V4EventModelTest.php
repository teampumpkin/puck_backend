<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class V4EventModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $a = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'U'.Str::random(4),
            'email' => Str::random(8).'@test.io',
            'role' => 'player',
        ], $a));
    }

    public function test_event_defaults_casts_and_soft_delete(): void
    {
        $user = $this->makeUser();
        $event = V4Event::create([
            'user_id' => $user->id,
            'event_type' => 'ID Camp',
            'name' => 'Camp at Ontario',
            'description' => 'desc',
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(6),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'scout_leagues' => ['NCAA', 'USHL'],
            'positions' => ['Forward'],
            'birth_years' => [2008, 2009],
        ]);

        $this->assertSame(V4Event::STATUS_PENDING_PAYMENT, $event->fresh()->status);
        $this->assertIsArray($event->fresh()->scout_leagues);
        $this->assertSame(['NCAA', 'USHL'], $event->fresh()->scout_leagues);
        $this->assertSame($user->id, $event->creator->id);

        $event->delete();
        $this->assertSoftDeleted('v4_events', ['id' => $event->id]);
    }
}
