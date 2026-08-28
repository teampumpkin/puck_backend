<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyEventMembers;
use App\Models\V4Event;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use App\Services\Payments\EventPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class V4EventAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('deleted_only')) {
            $q = V4Event::onlyTrashed();
        } elseif ($request->boolean('include_deleted')) {
            $q = V4Event::withTrashed();
        } else {
            $q = V4Event::query();
        }
        if ($s = $request->input('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'ilike', "%$s%")
                    ->orWhere('city', 'ilike', "%$s%")
                    ->orWhere('province', 'ilike', "%$s%")
                    ->orWhere('event_type', 'ilike', "%$s%");
            });
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        $page = $q->orderByDesc('id')->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (V4Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'event_type' => $e->event_type,
                'status' => $e->status,
                'city' => $e->city,
                'province' => $e->province,
                'start_at' => $e->start_at,
                'end_at' => $e->end_at,
                'deleted_at' => $e->deleted_at,
                'joined_count' => $e->attendeeCount(),
            ])->all(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'has_more_pages' => $page->hasMorePages(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        [$revenueCents, , $revenueFormatted, $paidEvents] = $this->revenueByPurpose('event');

        return response()->json(['success' => true, 'data' => [
            'total' => V4Event::withTrashed()->count(),
            'published' => V4Event::where('status', V4Event::STATUS_PUBLISHED)->count(),
            'cancelled' => V4Event::where('status', V4Event::STATUS_CANCELLED)->count(),
            'deleted' => V4Event::onlyTrashed()->count(),
            'paid_events' => $paidEvents,
            'total_revenue_cents' => $revenueCents,
            'total_revenue_formatted' => $revenueFormatted,
        ]]);
    }

    /**
     * Total revenue = successful transactions whose payment request had the given
     * meta.purpose ('event' / 'hockey_listing'), summed per currency. Purpose-scoping
     * is sku-agnostic (survives fee-sku renames/variants — a single-sku filter silently
     * undercounts) and never mixes domains. Currencies are summed separately so mixed
     * currencies are not added as one unit. Returns [dominantCents, dominantCurrency, formatted].
     */
    private function revenueByPurpose(string $purpose): array
    {
        $base = fn () => V4PaymentTransaction::query()
            ->from('v4_payment_transactions as t')
            ->join('v4_payment_requests as r', 'r.id', '=', 't.payment_request_id')
            ->where('t.status', V4PaymentTransaction::STATUS_SUCCESS)
            ->whereRaw("r.meta->>'purpose' = ?", [$purpose]);

        $paidCount = $base()->count();
        $byCurrency = $base()
            ->groupBy('t.currency')
            ->selectRaw('UPPER(t.currency) as currency, SUM(t.amount_cents) as cents')
            ->pluck('cents', 'currency');

        if ($byCurrency->isEmpty()) {
            return [0, 'USD', 'USD 0.00', 0];
        }

        $dominant = $byCurrency->sortDesc()->keys()->first();
        $formatted = $byCurrency
            ->map(fn ($cents, $cur) => $cur . ' ' . number_format(((int) $cents) / 100, 2))
            ->values()->implode(' + ');

        return [(int) $byCurrency[$dominant], $dominant, $formatted, $paidCount];
    }

    public function show(int $id): JsonResponse
    {
        $event = V4Event::withTrashed()->with('media')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $event]);
    }

    public function members(int $id): JsonResponse
    {
        $event = V4Event::withTrashed()->findOrFail($id);

        return response()->json(['success' => true, 'data' => [
            'current' => V4User::whereIn('id', $event->currentMemberIds())->get(['id', 'first_name', 'last_name', 'email']),
            'history' => $event->memberActions()->orderByDesc('id')->get(['id', 'user_id', 'action', 'created_at']),
        ]]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $event = V4Event::withTrashed()->findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'event_type' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'registration_deadline' => 'sometimes|nullable|date|before_or_equal:start_at',
            'country' => 'sometimes|nullable|string|max:255',
            'province' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'venue' => 'sometimes|nullable|string|max:255',
            'cost_person_cents' => 'sometimes|nullable|integer|min:0',
            'age_min' => 'sometimes|nullable|integer|min:0',
            'age_max' => 'sometimes|nullable|integer|min:0|gte:age_min',
        ]);
        $event->update($validated);

        return response()->json(['success' => true, 'data' => $event->fresh()]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $event = V4Event::findOrFail($id);
        $reason = $request->validate(['reason' => 'required|string|max:1000'])['reason'];
        $event->update(['status' => V4Event::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
        NotifyEventMembers::dispatch($event->id, 'event_cancelled', $reason);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $event = V4Event::findOrFail($id);
        $reason = $request->input('reason', 'Removed by admin');
        $event->update(['delete_reason' => $reason]);
        NotifyEventMembers::dispatch($event->id, 'event_deleted', $reason);
        $event->delete();

        return response()->json(['success' => true]);
    }

    public function restore(int $id): JsonResponse
    {
        $event = V4Event::onlyTrashed()->findOrFail($id);
        $event->restore();

        return response()->json(['success' => true]);
    }

    /** Global events platform-fee switch. Admin-gated by the route group's `admin` middleware. */
    public function getFeeSetting(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['platform_fee_enabled' => EventPaymentService::feeEnabled()]]);
    }

    public function setFeeSetting(Request $request): JsonResponse
    {
        $enabled = $request->validate(['platform_fee_enabled' => 'required|boolean'])['platform_fee_enabled'];
        EventPaymentService::setFeeEnabled($enabled);

        return response()->json(['success' => true, 'data' => ['platform_fee_enabled' => EventPaymentService::feeEnabled()]]);
    }
}
