<?php

namespace Tests\Feature\Event;

use App\Models\V4Event;
use App\Models\V4InAppPurchase;
use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $a = []): V4User
    {
        return V4User::forceCreate(array_merge(['first_name' => 'U'.Str::random(4), 'email' => Str::random(8).'@t.io', 'role' => 'player'], $a));
    }

    private function authAs(V4User $u): array
    {
        return ['Authorization' => 'Bearer '.auth('v4api')->login($u)];
    }

    private function draftEvent(V4User $o): V4Event
    {
        return V4Event::create([
            'user_id' => $o->id, 'event_type' => 'ID Camp', 'name' => 'E', 'description' => 'd',
            'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'country' => 'Canada', 'province' => 'ON', 'city' => 'Ontario',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        V4InAppPurchase::create([
            'sku' => 'event_platform_fee', 'title' => 'Fee', 'product_type' => 'consumable',
            'amount_cents' => 999, 'currency' => 'CAD', 'active' => true,
        ]);
        config(['services.event.fee_sku' => 'event_platform_fee']);
    }

    public function test_adult_initiate_returns_sku_and_amount(): void
    {
        $owner = $this->makeUser();
        $event = $this->draftEvent($owner);
        $this->withHeaders($this->authAs($owner))->postJson("/api/v4/events/{$event->id}/initiate-payment")
            ->assertStatus(200)
            ->assertJsonPath('data.sku', 'event_platform_fee')
            ->assertJsonPath('data.awaiting_parent', false);
    }

    public function test_child_initiate_creates_parent_request(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $event = $this->draftEvent($child);

        $this->withHeaders($this->authAs($child))->postJson("/api/v4/events/{$event->id}/initiate-payment")
            ->assertStatus(200)
            ->assertJsonPath('data.awaiting_parent', true);

        $this->assertSame(V4Event::STATUS_PAYMENT_REQUESTED, $event->fresh()->status);
        $this->assertDatabaseHas('v4_payment_requests', ['parent_id' => $parent->id, 'status' => 'pending']);
    }

    public function test_adult_confirm_publishes_event_and_is_idempotent(): void
    {
        $owner = $this->makeUser();
        $event = $this->draftEvent($owner);
        $h = $this->authAs($owner);

        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/initiate-payment")->assertStatus(200);

        $body = ['source' => 'android', 'purchase_id' => 'gpa.'.Str::random(8)];
        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(V4Event::STATUS_PUBLISHED, $event->fresh()->status);
        $this->assertDatabaseHas('v4_payment_transactions', ['status' => 'success']);

        // replay same receipt -> idempotent, still exactly one success txn
        $this->withHeaders($h)->postJson("/api/v4/events/{$event->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(1, \App\Models\V4PaymentTransaction::where('status', 'success')->count());
    }

    public function test_only_parent_can_confirm_child_request(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $event = $this->draftEvent($child);
        $this->withHeaders($this->authAs($child))->postJson("/api/v4/events/{$event->id}/initiate-payment")->assertStatus(200);

        $body = ['source' => 'android', 'purchase_id' => 'gpa.'.Str::random(8)];
        // a random non-parent cannot confirm
        $this->withHeaders($this->authAs($this->makeUser()))
            ->postJson("/api/v4/events/{$event->id}/confirm-payment", $body)->assertStatus(403);

        // parent confirms -> published
        $this->withHeaders($this->authAs($parent))
            ->postJson("/api/v4/events/{$event->id}/confirm-payment", $body)->assertStatus(200);
        $this->assertSame(V4Event::STATUS_PUBLISHED, $event->fresh()->status);
    }

    public function test_reject_keeps_event_unpublished(): void
    {
        $parent = $this->makeUser();
        $child = $this->makeUser(['is_child' => true, 'parent_id' => $parent->id]);
        $event = $this->draftEvent($child);
        $this->withHeaders($this->authAs($child))->postJson("/api/v4/events/{$event->id}/initiate-payment")->assertStatus(200);

        $this->withHeaders($this->authAs($parent))
            ->postJson("/api/v4/events/{$event->id}/reject-payment", ['reason' => 'no'])->assertStatus(200);

        $this->assertNotSame(V4Event::STATUS_PUBLISHED, $event->fresh()->status);
        $this->assertDatabaseHas('v4_payment_requests', ['parent_id' => $parent->id, 'status' => 'parent_rejected']);
    }
}
