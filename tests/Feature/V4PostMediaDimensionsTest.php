<?php

namespace Tests\Feature;

use App\Models\V4PostMedia;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class V4PostMediaDimensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    private function makeUser(): V4User
    {
        return V4User::forceCreate([
            'first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@test.io', 'role' => 'player',
        ]);
    }

    private function upload(V4User $user, array $media)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . auth('v4api')->login($user),
            'Accept'        => 'application/json',
        ])->post('/api/v4/posts/upload', ['media' => $media]);
    }

    public function test_client_dimensions_are_stored_in_meta_next_to_existing_keys(): void
    {
        $res = $this->upload($this->makeUser(), [[
            'type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 640, 480),
            'width' => 1080, 'height' => 1350,
        ]]);

        $res->assertStatus(201);
        $meta = V4PostMedia::firstOrFail()->meta;
        $this->assertSame(1080, $meta['width']);
        $this->assertSame(1350, $meta['height']);
        $this->assertNull($meta['duration_ms']);
        $this->assertArrayHasKey('storage_path', $meta);
        $this->assertArrayHasKey('original_name', $meta);
    }

    public function test_image_without_client_dimensions_falls_back_to_getimagesize(): void
    {
        $this->upload($this->makeUser(), [[
            'type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 640, 480),
        ]])->assertStatus(201);

        $meta = V4PostMedia::firstOrFail()->meta;
        $this->assertSame(640, $meta['width']);
        $this->assertSame(480, $meta['height']);
    }

    public function test_video_without_dimensions_stores_null_sizes_and_duration(): void
    {
        $this->upload($this->makeUser(), [[
            'type' => 'video', 'file' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4'),
            'duration_ms' => 5000,
        ]])->assertStatus(201);

        $meta = V4PostMedia::firstOrFail()->meta;
        $this->assertNull($meta['width']);
        $this->assertNull($meta['height']);
        $this->assertSame(5000, $meta['duration_ms']);
    }

    public function test_invalid_dimensions_are_rejected(): void
    {
        $user = $this->makeUser();
        foreach ([0, -5, 20000] as $bad) {
            $this->upload($user, [[
                'type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 640, 480),
                'width' => $bad, 'height' => 480,
            ]])->assertStatus(422);
        }
        $this->assertSame(0, V4PostMedia::count());
    }

    public function test_post_and_feed_responses_expose_dimensions_but_not_meta(): void
    {
        $user = $this->makeUser();
        $postId = $this->upload($user, [[
            'type' => 'image', 'file' => UploadedFile::fake()->image('p.jpg', 640, 480),
            'width' => 1080, 'height' => 1350,
        ]])->json('data.id');

        $headers = ['Authorization' => 'Bearer ' . auth('v4api')->login($user), 'Accept' => 'application/json'];

        $show = $this->withHeaders($headers)->get("/api/v4/posts/{$postId}");
        $show->assertStatus(200)
            ->assertJsonPath('data.media.0.width', 1080)
            ->assertJsonPath('data.media.0.height', 1350)
            ->assertJsonPath('data.media.0.duration_ms', null);
        $this->assertStringNotContainsString('storage_path', $show->getContent());

        $feed = $this->withHeaders($headers)->get("/api/v4/feeds/users/{$user->id}");
        $feed->assertStatus(200)->assertJsonPath('data.0.media.0.height', 1350);
        $this->assertStringNotContainsString('storage_path', $feed->getContent());
    }
}
