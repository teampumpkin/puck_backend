<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4SuspendedUser;
use App\Models\V4User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4SuspendedUserController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    public function index(): JsonResponse
    {
        try {
            $suspensions = V4SuspendedUser::with(['user', 'reason'])->withTrashed()->get();

            return response()->json([
                'success' => true,
                'message' => 'Suspended users retrieved successfully',
                'data'    => $suspensions,
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
            $suspension = V4SuspendedUser::with(['user', 'reason'])->withTrashed()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Suspended user retrieved successfully',
                'data'    => $suspension,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Suspended user not found');

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
                'user_id'   => 'required|exists:v4_users,id',
                'reason_id' => 'required|exists:v4_suspend_reasons,id',
                'message'   => 'nullable|string',
            ]);

            $suspension = V4SuspendedUser::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully',
                'data'    => $suspension,
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
                'reason_id'      => 'sometimes|required|exists:v4_suspend_reasons,id',
                'message'        => 'nullable|string',
                'unsuspended_at' => 'nullable|date',
            ]);

            $suspension = V4SuspendedUser::findOrFail($id);
            $suspension->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Suspension updated successfully',
                'data'    => $suspension,
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
            return $this->handleNotFound($e, 'Suspended user not found');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function suspend(Request $request, int $userId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'reason_id' => 'required|exists:v4_suspend_reasons,id',
                'message'   => 'nullable|string',
            ]);

            $user = V4User::findOrFail($userId);

            $suspension = V4SuspendedUser::create([
                'user_id'      => $user->id,
                'reason_id'    => $validated['reason_id'],
                'message'      => $validated['message'] ?? null,
                'suspended_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully',
                'data'    => $suspension,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'User not found');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function unsuspend(int $userId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $suspension = V4SuspendedUser::where('user_id', $userId)
                ->whereNull('unsuspended_at')
                ->firstOrFail();

            $suspension->update([
                'unsuspended_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User unsuspended successfully',
                'data'    => $suspension,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'Active suspension not found for this user');

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $suspension = V4SuspendedUser::findOrFail($id);
            $suspension->delete();

            return response()->json([
                'success' => true,
                'message' => 'Suspension soft deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Suspended user not found');

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
            $suspension = V4SuspendedUser::withTrashed()->findOrFail($id);
            $suspension->restore();

            return response()->json([
                'success' => true,
                'message' => 'Suspension restored successfully',
                'data'    => $suspension,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Suspended user not found');

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
