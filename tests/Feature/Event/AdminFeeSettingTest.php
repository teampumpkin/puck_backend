<?php

namespace Tests\Feature\Event;

use App\Models\V4AppSetting;
use App\Models\V4User;
use App\Services\Payments\EventPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminFeeSettingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): V4User
    {
        return V4User::forceCreate(['first_name' => 'U'.Str::random(4), 'email' => Str::random(8).'@t.io', 'role' => $role]);
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    private const URL = '/api/v4/admin/events/platform-fee';

    public function test_admin_can_read_and_toggle_global_fee(): void
    {
        $admin = $this->makeUser('admin');

        $this->withHeaders($this->authAs($admin))->getJson(self::URL)
            ->assertStatus(200)->assertJsonPath('data.platform_fee_enabled', true); // default ON

        $this->withHeaders($this->authAs($admin))->putJson(self::URL, ['platform_fee_enabled' => false])
            ->assertStatus(200)->assertJsonPath('data.platform_fee_enabled', false);

        $this->assertFalse(EventPaymentService::feeEnabled());
        $this->withHeaders($this->authAs($admin))->getJson(self::URL)
            ->assertStatus(200)->assertJsonPath('data.platform_fee_enabled', false);
    }

    public function test_non_admin_cannot_read_or_change_global_fee(): void
    {
        $player = $this->makeUser('player');

        $this->withHeaders($this->authAs($player))->getJson(self::URL)->assertStatus(403);
        $this->withHeaders($this->authAs($player))->putJson(self::URL, ['platform_fee_enabled' => false])
            ->assertStatus(403);

        // The non-admin request must NOT have changed anything.
        $this->assertTrue(EventPaymentService::feeEnabled());
        $this->assertDatabaseCount('v4_app_settings', 0);
    }

    public function test_invalid_payload_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $this->withHeaders($this->authAs($admin))->putJson(self::URL, ['platform_fee_enabled' => 'banana'])
            ->assertStatus(422);
    }

    public function test_super_admin_is_allowed(): void
    {
        $super = $this->makeUser('super-admin');
        $this->withHeaders($this->authAs($super))->getJson(self::URL)->assertStatus(200);
        $this->withHeaders($this->authAs($super))->putJson(self::URL, ['platform_fee_enabled' => false])
            ->assertStatus(200)->assertJsonPath('data.platform_fee_enabled', false);
    }

    /** The fix is group-wide: a non-admin is blocked from OTHER admin event endpoints too. */
    public function test_non_admin_blocked_from_admin_group(): void
    {
        $player = $this->makeUser('player');
        $this->withHeaders($this->authAs($player))->getJson('/api/v4/admin/events/stats')->assertStatus(403);
        $this->withHeaders($this->authAs($player))->getJson('/api/v4/admin/events')->assertStatus(403);
    }
}
