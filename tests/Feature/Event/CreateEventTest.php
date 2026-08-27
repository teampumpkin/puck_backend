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

    protected function setUp(): void
    {
        parent::setUp();
        // store() validates event_type against active V4EventType names, so the
        // lookup must be seeded before hitting the create endpoint.
        $this->seed(\Database\Seeders\V4EventTypeSeeder::class);
    }

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

    public function test_create_event_stores_video_thumbnail(): void
    {
        Storage::fake('s3');
        $user = $this->makeUser();

        $res = $this->withHeaders($this->authAs($user))->postJson('/api/v4/events', [
            'event_type' => 'ID Camp',
            'name' => 'Camp with video',
            'description' => 'desc',
            'start_at' => now()->addDays(5)->toISOString(),
            'end_at' => now()->addDays(6)->toISOString(),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'media' => [UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')],
            'media_types' => ['video'],
            'thumbnails' => [UploadedFile::fake()->image('poster.jpg')],
        ]);

        $res->assertStatus(201);

        $media = V4Event::first()->media()->first();
        $this->assertSame('video', $media->media_type);
        $this->assertNotNull($media->thumbnail_url);
        $this->assertStringContainsString('/thumbs/', $media->thumbnail_url);
    }

    public function test_create_event_video_without_thumbnail_leaves_null(): void
    {
        Storage::fake('s3');
        $user = $this->makeUser();

        $this->withHeaders($this->authAs($user))->postJson('/api/v4/events', [
            'event_type' => 'ID Camp',
            'name' => 'Camp no poster',
            'description' => 'desc',
            'start_at' => now()->addDays(5)->toISOString(),
            'end_at' => now()->addDays(6)->toISOString(),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'media' => [UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')],
            'media_types' => ['video'],
        ])->assertStatus(201);

        $this->assertNull(V4Event::first()->media()->first()->thumbnail_url);
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
