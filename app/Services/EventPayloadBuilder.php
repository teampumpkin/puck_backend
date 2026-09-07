<?php

namespace App\Services;

use App\Http\Controllers\V4\V4EventController;
use App\Models\V4Event;
use App\Models\V4EventMember;
use App\Models\V4User;

class EventPayloadBuilder
{
    /**
     * Full authed shared view. Reuses V4EventController::formatEvent so the payload stays
     * byte-identical to what the app's EventModel.fromJson already consumes (via getEvent),
     * plus the viewer-relative flags show() stamps so the correct Apply/Joined CTA paints.
     */
    public function buildFull(V4Event $event, ?V4User $user): array
    {
        $data = app(V4EventController::class)->formatEvent($event->load('media'));
        $data['is_owner'] = $user !== null && $event->user_id === $user->id;
        $data['is_joined'] = $user !== null
            && $event->latestActionFor($user->id) === V4EventMember::ACTION_JOIN;
        $data['joined_count'] = $event->attendeeCount();

        return $data;
    }

    /**
     * Anonymous web teaser — explicit allowlist. Never contact PII, exact venue, or lat/long.
     * Only ever reached for published events (blockReason gates cancelled/pending upstream).
     */
    public function buildPreview(V4Event $event): array
    {
        $banner = $event->media->first(); // media() relation is ordered by sort_order

        return [
            'organizer' => [
                'name' => $event->business_name ?: $event->coordinator_name,
            ],
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'event_type' => $event->event_type,
                'banner_url' => $banner->thumbnail_url ?? $banner->url ?? null,
                'start_at' => $event->start_at,
                'end_at' => $event->end_at,
                'city' => $event->city,
                'province' => $event->province,
                'country' => $event->country,
                'cost_person_cents' => $event->cost_person_cents,
                'cost_person_currency' => $event->cost_person_currency,
            ],
        ];
    }
}
