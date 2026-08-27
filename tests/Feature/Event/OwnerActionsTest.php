<?php

namespace Tests\Feature\Event;

use App\Jobs\NotifyEventMembers;
use App\Models\V4Event;
use App\Models\V4EventMedia;
use App\Models\V4EventMember;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerActionsTest extends TestCase
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

    private function publishedEvent(V4User $o): V4Event
    {
        return V4Event::create([
            'user_id' => $o->id, 'event_type' => 'ID Camp', 'name' => 'E', 'description' => 'd',
            'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
            'status' => V4Event::STATUS_PUBLISHED, 'published_at' => now(),
        ]);
    }

    public function test_non_owner_cannot_cancel(): void
    {
        $event = $this->publishedEvent($this->makeUser());
        $this->withHeaders($this->authAs($this->makeUser()))
            ->postJson("/api/v4/events/{$event->id}/cancel", ['reason' => 'x'])
            ->assertStatus(403);
    }

    public function test_cancel_sets_reason_and_notifies_members(): void
    {
        Queue::fake();
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);
        V4EventMember::create(['event_id' => $event->id, 'user_id' => $this->makeUser()->id, 'action' => 'join']);

        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/events/{$event->id}/cancel", ['reason' => 'Rink closed'])
            ->assertStatus(200);

        $this->assertSame(V4Event::STATUS_CANCELLED, $event->fresh()->status);
        $this->assertSame('Rink closed', $event->fresh()->cancel_reason);
        Queue::assertPushed(NotifyEventMembers::class);
    }

    public function test_delete_soft_deletes_and_notifies(): void
    {
        Queue::fake();
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);

        $this->withHeaders($this->authAs($owner))
            ->json('DELETE', "/api/v4/events/{$event->id}", ['reason' => 'No longer running'])
            ->assertStatus(200);

        $this->assertSoftDeleted('v4_events', ['id' => $event->id]);
        $this->assertSame('No longer running', $event->fresh()->delete_reason);
        Queue::assertPushed(NotifyEventMembers::class);
    }

    public function test_edit_media_cap_enforced(): void
    {
        Storage::fake('s3');
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);
        for ($i = 0; $i < 10; $i++) {
            V4EventMedia::create(['event_id' => $event->id, 'media_type' => 'image', 'url' => "u$i", 'sort_order' => $i]);
        }
        // add one more with no removals -> 11 > 10 -> 422
        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/events/{$event->id}", [
                '_method' => 'PUT',
                'add_media' => [UploadedFile::fake()->image('x.jpg')],
                'add_media_types' => ['image'],
            ])->assertStatus(422);
    }

    public function test_edit_stores_added_video_thumbnail(): void
    {
        Storage::fake('s3');
        $owner = $this->makeUser();
        $event = $this->publishedEvent($owner);

        $this->withHeaders($this->authAs($owner))
            ->postJson("/api/v4/events/{$event->id}", [
                '_method' => 'PUT',
                'add_media' => [UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')],
                'add_media_types' => ['video'],
                'add_thumbnails' => [UploadedFile::fake()->image('poster.jpg')],
            ])->assertStatus(200);

        $media = $event->media()->where('media_type', 'video')->first();
        $this->assertNotNull($media);
        $this->assertNotNull($media->thumbnail_url);
        $this->assertStringContainsString('/thumbs/', $media->thumbnail_url);
    }
}
