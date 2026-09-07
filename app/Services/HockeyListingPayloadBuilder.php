<?php

namespace App\Services;

use App\DTOs\SellerInfoDTO;
use App\Models\V4HockeyListing;

class HockeyListingPayloadBuilder
{
    /**
     * Full authed shared view — explicit allowlist matching MyListingModel.fromJson.
     * NEVER latitude/longitude/address/postal_code/payment_request_id (privacy: a listing's
     * precise location is the seller's home). Allowlist, not toArray()-minus-fields.
     */
    public function buildFull(V4HockeyListing $listing): array
    {
        $listing->loadMissing(['images', 'user']);
        $seller = $listing->user ? SellerInfoDTO::fromUser($listing->user)->toArray() : null;

        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'status' => $listing->status,
            'price_cents' => $listing->price_cents,
            'currency' => $listing->currency,
            'category' => $listing->category,
            'condition' => $listing->condition,
            'description' => $listing->description,
            'city' => $listing->city,
            'state' => $listing->state,
            'country' => $listing->country,
            'listed_at' => $listing->listed_at,
            'images' => $listing->images->map(fn ($img) => ['url' => $img->image_url])->all(),
            'user' => $seller,
        ];
    }

    /**
     * Anonymous web teaser — first image, coarse location, seller name + photo only.
     * Only ever reached for published listings (blockReason gates sold/draft upstream).
     */
    public function buildPreview(V4HockeyListing $listing): array
    {
        $listing->loadMissing(['images', 'user']);
        $seller = $listing->user ? SellerInfoDTO::fromUser($listing->user) : null;
        $firstImage = $listing->images->first(); // images() relation ordered by sort_order

        return [
            'listing' => [
                'name' => $listing->name,
                'image_url' => $firstImage->image_url ?? null,
                'price_cents' => $listing->price_cents,
                'currency' => $listing->currency,
                'category' => $listing->category,
                'city' => $listing->city,
                'state' => $listing->state,
                'country' => $listing->country,
            ],
            'seller' => [
                'name' => $seller->name ?? null,
                'profile_photo' => $seller->profile_photo ?? null,
            ],
        ];
    }
}
