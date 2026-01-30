<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\EvaluationRejectionReason;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

/**
 * Class EvaluationRejectionReasonController
 * @package App\Http\Controllers\API
 */
class EvaluationRejectionReasonController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    /**
     * @OA\Get(
     * path="/evaluation-rejection-reasons/active",
     * summary="Get Active Evaluation Rejection Reasons",
     * description="Retrieve all active rejection reasons that evaluators can select",
     * operationId="getActiveEvaluationRejectionReasons",
     * tags={"Evaluation"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="Active rejection reasons retrieved successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Active rejection reasons retrieved successfully"),
     *       @OA\Property(property="data", type="array",
     *          @OA\Items(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="title", type="string", example="Insufficient Skill Level"),
     *              @OA\Property(property="description", type="string", example="Player does not demonstrate the required skill level"),
     *              @OA\Property(property="active", type="boolean", example=true),
     *              @OA\Property(property="sort_order", type="integer", example=1),
     *              @OA\Property(property="meta", type="object", example={"severity": "high", "category": "technical"})
     *          )
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=500,
     *    description="Internal server error",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Something went wrong")
     *    )
     * )
     * )
     */
    public function getActiveReasons(Request $request): JsonResponse
    {
        try {
            $activeReasons = EvaluationRejectionReason::active()->get();

            return response()->json([
                'success' => true,
                'message' => 'Active rejection reasons retrieved successfully',
                'data' => $activeReasons
            ]);
        } catch (Exception $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/evaluation-rejection-reasons",
     * summary="Get All Evaluation Rejection Reasons",
     * description="Retrieve all rejection reasons (admin only)",
     * operationId="getAllEvaluationRejectionReasons",
     * tags={"Evaluation"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="All rejection reasons retrieved successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="All rejection reasons retrieved successfully"),
     *       @OA\Property(property="data", type="array",
     *          @OA\Items(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="title", type="string", example="Insufficient Skill Level"),
     *              @OA\Property(property="description", type="string", example="Player does not demonstrate the required skill level"),
     *              @OA\Property(property="active", type="boolean", example=true),
     *              @OA\Property(property="sort_order", type="integer", example=1),
     *              @OA\Property(property="meta", type="object", example={"severity": "high", "category": "technical"})
     *          )
     *       )
     *    )
     * )
     * )
     */
    public function getAllReasons(Request $request): JsonResponse
    {
        try {
            $allReasons = EvaluationRejectionReason::orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'message' => 'All rejection reasons retrieved successfully',
                'data' => $allReasons
            ]);
        } catch (Exception $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'meta' => 'nullable|array',
                'meta.*' => 'string'
            ]);

            $existingDescription = EvaluationRejectionReason::where('description', $validated['description'])->first();
            if ($existingDescription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Description already exists'
                ], 400);
            }

            $highestSortOrder = EvaluationRejectionReason::max('sort_order') ?? 0;
            $nextSortOrder = $highestSortOrder + 1;

            $meta = null;
            if (isset($validated['meta']) && is_array($validated['meta'])) {
                $meta = [];
                foreach ($validated['meta'] as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Meta keys and values must be strings'
                        ], 400);
                    }
                    $meta[$key] = $value;
                }
            }

            $rejectionReason = EvaluationRejectionReason::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'active' => true,
                'sort_order' => $nextSortOrder,
                'meta' => $meta,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rejection reason created successfully',
                'data' => $rejectionReason
            ], 201);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_rejection_reasons,id',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'active' => 'sometimes|required|boolean',
                'sort_order' => 'sometimes|required|integer|min:1',
                'meta' => 'sometimes|nullable|array',
                'meta.*' => 'string'
            ]);

            $rejectionReason = EvaluationRejectionReason::findOrFail($validated['id']);

            $updateData = [];
            $hasAtLeastOneField = false;

            if (isset($validated['title'])) {
                $updateData['title'] = $validated['title'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['description'])) {
                if ($validated['description'] !== $rejectionReason->description) {
                    $existingDescription = EvaluationRejectionReason::where('description', $validated['description'])
                        ->where('id', '!=', $validated['id'])
                        ->first();
                    if ($existingDescription) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Description already exists'
                        ], 400);
                    }
                }
                $updateData['description'] = $validated['description'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['active']) && $validated['active'] === true) {
                $sortOrderToCheck = isset($validated['sort_order']) ? $validated['sort_order'] : $rejectionReason->sort_order;

                $existingSortOrder = EvaluationRejectionReason::where('sort_order', $sortOrderToCheck)
                    ->where('id', '!=', $validated['id'])
                    ->where('active', true)
                    ->first();

                if ($existingSortOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot activate record: Sort order ' . $sortOrderToCheck . ' already exists for an active rejection reason (ID: ' . $existingSortOrder->id . ')'
                    ], 400);
                }
            }

            if (isset($validated['sort_order'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $rejectionReason->active;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationRejectionReason::where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();
                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order ' . $validated['sort_order'] . ' already exists for an active rejection reason (ID: ' . $existingSortOrder->id . ')'
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['active'])) {
                $updateData['active'] = $validated['active'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['meta'])) {
                $meta = null;
                if (is_array($validated['meta'])) {
                    $meta = [];
                    foreach ($validated['meta'] as $key => $value) {
                        if (!is_string($key) || !is_string($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Meta keys and values must be strings'
                            ], 400);
                        }
                        $meta[$key] = $value;
                    }
                }
                $updateData['meta'] = $meta;
                $hasAtLeastOneField = true;
            }

            if (!$hasAtLeastOneField) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one field (title, description, active, sort_order, or meta) must be provided for update'
                ], 400);
            }

            $updateData['updated_at'] = now()->format('Y-m-d H:i:s');

            $rejectionReason->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Rejection reason updated successfully',
                'data' => $rejectionReason->fresh()
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_rejection_reasons,id'
            ]);

            $rejectionReason = EvaluationRejectionReason::findOrFail($validated['id']);
            $rejectionReason->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rejection reason deleted successfully'
            ], 200);
        } catch (ValidationException $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getRejectionReason(Request $request, int $id): JsonResponse
    {
        try {
            $rejectionReason = EvaluationRejectionReason::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Rejection reason retrieved successfully',
                'data' => $rejectionReason
            ], 200);
        } catch (Exception $e) {
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rejection reason not found',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }
}
