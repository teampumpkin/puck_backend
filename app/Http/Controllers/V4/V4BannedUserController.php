<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4BannedUser;
use App\Models\V4User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4BannedUserController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $bans = V4BannedUser::with(['user', 'reason'])->withTrashed()->get();

            return response()->json([
                'success' => true,
                'message' => 'Banned users retrieved successfully',
                'data'    => $bans,
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $ban = V4BannedUser::with(['user', 'reason'])->withTrashed()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Banned user retrieved successfully',
                'data'    => $ban,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Banned user not found');
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
                'reason_id' => 'required|exists:v4_ban_reasons,id',
                'message'   => 'nullable|string',
            ]);

            $ban = V4BannedUser::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User banned successfully',
                'data'    => $ban,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);
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
                'reason_id'   => 'sometimes|required|exists:v4_ban_reasons,id',
                'message'     => 'nullable|string',
                'unbanned_at' => 'nullable|date',
            ]);

            $ban = V4BannedUser::findOrFail($id);
            $ban->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ban updated successfully',
                'data'    => $ban,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'Banned user not found');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function ban(Request $request, int $userId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'reason_id' => 'required|exists:v4_ban_reasons,id',
                'message'   => 'nullable|string',
            ]);

            $user = V4User::findOrFail($userId);

            $ban = V4BannedUser::create([
                'user_id'   => $user->id,
                'reason_id' => $validated['reason_id'],
                'message'   => $validated['message'] ?? null,
                'banned_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User banned successfully',
                'data'    => $ban,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->handleValidationException($e);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'User not found');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function unban(int $userId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $ban = V4BannedUser::where('user_id', $userId)
                ->whereNull('unbanned_at')
                ->firstOrFail();

            $ban->update([
                'unbanned_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User unbanned successfully',
                'data'    => $ban,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound($e, 'Active ban not found for this user');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $ban = V4BannedUser::findOrFail($id);
            $ban->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ban soft deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Banned user not found');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $ban = V4BannedUser::withTrashed()->findOrFail($id);
            $ban->restore();

            return response()->json([
                'success' => true,
                'message' => 'Ban restored successfully',
                'data'    => $ban,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound($e, 'Banned user not found');
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
