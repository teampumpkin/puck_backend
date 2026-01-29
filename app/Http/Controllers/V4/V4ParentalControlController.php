<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use App\Services\ParentalControlService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Contracts\ErrorTrackerInterface;

class V4ParentalControlController extends Controller
{
    protected $errorTracker;

    protected $parentalControlService;

    public function __construct(ErrorTrackerInterface $errorTracker, ParentalControlService $parentalControlService)
    {
        $this->errorTracker = $errorTracker;
        $this->parentalControlService = $parentalControlService;
    }

    private function checkIfUserIsParent($authUser, $userId)
    {
        $child = V4User::find($userId);

        if (! $child) {
            throw new ModelNotFoundException('Child not found');
        }

        if ($authUser->id !== $child->parent_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You are not the parent of this child',
            ], Response::HTTP_FORBIDDEN);
        }

        return $child;
    }

    private function sendResponse($status, $message, $data = null, $statusCode = Response::HTTP_OK)
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    public function getParentControl($userId)
    {
        try {
            $authUser      = Auth::guard('v4api')->user();
            $parentControl = $this->parentalControlService->getParentControl($authUser->id, $userId);

            if (!$parentControl) {
                $parentControl = $this->parentalControlService->createControl($authUser->id, $userId);
            }

            return $this->sendResponse('success', 'Parental control fetched successfully', [
                'parent_id' => $parentControl->parent_id,
                'child_id'  => $parentControl->child_id,
                'enabled'   => $parentControl->enabled,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Error fetching parental control: ' . $e->getMessage());

            return $this->sendResponse('error', 'Parental control or child not found', null, Response::HTTP_NOT_FOUND);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (QueryException $e) {
            Log::error('Database query error: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while fetching the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Unexpected error fetching parental control: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while fetching the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function toggleParentControl($userId, Request $request)
    {
        try {
            $authUser = Auth::guard('v4api')->user();

            $child = $this->checkIfUserIsParent($authUser, $userId);

            $parentalControl = $this->parentalControlService->toggleControl($authUser->id, $userId);

            return $this->sendResponse('success', 'Parental control updated successfully', [
                'enabled' => $parentalControl->enabled,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Child not found: ' . $e->getMessage());

            return $this->sendResponse('error', 'Child not found', null, Response::HTTP_NOT_FOUND);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (QueryException $e) {
            Log::error('Database query error: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while toggling the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Error toggling parental control: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while toggling the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }

    public function deleteControl($userId)
    {
        try {
            $authUser = Auth::guard('v4api')->user();

            $child = $this->checkIfUserIsParent($authUser, $userId);

            $this->parentalControlService->deleteControl($authUser->id, $userId);

            return $this->sendResponse('success', 'Parental control deleted successfully');
        } catch (ModelNotFoundException $e) {
            Log::error('Parental control or child not found: ' . $e->getMessage());

            return $this->sendResponse('error', 'Parental control or child not found', null, Response::HTTP_NOT_FOUND);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        } catch (QueryException $e) {
            Log::error('Database query error: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while deleting the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            Log::error('Error deleting parental control: ' . $e->getMessage());

            return $this->sendResponse('error', 'An error occurred while deleting the parental control', null, Response::HTTP_INTERNAL_SERVER_ERROR);

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);
        }
    }
}
