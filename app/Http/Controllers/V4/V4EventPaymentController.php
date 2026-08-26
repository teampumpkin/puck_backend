<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4Event;
use App\Services\Payments\EventPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class V4EventPaymentController extends Controller
{
    public function __construct(private EventPaymentService $service)
    {
    }

    public function initiatePayment(Request $request, V4Event $event): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            if ((int) $event->user_id !== (int) $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }
            $result = $this->service->initiate($event, $user);

            return response()->json($result['payload'], $result['http']);
        } catch (\Exception $e) {
            Log::error('Event initiate-payment failed', ['e' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to initiate payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    public function confirmPayment(Request $request, V4Event $event): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $result = $this->service->confirm($event, $user, $request->all());

            return response()->json($result['payload'], $result['http']);
        } catch (\Exception $e) {
            Log::error('Event confirm-payment failed', ['e' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to confirm payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    public function rejectPayment(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $result = $this->service->reject($event, $user, $request->input('reason'));

        return response()->json($result['payload'], $result['http']);
    }

    public function paymentStatus(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $parentId = (int) optional($event->paymentRequest)->parent_id;
        if ((int) $event->user_id !== (int) $user->id && $parentId !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        $result = $this->service->status($event);

        return response()->json($result['payload'], $result['http']);
    }

    public function parentPayment(Request $request, V4Event $event): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        $result = $this->service->parentPayment($event, $user);

        return response()->json($result['payload'], $result['http']);
    }
}
