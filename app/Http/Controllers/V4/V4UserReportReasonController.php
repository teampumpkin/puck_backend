<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4UserReportReason;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4UserReportReasonController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }


    public function getActiveReasons(Request $request): JsonResponse
    {
        try {
            $activeReasons = V4UserReportReason::active()->get();

            return response()->json([
                'success' => true,
                'message' => 'Active user report reasons retrieved successfully',
                'data'    => $activeReasons,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function getAllReasons(Request $request): JsonResponse
    {
        try {
            $allReasons = V4UserReportReason::orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'message' => 'All user report reasons retrieved successfully',
                'data'    => $allReasons,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason'      => 'required|string|max:255|unique:v4_user_report_reasons,reason',
                'description' => 'required|string',
                'meta'        => 'nullable|array',
                'meta.*'      => 'string',
            ]);

            $highestSortOrder = V4UserReportReason::max('sort_order') ?? 0;
            $nextSortOrder    = $highestSortOrder + 1;

            // Handling 'meta' field, if provided
            $meta = $validated['meta'] ?? null;

            // Create new report reason
            $rejectionReason = V4UserReportReason::create([
                'reason'      => $validated['reason'],
                'slug'        => Str::slug($validated['reason']),
                'description' => $validated['description'],
                'active'      => true,
                'sort_order'  => $nextSortOrder,
                'meta'        => $meta,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User report reason created successfully',
                'data'    => $rejectionReason,
            ], 201);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id'          => 'required|integer|exists:v4_user_report_reasons,id',
                'reason'      => 'sometimes|required|string|max:255|unique:v4_user_report_reasons,reason',
                'description' => 'sometimes|required|string',
                'active'      => 'sometimes|required|boolean',
                'sort_order'  => 'sometimes|required|integer|min:1',
                'meta'        => 'sometimes|nullable|array',
                'meta.*'      => 'string',
            ]);

            $rejectionReason = V4UserReportReason::findOrFail($validated['id']);

            $updateData         = $validated;
            $updateData['slug'] = Str::slug($validated['reason'] ?? $rejectionReason->reason); // Always update slug with reason change.

            $rejectionReason->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User report reason updated successfully',
                'data'    => $rejectionReason,
            ]);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User report reason not found',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:v4_user_report_reasons,id',
            ]);

            $rejectionReason = V4UserReportReason::findOrFail($validated['id']);
            $rejectionReason->delete();

            return response()->json([
                'success' => true,
                'message' => 'User report reason deleted successfully',
            ]);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User report reason not found',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function getRejectionReason(int $id): JsonResponse
    {
        try {
            $rejectionReason = V4UserReportReason::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'User report reason retrieved successfully',
                'data'    => $rejectionReason,
            ]);
        } catch (ModelNotFoundException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User report reason not found',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException(Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred',
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
