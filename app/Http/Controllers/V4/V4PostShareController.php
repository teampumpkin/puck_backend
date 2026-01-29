<?php

namespace App\Http\Controllers\V4;


use App\Http\Controllers\Controller;
use App\Models\V4PostShare;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Contracts\ErrorTrackerInterface;

class V4PostShareController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }




    public function store(Request $request, $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if (! $authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        // Merge the postId from the route into the request
        $request->merge(['post_id' => $postId]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:v4_posts,id',
            'conversation_id' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Create post share entry
        $validated = $validator->validated();

        // Use updateOrCreate to handle the unique combo (user_id, post_id, conversation_id)
        $share = V4PostShare::updateOrCreate(
            [
                'user_id' => $authUser->id,
                'post_id' => $validated['post_id'],
                'conversation_id' => $validated['conversation_id'] ?? null,
            ],
            [
                'caption' => $validated['caption'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully.',
            'data' => $share
        ], 201);
    }
}
