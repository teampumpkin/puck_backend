<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): V4User
    {
        return V4User::forceCreate(['first_name' => 'A', 'email' => Str::random(6).'@t.io', 'role' => 'admin']);
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    private function makeEvent(array $a = []): V4Event
    {
        $owner = V4User::forceCreate(['first_name' => 'O', 'email' => Str::random(6).'@t.io', 'role' => 'player']);

        return V4Event::create(array_merge([
            'user_id' => $owner->id, 'event_type' => 'ID Camp', 'name' => 'E', 'description' => 'd',
            'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'status' => V4Event::STATUS_PUBLISHED,
        ], $a));
    }

    public function test_admin_list_includes_deleted_and_can_restore(): void
    {
        $admin = $this->admin();
        $event = $this->makeEvent();
        $event->delete();

        $this->withHeaders($this->authAs($admin))->getJson('/api/v4/admin/events?include_deleted=1')
            ->assertStatus(200);

        $this->withHeaders($this->authAs($admin))->postJson("/api/v4/admin/events/{$event->id}/restore")
            ->assertStatus(200);

        $this->assertNull($event->fresh()->deleted_at);
    }

    public function test_admin_stats_shape(): void
    {
        $admin = $this->admin();
        $this->makeEvent();
        $this->makeEvent(['status' => V4Event::STATUS_CANCELLED]);

        $this->withHeaders($this->authAs($admin))->getJson('/api/v4/admin/events/stats')
            ->assertStatus(200)
            ->assertJsonPath('data.published', 1)
            ->assertJsonPath('data.cancelled', 1);
    }
}
