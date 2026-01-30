<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4BanReason;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4BanReasonController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    public function index(): JsonResponse
    {
        try {
            $reasons = V4BanReason::withTrashed()->get();

            return response()->json([
                'success' => true,
                'message' => 'Ban reasons retrieved successfully',
                'data'    => $reasons,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $reason = V4BanReason::withTrashed()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Ban reason retrieved successfully',
                'data'    => $reason,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Ban reason not found');

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'active'      => 'boolean',
            ]);

            $reason = V4BanReason::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ban reason created successfully',
                'data'    => $reason,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'title'       => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'active'      => 'boolean',
            ]);

            $reason = V4BanReason::findOrFail($id);
            $reason->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ban reason updated successfully',
                'data'    => $reason,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'Ban reason not found');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $reason = V4BanReason::findOrFail($id);
            $reason->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ban reason soft deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Ban reason not found');

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $reason = V4BanReason::withTrashed()->findOrFail($id);
            $reason->restore();

            return response()->json([
                'success' => true,
                'message' => 'Ban reason restored successfully',
                'data'    => $reason,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Ban reason not found');

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        Log::warning('Validation failed: ' . $e->getMessage(), $e->errors());

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    }

    protected function handleNotFound(ModelNotFoundException $e, string $message = 'Resource not found'): JsonResponse
    {
        Log::warning($message . ': ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => $e->getMessage(),
        ], 404);
    }

    protected function handleException(Exception $e): JsonResponse
    {
        Log::error('Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred',
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
