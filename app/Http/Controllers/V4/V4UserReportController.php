<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4UserReport;
use App\Models\V4UserReportReason;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4UserReportController extends Controller
{

    public function reportUser(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'reported_user_id' => 'required|exists:v4_users,id',
                'reason_id'        => 'required|integer|exists:v4_user_report_reasons,id',
                'message'          => 'nullable|string|max:1000',
            ]);

            $reportedUserId = $validated['reported_user_id'];
            $reasonId       = $validated['reason_id'];
            $message        = $validated['message'] ?? '';

            $rejectionReason = V4UserReportReason::findOrFail($reasonId);
            if (! $rejectionReason->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected reason is no longer active.',
                ], 400);
            }

            $reportUser = V4UserReport::create([
                'reported_user_id' => $reportedUserId,
                'reported_by'      => $user->id,
                'reason_id'        => $reasonId,
                'message'          => $message,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User reported successfully',
                'data'    => $reportUser,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reason or user not found',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to report user', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to report user',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
