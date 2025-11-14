<?php

namespace App\Http\Controllers\V4\Chat;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class V4ChatMediaController extends Controller
{

    /**
     * Upload group profile media (image)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadGroupProfileMedia(Request $request)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Basic validation
            $request->validate([
                'groupImage' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $file = $request->file('groupImage');

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image file provided.'
                ], 400);
            }

            // Handle file upload
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize(); // Size in bytes
            $extension = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();
            $mediaFormat = $this->determineMediaFormat($mimeType);

            // Determine media format and validate size
            $this->validateFileSize($file, $mediaFormat, $mimeType);

            // Generate unique filename to prevent conflicts
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store file in S3 under chat-media directory
            $directory = "group-profile/{$user->id}";
            $path = $file->storeAs(
                $directory,
                $filename,
                's3'
            );

            $mediaUrl = Storage::disk('s3')->url($path);

            return response()->json([
                'success' => true,
                'message' => 'Group profile image uploaded successfully',
                'data' => [
                    'media_url' => $mediaUrl,
                    'original_name' => $originalName,
                    'media_type' => $mimeType,
                    'media_format' => $mediaFormat,
                    'file_size' => $fileSize,
                    'uploaded_at' => now()->toISOString(),
                    'path' => $path
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Upload media file for chat (documents, images, videos)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMedia(Request $request)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Basic validation
            $request->validate([
                'media' => 'required|file',
            ]);

            // Handle file upload
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize(); // Size in bytes

                // Determine media format and validate size
                $mediaFormat = $this->determineMediaFormat($mimeType);
                $this->validateFileSize($file, $mediaFormat, $mimeType);

                // Generate unique filename to prevent conflicts
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Store file in S3 under chat-media directory
                $path = $file->storeAs(
                    'chat-media/' . $user->id,
                    $filename,
                    's3'
                );

                $mediaUrl = Storage::disk('s3')->url($path);

                // Get file information
                $originalName = $file->getClientOriginalName();

                return response()->json([
                    'success' => true,
                    'message' => 'Media uploaded successfully',
                    'data' => [
                        'media_url' => $mediaUrl,
                        'original_name' => $originalName,
                        'media_type' => $mimeType,
                        'media_format' => $mediaFormat,
                        'file_size' => $fileSize,
                        'uploaded_at' => now()->toISOString(),
                        'path' => $path
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No media file provided'
            ], 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate file size based on media format
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $mediaFormat
     * @param string $mimeType
     * @throws ValidationException
     */
    private function validateFileSize($file, $mediaFormat, $mimeType)
    {
        $fileSize = $file->getSize(); // Size in bytes

        $limits = [
            'image' => 2,
            'audio' => 3,
            'document' => 2,
            'video' => 4,
            'file' => 2,
        ];

        $maxSize = $limits[$mediaFormat] ?? 2; // Default 2MB
        $maxSizeInBytes = $maxSize * 1024 * 1024;

        if ($fileSize > $maxSizeInBytes) {
            throw ValidationException::withMessages([
                'media' => [
                    "The {$mediaFormat} file size can't exceeds {$maxSize}MB."
                ]
            ]);
        }
    }

    /**
     * Determine media format based on MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function determineMediaFormat($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (
            in_array($mimeType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-rar-compressed',
                'application/json',
                'application/xml'
            ])
        ) {
            return 'document';
        } else {
            return 'file';
        }
    }

    /**
     * Get media file from S3 with formatted response
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function getMedia(Request $request)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Validate request
            $request->validate([
                'path' => 'required|string',
                'download' => 'sometimes|boolean'
            ]);

            $path = $request->input('path');
            $download = $request->input('download', false);

            // Check if file exists in S3
            if (!Storage::disk('s3')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media file not found'
                ], 404);
            }

            // Extract media information from path
            $mediaInfo = $this->extractMediaInfoFromPath($path);

            // Get file URL from S3
            $mediaUrl = Storage::disk('s3')->url($path);

            // If download is requested, return a temporary signed URL
            if ($download) {
                $signedUrl = Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(60) // URL expires in 1 hour
                );

                return redirect($signedUrl);
            }

            // Get file metadata
            $fileSize = Storage::disk('s3')->size($path);
            $lastModified = Storage::disk('s3')->lastModified($path);

            // Generate formatted media name
            $formattedMediaName = $this->generateFormattedMediaName($mediaInfo);

            return response()->json([
                'success' => true,
                'message' => 'Media retrieved successfully',
                'data' => [
                    'media_url' => $mediaUrl,
                    'original_path' => $path,
                    'formatted_media_name' => $formattedMediaName,
                    'media_format' => $mediaInfo['format_code'],
                    'media_type' => $mediaInfo['mime_type'],
                    'original_name' => $mediaInfo['original_name'],
                    'file_size' => $fileSize,
                    'last_modified' => date('Y-m-d H:i:s', $lastModified),
                    'current_date' => $mediaInfo['current_date']
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Extract media information from S3 path
     *
     * @param string $path
     * @return array
     */
    private function extractMediaInfoFromPath($path)
    {
        // Extract filename from path: "chat-media/21/1758529440_68d107a0e01fe.jpg"
        $pathParts = explode('/', $path);
        $filename = end($pathParts); // "1758529440_68d107a0e01fe.jpg"

        // Get file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION); // "jpg"
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME); // "1758529440_68d107a0e01fe"

        // Determine MIME type based on extension (since we can't get it from S3 directly)
        $mimeType = $this->getMimeTypeFromExtension($extension);

        // Determine media format
        $mediaFormat = $this->determineMediaFormat($mimeType);

        // Get format code
        $formatCode = $this->getFormatCode($mediaFormat);

        // Get current date in required format
        $currentDate = now()->format('Ymd'); // 20251109

        return [
            'filename' => $filename,
            'original_name' => $nameWithoutExt,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'media_format' => $mediaFormat,
            'format_code' => $formatCode,
            'current_date' => $currentDate
        ];
    }

    /**
     * Generate formatted media name: MediaFormat-currentDate-mediaNamewithextension
     *
     * @param array $mediaInfo
     * @return string
     */
    private function generateFormattedMediaName($mediaInfo)
    {
        // Format: MediaFormat-currentDate-mediaNamewithextension
        // Example: IMG-20251109-1758529440_68d107a0e01fe.jpg
        return $mediaInfo['format_code'] . '-' . $mediaInfo['current_date'] . '-' . $mediaInfo['filename'];
    }

    /**
     * Get format code based on media format
     *
     * @param string $mediaFormat
     * @return string
     */
    private function getFormatCode($mediaFormat)
    {
        $formatCodes = [
            'audio' => 'AUD',
            'video' => 'VID',
            'image' => 'IMG',
            'document' => 'DOC',
            'file' => 'DOC' // Default to DOC for any other format
        ];

        return $formatCodes[$mediaFormat] ?? 'DOC';
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $extension
     * @return string
     */
    private function getMimeTypeFromExtension($extension)
    {
        $mimeTypes = [
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',

            // Videos
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'flv' => 'video/x-flv',

            // Audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',

            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
