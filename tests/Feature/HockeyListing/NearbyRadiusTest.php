<?php

namespace Tests\Feature\HockeyListing;

use App\Constants\HockeyListingCategories;
use App\Constants\HockeyListingConditions;
use App\Models\V4HockeyListing;
use App\Models\V4User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NearbyRadiusTest extends TestCase
{
    use RefreshDatabase;

    /** Toronto City Hall — the buyer's position for every test below. */
    private const BUYER_LAT = 43.6532;
    private const BUYER_LNG = -79.3832;

    protected function setUp(): void
    {
        parent::setUp();

        // V4HockeyListingController constructor-injects NotificationService,
        // which reaches Firebase via PushNotificationHelper. The service-account
        // JSON is absent in test, so without this every route returns 500.
        $this->instance(
            NotificationService::class,
            \Mockery::mock(NotificationService::class)->shouldIgnoreMissing()
        );
    }

    private function makeUser(): V4User
    {
        return V4User::forceCreate([
            'first_name' => 'U' . Str::random(4),
            'email' => Str::random(8) . '@t.io',
            'role' => 'player',
        ]);
    }

    /**
     * A published listing offset due north of the buyer by roughly $milesNorth.
     * 69.0 miles per degree of latitude is the same approximation the controller
     * uses for its bounding-box prefilter, so test and implementation agree.
     */
    private function listingMilesAway(
        V4User $owner,
        float $milesNorth,
        int $sellRadius,
        string $name = 'Bauer Vapor'
    ): V4HockeyListing {
        return $this->listingAtPoint(
            $owner,
            self::BUYER_LAT + ($milesNorth / 69.0),
            self::BUYER_LNG,
            $sellRadius,
            $name
        );
    }

    private function listingAtPoint(
        V4User $owner,
        float $lat,
        float $lng,
        int $sellRadius = 500,
        string $name = 'Bauer Vapor'
    ): V4HockeyListing {
        return V4HockeyListing::create([
            'user_id' => $owner->id,
            'name' => $name,
            'description' => 'test listing',
            'price_cents' => 5000,
            'currency' => 'CAD',
            'category' => HockeyListingCategories::PLAYER_STICKS,
            'condition' => HockeyListingConditions::USED_GOOD,
            'latitude' => $lat,
            'longitude' => $lng,
            'city' => 'Toronto',
            'country' => 'Canada',
            'sell_radius' => $sellRadius,
            'status' => V4HockeyListing::STATUS_PUBLISHED,
            'listed_at' => now(),
        ]);
    }

    private function nearby(array $params = [])
    {
        return $this->getJson('/api/v4/hockey-listings/nearby?' . http_build_query(array_merge([
            'latitude' => self::BUYER_LAT,
            'longitude' => self::BUYER_LNG,
            'radius' => 25,
        ], $params)));
    }

    public function test_radius_is_required(): void
    {
        $res = $this->getJson('/api/v4/hockey-listings/nearby?' . http_build_query([
            'latitude' => self::BUYER_LAT,
            'longitude' => self::BUYER_LNG,
        ]));

        $res->assertStatus(422);
        $this->assertArrayHasKey('radius', $res->json('errors'));
    }

    public function test_radius_below_one_is_rejected(): void
    {
        $this->nearby(['radius' => 0])->assertStatus(422);
    }

    public function test_radius_above_five_hundred_is_rejected(): void
    {
        $this->nearby(['radius' => 501])->assertStatus(422);
    }

    /**
     * The inversion, stated as a test. Before this change the seller's
     * sell_radius of 1 mile hid this listing from a buyer 5 miles away.
     * Now the buyer's own 10-mile radius decides, so it is returned.
     */
    public function test_seller_radius_no_longer_hides_a_listing_inside_the_buyer_radius(): void
    {
        $seller = $this->makeUser();
        $listing = $this->listingMilesAway($seller, 5, 1);

        $res = $this->nearby(['radius' => 10]);

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertSame($listing->id, $res->json('data.0.id'));
    }

    public function test_listing_outside_the_buyer_radius_is_excluded_and_reappears_when_widened(): void
    {
        $seller = $this->makeUser();
        $this->listingMilesAway($seller, 50, 500);

        $this->assertCount(0, $this->nearby(['radius' => 10])->json('data'));
        $this->assertCount(1, $this->nearby(['radius' => 100])->json('data'));
    }

    public function test_distance_miles_is_present_and_ascending(): void
    {
        $seller = $this->makeUser();
        $this->listingMilesAway($seller, 20, 5, 'Far stick');
        $this->listingMilesAway($seller, 2, 5, 'Near stick');

        $data = $this->nearby(['radius' => 50])->json('data');

        $this->assertCount(2, $data);
        $this->assertArrayHasKey('distance_miles', $data[0]);
        $this->assertLessThan($data[1]['distance_miles'], $data[0]['distance_miles']);
    }

    /**
     * The bounding box is only a cheap prefilter; it has to stay a strict
     * superset of the haversine that follows it. This listing sits 499.8 miles
     * from the buyer — inside a 500-mile radius — but near the circle's eastern
     * tangent, where an unpadded box is ~0.7% too narrow and drops it before the
     * haversine ever runs. Delete the 1.02 margin in nearby() and this fails.
     */
    public function test_listing_near_the_east_tangent_is_not_clipped_by_the_bounding_box(): void
    {
        $seller = $this->makeUser();
        $listing = $this->listingAtPoint($seller, 43.882092, self::BUYER_LNG + 10.018);

        $data = $this->nearby(['radius' => 500])->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($listing->id, $data[0]['id']);
        $this->assertLessThan(500, $data[0]['distance_miles']);
    }
}
