<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyEventMembers;
use App\Models\V4Event;
use App\Models\V4User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class V4EventAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->boolean('include_deleted') ? V4Event::withTrashed() : V4Event::query();
        if ($s = $request->input('search')) {
            $q->where('name', 'ilike', "%$s%");
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
        return response()->json(['success' => true, 'data' => [
            'total' => V4Event::withTrashed()->count(),
            'published' => V4Event::where('status', V4Event::STATUS_PUBLISHED)->count(),
            'cancelled' => V4Event::where('status', V4Event::STATUS_CANCELLED)->count(),
            'deleted' => V4Event::onlyTrashed()->count(),
        ]]);
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
}
