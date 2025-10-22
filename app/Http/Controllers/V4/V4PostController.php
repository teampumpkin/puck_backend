<?php

namespace App\Http\Controllers\V4;


use App\Http\Controllers\Controller;
use App\Models\V4Post;
use App\Models\V4PostMedia;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class V4PostController extends Controller
{


    /**
     * Store a newly created post with media upload to S3
     */
    public function uploadPost(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // --------------------------
            // ✅ Validation
            // --------------------------
            $validated = $request->validate([
                'caption' => 'nullable|string|max:2000',
                'media' => 'required|array|min:1|max:10', // max 10 uploads
                'media.*.type' => 'required|in:image,video',
                'media.*.file' => [
                    'required',
                    'file',
                    'max:10240', // 10MB
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $type = $request->input("media.$index.type");

                        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                        $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/webm'];

                        $mime = $value->getMimeType();

                        if ($type === 'image' && !in_array($mime, $allowedImageTypes)) {
                            return $fail("Invalid image type: $mime");
                        }

                        if ($type === 'video' && !in_array($mime, $allowedVideoTypes)) {
                            return $fail("Invalid video type: $mime");
                        }

                        if ($type === 'image') {
                            [$width, $height] = @getimagesize($value);
                            if (!$width || $width > 10000 || $height > 10000) {
                                return $fail("Image dimensions must be under 10000x10000 pixels.");
                            }
                        }
                    }
                ],
            ]);

            // --------------------------
            // ✅ Authenticated User
            // --------------------------
            $authUser = Auth::guard('v4api')->user();

            // --------------------------
            // ✅ Create Post
            // --------------------------
            $post = V4Post::create([
                'user_id' => $authUser->id,
                'caption' => $request->caption,
            ]);

            // --------------------------
            // ✅ Upload Media Files
            // --------------------------
            foreach ($validated['media'] as $index => $item) {
                $file = $item['file'];

                if (!$file->isValid()) {
                    throw new Exception('One or more uploaded files are invalid.');
                }

                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize();

                $fileName = $item['type'] . '_' . Str::uuid() . '_' . time() . '.' . $extension;
                $folder = $item['type'] === 'image' ? "posts/images/{$post->id}" : "posts/videos/{$post->id}";

                $path = $file->storeAs($folder, $fileName, 's3');
                $url = Storage::disk('s3')->url($path);

                V4PostMedia::create([
                    'post_id' => $post->id,
                    'type' => $item['type'],
                    'url' => $url,
                    'mime_type' => $mimeType,
                    'order' => $index,
                    'meta' => [
                        'original_name' => $originalName,
                        'file_size' => $fileSize,
                        'storage_path' => $path,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post->load('media'),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Upload post failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getMyPosts(Request $request): JsonResponse
    {
        try {
            $authUser = Auth::guard('v4api')->user();

            // Get page and per_page or fallback to defaults
            $perPage = $request->input('per_page', 10); // default: 10 posts per page

            // Fetch paginated posts with media
            $posts = V4Post::with('media')
                ->where('user_id', $authUser->id)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts->items(),
                'pagination' => [
                    'total'          => $posts->total(),
                    'per_page'       => $posts->perPage(),
                    'current_page'   => $posts->currentPage(),
                    'last_page'      => $posts->lastPage(),
                    'from'           => $posts->firstItem() ?? 0,
                    'to'             => $posts->lastItem() ?? 0,
                    'has_more_pages' => $posts->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getMyPost(V4Post $post): JsonResponse
    {
        try {
            $authUser = Auth::guard('v4api')->user();

            // Ensure the post belongs to the authenticated user
            if ($post->user_id !== $authUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this post.',
                ], 403);
            }

            // Load media relationship
            $post->load('media');

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $post,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function editPost(Request $request, V4Post $post): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if ($post->user_id !== $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this post.',
            ], 403);
        }

        $validated = $request->validate([
            'caption' => 'nullable|string|max:2000',
        ]);

        try {
            $post->update([
                'caption' => $validated['caption'] ?? $post->caption,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully.',
                'data' => $post->load('media'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function deletePost(V4Post $post): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        if ($post->user_id !== $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this post.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Optionally, delete files from S3 storage here before deleting DB records
            // foreach ($post->media as $media) {
            //     Storage::disk('s3')->delete($media->meta['storage_path'] ?? null);
            // }

            $post->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
