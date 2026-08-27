<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyEventMembers;
use App\Models\V4Event;
use App\Models\V4EventMedia;
use App\Models\V4EventMember;
use App\Models\V4EventType;
use App\Models\V4User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class V4EventController extends Controller
{
    /** Active event type names (cached). Mobile fetches + caches this. */
    public function types(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => V4EventType::activeNames()]);
    }

    /**
     * Global platform-fee switch, so the create wizard can label its CTA
     * "Publish" (fee off) vs "Pay & Publish" (fee on) before submitting.
     */
    public function feeStatus(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['platform_fee_enabled' => \App\Services\Payments\EventPaymentService::feeEnabled()]]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $validated = $request->validate([
                'event_type' => ['required', 'string', Rule::in(V4EventType::activeNames())],
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'start_at' => 'required|date',
                'end_at' => 'required|date|after_or_equal:start_at',
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
                'coordinator_name' => 'nullable|string|min:2|max:100',
                'business_name' => 'nullable|string|min:2|max:150',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|regex:/^[0-9+\-()\s]{8,20}$/',
                'website_url' => 'nullable|url|max:255',
                'social_links' => 'nullable|array',
                'scout_leagues' => 'nullable|array',
                'positions' => 'nullable|array',
                'birth_years' => 'nullable|array',
                'league' => 'nullable|array',
                'league.*' => 'string',
                'team' => 'nullable|array',
                'team.*' => 'string',
                'media' => 'nullable|array|max:10',
                'media.*' => 'file|max:102400',
                'media_types' => 'nullable|array',
                'thumbnails' => 'nullable|array',
                'thumbnails.*' => 'nullable|image|max:10240',
            ]);

            DB::beginTransaction();
            $event = V4Event::create(array_merge(
                collect($validated)->except(['media', 'media_types', 'thumbnails'])->toArray(),
                ['user_id' => $user->id, 'status' => V4Event::STATUS_PENDING_PAYMENT]
            ));

            foreach ((array) $request->file('media', []) as $i => $file) {
                $type = $request->input("media_types.$i", 'image');
                $path = $file->store("events/{$event->id}", 's3');
                // Video poster uploaded by the client alongside the video (keyed by
                // the same index). Images render their own url, so no thumb needed.
                $thumbUrl = null;
                if ($type === 'video' && ($thumb = $request->file("thumbnails.$i"))) {
                    $thumbUrl = Storage::disk('s3')->url($thumb->store("events/{$event->id}/thumbs", 's3'));
                }
                V4EventMedia::create([
                    'event_id' => $event->id,
                    'media_type' => $type,
                    'url' => Storage::disk('s3')->url($path),
                    'thumbnail_url' => $thumbUrl,
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
            report($e);
            Log::error('Event create failed', ['e' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        // Browse is public (guests can view before signing up), so $user may be
        // null — only exclude the viewer's own events when logged in.
        $user = Auth::guard('v4api')->user();
        $q = V4Event::query()->published();
        if ($user) {
            $q->where('user_id', '!=', $user->id);
        }

        if ($s = $request->input('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'ilike', "%$s%")
                    ->orWhere('city', 'ilike', "%$s%")
                    ->orWhere('event_type', 'ilike', "%$s%");
            });
        }
        if ($t = $request->input('event_type')) {
            $q->where('event_type', $t);
        }
        foreach (['country', 'province', 'city'] as $f) {
            if ($v = $request->input($f)) {
                $q->where($f, $v);
            }
        }
        foreach (['scout_leagues', 'positions'] as $col) {
            $vals = (array) $request->input($col, []);
            if ($vals) {
                $q->where(function ($w) use ($col, $vals) {
                    foreach ($vals as $v) {
                        $w->orWhereJsonContains($col, $v);
                    }
                });
            }
        }
        if ($div = $request->input('age_division')) {
            $q->where('age_division', $div);
        }
        foreach (['league', 'team'] as $col) {
            $vals = (array) $request->input($col, []);
            if ($vals) {
                $q->where(function ($w) use ($col, $vals) {
                    foreach ($vals as $v) {
                        $w->orWhereJsonContains($col, $v);
                    }
                });
            }
        }
        // Age-range overlap: keep events whose [age_min, age_max] intersects the
        // selected range. A null event bound is open-ended (matches anything).
        if (($selMin = $request->input('age_min')) !== null) {
            $q->where(function ($w) use ($selMin) {
                $w->whereNull('age_max')->orWhere('age_max', '>=', (int) $selMin);
            });
        }
        if (($selMax = $request->input('age_max')) !== null) {
            $q->where(function ($w) use ($selMax) {
                $w->whereNull('age_min')->orWhere('age_min', '<=', (int) $selMax);
            });
        }
        $birthYears = (array) $request->input('birth_years', []);
        if ($birthYears) {
            $q->where(function ($w) use ($birthYears) {
                foreach ($birthYears as $y) {
                    $w->orWhereJsonContains('birth_years', (int) $y);
                }
            });
        }
        // Date window filters the event's start day (all-day → compare by date).
        if ($from = $request->input('date_from')) {
            $q->whereDate('start_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $q->whereDate('start_at', '<=', $to);
        }
        $tab = $request->input('tab', 'ongoing');
        // Events are all-day (date-only picker → end_at stored at midnight), so an
        // event ending "today" is still ongoing all day. Compare by date, not time,
        // else a same-day event reads as completed the moment now() passes midnight.
        $q->whereDate('end_at', $tab === 'completed' ? '<' : '>=', now()->toDateString());

        // Newest-created first; id tiebreaker keeps pagination stable when two
        // rows share a created_at.
        return $this->paginatedResponse($q->orderByDesc('created_at')->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    public function myEvents(Request $request): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $status = $request->input('status', 'ongoing');
        // Hide events still awaiting platform-fee payment (pending_payment /
        // payment_requested) from both tabs: My Events only lists live events.
        $q = V4Event::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [V4Event::STATUS_PENDING_PAYMENT, V4Event::STATUS_PAYMENT_REQUESTED]);
        if ($status === 'completed') {
            $q->where(function ($w) {
                $w->whereDate('end_at', '<', now()->toDateString())->orWhere('status', V4Event::STATUS_CANCELLED);
            });
        } else {
            $q->whereDate('end_at', '>=', now()->toDateString())->where('status', V4Event::STATUS_PUBLISHED);
        }

        // Whitelisted sort; default = newest created first. `id` tiebreaker keeps
        // pagination stable when two rows share a created_at.
        [$col, $dir] = match ($request->input('sort', 'created_desc')) {
            'created_asc' => ['created_at', 'asc'],
            default => ['created_at', 'desc'],
        };

        return $this->paginatedResponse(
            $q->orderBy($col, $dir)->orderByDesc('id')->paginate((int) $request->input('per_page', 15))
        );
    }

    public function show(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $data = $this->formatEvent($event->load('media'));
        $data['is_owner'] = $event->user_id === $user->id;
        $data['is_joined'] = $event->latestActionFor($user->id) === V4EventMember::ACTION_JOIN;
        $data['joined_count'] = $event->attendeeCount();

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function paginatedResponse($page): JsonResponse
    {
        // Stamp is_owner/is_joined on every list item (same as show()) so the
        // detail page, which paints the passed list model before its own fresh
        // fetch resolves, never flashes the wrong CTA (e.g. Apply on your own
        // event). formatEvent alone omits these viewer-relative flags.
        // ponytail: is_joined is one latestActionFor query per row (page size
        // ~15); batch by event if a page ever grows large.
        $userId = optional(Auth::guard('v4api')->user())->id;

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(function ($e) use ($userId) {
                $d = $this->formatEvent($e->load('media'));
                $d['is_owner'] = $userId !== null && $e->user_id === $userId;
                $d['is_joined'] = $userId !== null
                    && $e->latestActionFor($userId) === V4EventMember::ACTION_JOIN;

                return $d;
            })->all(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'has_more_pages' => $page->hasMorePages(),
            ],
        ]);
    }

    private function assertOwner(V4Event $event, $user): void
    {
        abort_if($event->user_id !== $user->id, 403, 'Forbidden.');
    }

    public function update(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $this->assertOwner($event, $user);
        try {
            $validated = $request->validate([
                'event_type' => ['sometimes', 'string', Rule::in(V4EventType::activeNames())],
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'start_at' => 'sometimes|date',
                'end_at' => 'sometimes|date|after_or_equal:start_at',
                'registration_deadline' => 'nullable|date|before_or_equal:start_at',
                'payment_deadline' => 'nullable|date|before_or_equal:start_at',
                'country' => 'sometimes|string',
                'province' => 'sometimes|string',
                'city' => 'sometimes|string',
                'venue' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'age_min' => 'nullable|integer',
                'age_max' => 'nullable|integer',
                'age_division' => 'nullable|string',
                'cost_person_cents' => 'nullable|integer|min:0',
                'special_qualification' => 'nullable|string',
                'coordinator_name' => 'nullable|string|min:2|max:100',
                'business_name' => 'nullable|string|min:2|max:150',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|regex:/^[0-9+\-()\s]{8,20}$/',
                'website_url' => 'nullable|url|max:255',
                'social_links' => 'nullable|array',
                'scout_leagues' => 'nullable|array',
                'positions' => 'nullable|array',
                'birth_years' => 'nullable|array',
                'league' => 'nullable|array',
                'league.*' => 'string',
                'team' => 'nullable|array',
                'team.*' => 'string',
                'add_media' => 'nullable|array',
                'add_media.*' => 'file|max:102400',
                'add_media_types' => 'nullable|array',
                'add_thumbnails' => 'nullable|array',
                'add_thumbnails.*' => 'nullable|image|max:10240',
                'remove_media_ids' => 'nullable|array',
            ]);

            $keep = $event->media()->whereNotIn('id', (array) $request->input('remove_media_ids', []))->count();
            $adding = count((array) $request->file('add_media', []));
            if ($keep + $adding > 10) {
                return response()->json(['success' => false, 'message' => 'Media limit is 10.'], 422);
            }

            DB::beginTransaction();
            $event->update(collect($validated)->except(['add_media', 'add_media_types', 'add_thumbnails', 'remove_media_ids'])->toArray());
            if ($ids = (array) $request->input('remove_media_ids', [])) {
                $event->media()->whereIn('id', $ids)->delete();
            }
            foreach ((array) $request->file('add_media', []) as $i => $file) {
                $type = $request->input("add_media_types.$i", 'image');
                $path = $file->store("events/{$event->id}", 's3');
                $thumbUrl = null;
                if ($type === 'video' && ($thumb = $request->file("add_thumbnails.$i"))) {
                    $thumbUrl = Storage::disk('s3')->url($thumb->store("events/{$event->id}/thumbs", 's3'));
                }
                V4EventMedia::create([
                    'event_id' => $event->id,
                    'media_type' => $type,
                    'url' => Storage::disk('s3')->url($path),
                    'thumbnail_url' => $thumbUrl,
                    'sort_order' => ((int) $event->media()->max('sort_order')) + 1,
                ]);
            }
            DB::commit();

            return response()->json(['success' => true, 'data' => $this->formatEvent($event->fresh('media'))]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Log::error('Event update failed', ['e' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update event.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    public function cancel(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $this->assertOwner($event, $user);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        $event->update([
            'status' => V4Event::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $validated['reason'],
        ]);
        NotifyEventMembers::dispatch($event->id, 'event_cancelled', $validated['reason']);

        return response()->json(['success' => true, 'message' => 'Event cancelled.']);
    }

    public function destroy(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $this->assertOwner($event, $user);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        $event->update(['delete_reason' => $validated['reason']]);
        NotifyEventMembers::dispatch($event->id, 'event_deleted', $validated['reason']);
        $event->delete();

        return response()->json(['success' => true, 'message' => 'Event deleted.']);
    }

    public function join(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        if ($event->user_id === $user->id) {
            return response()->json(['success' => false, 'message' => "You can't join your own event."], 403);
        }
        if ($event->status !== V4Event::STATUS_PUBLISHED) {
            return response()->json(['success' => false, 'message' => 'Event is not open to join.'], 409);
        }
        // All-day event: ended only once its end day is fully past (compare dates,
        // not the midnight instant), so a same-day event stays joinable all day.
        if ($event->end_at && $event->end_at->lt(now()->startOfDay())) {
            return response()->json(['success' => false, 'message' => 'Event has ended.'], 409);
        }
        // Deadline is date-only (midnight); registration stays open through the
        // whole deadline day, closed only once that day is past. No explicit
        // deadline → the end-day check above governs.
        if ($event->registration_deadline
            && $event->registration_deadline->lt(now()->startOfDay())) {
            return response()->json(['success' => false, 'message' => 'Registration is closed.'], 409);
        }

        $everJoined = $event->memberActions()->where('user_id', $user->id)
            ->where('action', V4EventMember::ACTION_JOIN)->exists();

        if ($event->latestActionFor($user->id) !== V4EventMember::ACTION_JOIN) {
            V4EventMember::create(['event_id' => $event->id, 'user_id' => $user->id, 'action' => V4EventMember::ACTION_JOIN]);
            if (! $everJoined) {
                try {
                    app(NotificationService::class)->sendToUserWithImage(
                        $event->creator,
                        'New RSVP',
                        ($user->first_name ?: 'Someone')." joined your event \"{$event->name}\".",
                        optional($event->media()->first())->url ?? '',
                        [],
                        'event_member_joined',
                        "/events/detail/{$event->id}"
                    );
                } catch (\Exception $e) {
                    Log::error('Event join notify failed', ['e' => $e->getMessage()]);
                }
            }
        }

        return response()->json(['success' => true, 'data' => [
            'member_state' => 'joined', 'is_joined' => true, 'joined_count' => $event->attendeeCount(),
        ]]);
    }

    public function leave(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        if ($event->status === V4Event::STATUS_CANCELLED) {
            return response()->json(['success' => false, 'message' => 'Event is cancelled.'], 409);
        }
        if ($event->latestActionFor($user->id) !== V4EventMember::ACTION_JOIN) {
            return response()->json(['success' => false, 'message' => 'You are not a member.'], 409);
        }
        V4EventMember::create(['event_id' => $event->id, 'user_id' => $user->id, 'action' => V4EventMember::ACTION_LEAVE]);

        return response()->json(['success' => true, 'data' => [
            'member_state' => 'left', 'is_joined' => false, 'joined_count' => $event->attendeeCount(),
        ]]);
    }

    public function members(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $isAdmin = ($user->role ?? null) === 'admin';
        if ($event->user_id !== $user->id && ! $isAdmin) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        $current = V4User::whereIn('id', $event->currentMemberIds())->get(['id', 'first_name', 'last_name', 'profile_photo']);
        $history = $event->memberActions()->with('user:id,first_name,last_name')
            ->orderByDesc('id')->get(['id', 'user_id', 'action', 'created_at']);

        return response()->json(['success' => true, 'data' => ['current' => $current, 'history' => $history]]);
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
            'league' => $e->league ?? [],
            'team' => $e->team ?? [],
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
