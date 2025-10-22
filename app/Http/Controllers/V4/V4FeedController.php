<?php

namespace App\Http\Controllers\V4;


use App\Models\V4Post;
use App\Models\V4PostMedia;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class V4FeedController extends Controller
{

    public function  getRecentFeeds(Request $request): JsonResponse
    {
        try {
            $authUser = Auth::guard('v4api');


            return response()->json(
                []
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
