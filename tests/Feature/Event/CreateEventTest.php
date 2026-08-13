<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $a = []): V4User
    {
        return V4User::forceCreate(array_merge([
            'first_name' => 'U'.Str::random(4),
            'email' => Str::random(8).'@t.io',
            'role' => 'player',
        ], $a));
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    public function test_create_event_saves_pending_with_media(): void
    {
        Storage::fake('s3');
        $user = $this->makeUser();

        $res = $this->withHeaders($this->authAs($user))->postJson('/api/v4/events', [
            'event_type' => 'ID Camp',
            'name' => 'Camp at Ontario',
            'description' => 'desc',
            'start_at' => now()->addDays(5)->toISOString(),
            'end_at' => now()->addDays(6)->toISOString(),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'latitude' => 43.65, 'longitude' => -79.38,
            'scout_leagues' => ['NCAA'],
            'media' => [UploadedFile::fake()->image('a.jpg')],
            'media_types' => ['image'],
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', V4Event::STATUS_PENDING_PAYMENT);

        $event = V4Event::first();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame(1, $event->media()->count());
    }

    public function test_create_rejects_bad_dates(): void
    {
        $user = $this->makeUser();
        $res = $this->withHeaders($this->authAs($user))->postJson('/api/v4/events', [
            'event_type' => 'ID Camp', 'name' => 'X', 'description' => 'd',
            'start_at' => now()->addDays(6)->toISOString(),
            'end_at' => now()->addDays(5)->toISOString(), // end before start
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
        ]);
        $res->assertStatus(422);
    }
}
