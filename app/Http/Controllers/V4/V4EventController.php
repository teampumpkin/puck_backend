<?php

namespace App\Http\Controllers\V4;

use App\Constants\EventTypes;
use App\Http\Controllers\Controller;
use App\Models\V4Event;
use App\Models\V4EventMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class V4EventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $validated = $request->validate([
                'event_type' => 'required|string|in:'.implode(',', EventTypes::all()),
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'start_at' => 'required|date',
                'end_at' => 'required|date|after:start_at',
                'registration_deadline' => 'nullable|date|before_or_equal:start_at',
                'payment_deadline' => 'nullable|date|before_or_equal:start_at',
                'country' => 'required|string',
                'province' => 'required|string',
                'city' => 'required|string',
                'venue' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'age_min' => 'nullable|integer',
                'age_max' => 'nullable|integer',
                'age_division' => 'nullable|string',
                'cost_person_cents' => 'nullable|integer|min:0',
                'special_qualification' => 'nullable|string',
                'coordinator_name' => 'nullable|string',
                'business_name' => 'nullable|string',
                'contact_email' => 'nullable|email',
                'contact_phone' => 'nullable|string',
                'website_url' => 'nullable|string',
                'social_links' => 'nullable|array',
                'scout_leagues' => 'nullable|array',
                'positions' => 'nullable|array',
                'birth_years' => 'nullable|array',
                'league' => 'nullable|string',
                'team' => 'nullable|string',
                'media' => 'nullable|array|max:10',
                'media.*' => 'file|max:102400',
                'media_types' => 'nullable|array',
            ]);

            DB::beginTransaction();
            $event = V4Event::create(array_merge(
                collect($validated)->except(['media', 'media_types'])->toArray(),
                ['user_id' => $user->id, 'status' => V4Event::STATUS_PENDING_PAYMENT]
            ));

            foreach ((array) $request->file('media', []) as $i => $file) {
                $type = $request->input("media_types.$i", 'image');
                $path = $file->store("events/{$event->id}", 's3');
                V4EventMedia::create([
                    'event_id' => $event->id,
                    'media_type' => $type,
                    'url' => Storage::disk('s3')->url($path),
                    'sort_order' => $i,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event saved as draft.',
                'data' => $this->formatEvent($event->fresh('media')),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event create failed', ['e' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function formatEvent(V4Event $e): array
    {
        return [
            'id' => $e->id,
            'event_type' => $e->event_type,
            'name' => $e->name,
            'description' => $e->description,
            'status' => $e->status,
            'start_at' => $e->start_at,
            'end_at' => $e->end_at,
            'registration_deadline' => $e->registration_deadline,
            'payment_deadline' => $e->payment_deadline,
            'country' => $e->country,
            'province' => $e->province,
            'city' => $e->city,
            'venue' => $e->venue,
            'latitude' => $e->latitude,
            'longitude' => $e->longitude,
            'age_min' => $e->age_min,
            'age_max' => $e->age_max,
            'age_division' => $e->age_division,
            'cost_person_cents' => $e->cost_person_cents,
            'cost_person_currency' => $e->cost_person_currency,
            'scout_leagues' => $e->scout_leagues ?? [],
            'positions' => $e->positions ?? [],
            'birth_years' => $e->birth_years ?? [],
            'league' => $e->league,
            'team' => $e->team,
            'special_qualification' => $e->special_qualification,
            'coordinator_name' => $e->coordinator_name,
            'business_name' => $e->business_name,
            'contact_email' => $e->contact_email,
            'contact_phone' => $e->contact_phone,
            'website_url' => $e->website_url,
            'social_links' => $e->social_links ?? [],
            'media' => $e->media->map(fn ($m) => [
                'id' => $m->id,
                'media_type' => $m->media_type,
                'url' => $m->url,
                'thumbnail_url' => $m->thumbnail_url,
                'sort_order' => $m->sort_order,
            ])->all(),
        ];
    }
}
