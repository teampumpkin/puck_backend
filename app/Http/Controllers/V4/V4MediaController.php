<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Http\Requests\V4\V4UploadMediaRequest;
use App\Models\V4Media;
use App\Models\V4User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class   V4MediaController extends Controller
{
    /**
     * Upload media file (photo or video)
     *
     * @param V4UploadMediaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMedia(V4UploadMediaRequest $request)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Handle file upload
            if ($request->hasFile('media')) {
                $path = $request->file('media')->store(
                    'media/'.$user->id, 's3'
                );
                $mediaUrl = Storage::disk('s3')->url($path);

                // Get the MIME type
                $mimeType = $request->file('media')->getClientMimeType();

                // Determine if it's an image or video
                $mediaFormat = str_starts_with($mimeType, 'image/') ? 'image' : 'video';

                // Save to database without caption
                $media = V4Media::create([
                    'v4_user_id' => $user->id,
                    'media_type' => $mimeType,
                    'media_format' => $mediaFormat,
                    'uploaded_at' => now(),
                    'media_url' => $mediaUrl
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Media uploaded successfully',
                    'media' => $media
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No media file provided'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all media for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMedia()
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            $media = V4Media::where('v4_user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Media retrieved successfully',
                'media' => $media
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Edit media (update caption and/or replace media file)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editMedia(Request $request, $id)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Find the media
            $media = V4Media::where('id', $id)
                ->where('v4_user_id', $user->id)
                ->first();

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found or you do not have permission to edit it'
                ], 404);
            }

            // Validate request
            $request->validate([
                'caption' => 'sometimes|string|max:500',
                'media' => 'sometimes|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480'
            ]);

            // Update caption if provided
            if ($request->has('caption')) {
                $media->caption = $request->input('caption');
            }

            // Replace media file if provided
            if ($request->hasFile('media')) {
                // Delete old file from S3
                $parsedUrl = parse_url($media->media_url);
                $oldPath = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

                // Only attempt to delete if we have a valid path
                if (!empty($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }

                // Upload new file
                $path = $request->file('media')->store(
                    'media/'.$user->id, 's3'
                );
                $mediaUrl = Storage::disk('s3')->url($path);

                // Get the MIME type
                $mimeType = $request->file('media')->getClientMimeType();

                // Determine if it's an image or video
                $mediaFormat = str_starts_with($mimeType, 'image/') ? 'image' : 'video';

                // Update media record
                $media->media_url = $mediaUrl;
                $media->media_type = $mimeType;
                $media->media_format = $mediaFormat;
                $media->uploaded_at = now();
            }

            $media->save();

            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'media' => $media
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete media
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMedia($id)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Find the media
            $media = V4Media::where('id', $id)
                ->where('v4_user_id', $user->id)
                ->first();

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found or you do not have permission to delete it'
                ], 404);
            }

            // Delete file from S3
            $parsedUrl = parse_url($media->media_url);
            $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

            // Only attempt to delete if we have a valid path
            if (!empty($path)) {
                Storage::disk('s3')->delete($path);
            }

            // Delete record from database
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
