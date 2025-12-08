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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // --------------------------
        // ✅ Authenticated User
        // --------------------------
        $authUser = Auth::guard('v4api')->user();

        try {
            // --------------------------
            // ✅ Validation
            // --------------------------
            $validated = $request->validate([
                'caption'      => 'nullable|string|max:2000',
                'media'        => 'required|array|min:1|max:10', // max 10 uploads
                'media.*.type' => 'required|in:image,video',
                'media.*.file' => [
                    'required',
                    'file',
                    'max:10240', // 10MB
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $type  = $request->input("media.$index.type");

                        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                        $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/webm'];

                        $mime = $value->getMimeType();

                        if ($type === 'image' && ! in_array($mime, $allowedImageTypes)) {
                            return $fail("Invalid image type: $mime");
                        }

                        if ($type === 'video' && ! in_array($mime, $allowedVideoTypes)) {
                            return $fail("Invalid video type: $mime");
                        }

                        if ($type === 'image') {
                            [$width, $height] = @getimagesize($value);
                            if (! $width || $width > 10000 || $height > 10000) {
                                return $fail("Image dimensions must be under 10000x10000 pixels.");
                            }
                        }
                    },
                ],
            ]);

            // --------------------------
            // ✅ Determine the user ID for post ownership
            // --------------------------
            $postUserId = $authUser->role === 'super-admin'
                ? optional($authUser->superAdminProfile)->super_admin_id ?? $authUser->id
                : $authUser->id;

            // --------------------------
            // ✅ Create Post
            // --------------------------
            $post = V4Post::create([
                'user_id' => $postUserId,
                'caption' => $validated['caption'] ?? null,
            ]);

            // --------------------------
            // ✅ Upload Media Files
            // --------------------------
            foreach ($validated['media'] as $index => $item) {
                $file = $item['file'];

                if (! $file->isValid()) {
                    throw new Exception('One or more uploaded files are invalid.');
                }

                $originalName = $file->getClientOriginalName();
                $extension    = $file->getClientOriginalExtension();
                $mimeType     = $file->getClientMimeType();
                $fileSize     = $file->getSize();

                $fileName = $item['type'] . '_' . Str::uuid() . '_' . time() . '.' . $extension;
                $folder   = $item['type'] === 'image' ? "posts/images/{$post->id}" : "posts/videos/{$post->id}";

                $path = $file->storeAs($folder, $fileName, 's3');
                $url  = Storage::disk('s3')->url($path);

                V4PostMedia::create([
                    'post_id'   => $post->id,
                    'type'      => $item['type'],
                    'url'       => $url,
                    'mime_type' => $mimeType,
                    'order'     => $index,
                    'meta'      => [
                        'original_name' => $originalName,
                        'file_size'     => $fileSize,
                        'storage_path'  => $path,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data'    => $post->load('media'),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Post upload failed.', [
                'user_id' => $authUser->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while uploading post.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getMyPosts(Request $request): JsonResponse
    {
        $user = Auth::guard('v4api')->user();
        try {
            // Validate pagination input
            $validated = $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $perPage = $validated['per_page'] ?? 10;

            // Determine the ID to filter posts
            $filterUserId = $user->role === 'super-admin'
                ? optional($user->superAdminProfile)->super_admin_id ?? $user->id
                : $user->id;

            // Fetch paginated posts with media
            // Fetch paginated posts
            $posts = V4Post::with('media')
                ->where('user_id', $filterUserId)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success'    => true,
                'message'    => 'Posts retrieved successfully',
                'data'       => $posts->items(),
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to retrieve posts.', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while retrieving posts.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getMyPost($postId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $post = V4Post::where('id', $postId)
                ->with('media')
                ->firstOrFail();

            // Ensure the post belongs to the authenticated user
            if ($post->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this post.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data'    => $post,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found or access denied.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error retrieving post.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getPostById($postId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $post = V4Post::where('id', $postId)
                ->with([
                    'user:id,profile_photo,first_name,last_name,role',
                    'media:id,post_id,type,url',
                    'likedByAuthUser',
                    'comments' => function ($query) {
                        $query->latest()->limit(1); // ✅ Only latest comment
                    },
                    'comments.user:id,username,profile_photo,role',
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data'    => $post,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found or access denied.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error retrieving post.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function editPost(Request $request, int $postId): JsonResponse
    {
        $user = Auth::guard('v4api')->user();

        try {
            // Validation
            $validated = $request->validate([
                'caption' => ['nullable', 'string', 'max:2000'],
            ]);

            // Find post
            $post = V4Post::findOrFail($postId);

            // Authorization
            if ($post->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this post.',
                ], 403);
            }

            // Update
            $post->update([
                'caption' => $validated['caption'] ?? $post->caption,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully.',
                'data'    => $post->fresh()->load('media'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found during update attempt.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Failed to update post.', [
                'post_id' => $postId,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update post.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deletePost(int $postId): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();

        try {
            $post = V4Post::findOrFail($postId);

            // Authorization: Ensure the authenticated user owns the post
            if ($post->user_id !== $authUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this post.',
                ], 403);
            }

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
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('Post not found during deletion attempt.', [
                'post_id' => $postId,
                'user_id' => $authUser->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
