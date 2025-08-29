<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve private WhatsApp media files via temporary URLs
     * Only accessible to authenticated users
     */
    public function serveWhatsAppMedia(Request $request, string $type, string $filename): StreamedResponse|Response
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            abort(401, 'Unauthorized access to media file');
        }

        // Validate media type
        $allowedTypes = ['images', 'videos', 'audios', 'documents', 'contacts'];
        if (!in_array($type, $allowedTypes)) {
            abort(400, 'Invalid media type');
        }

        // Construct the private file path
        $filePath = "private/whatsapp/{$type}/{$filename}";

        // Check if file exists in private storage
        if (!Storage::exists($filePath)) {
            Log::warning('WhatsApp media file not found', [
                'file_path' => $filePath,
                'user_id' => Auth::id(),
                'requested_by' => $request->ip()
            ]);
            abort(404, 'Media file not found');
        }

        // Log access for security auditing
        Log::info('WhatsApp media file accessed', [
            'file_path' => $filePath,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email ?? 'unknown',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            // Get file info
            $mimeType = $this->getMimeTypeFromExtension($filename);
            $fileSize = Storage::size($filePath);
            
            // Create streamed response for efficient file serving
            return Storage::response($filePath, $filename, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                // Security headers
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'Content-Security-Policy' => "default-src 'none'",
                'Referrer-Policy' => 'no-referrer'
            ]);

        } catch (\Exception $e) {
            Log::error('Error serving WhatsApp media file', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            abort(500, 'Error serving media file');
        }
    }

    /**
     * Generate temporary URL for WhatsApp media file
     * Valid for 24 hours by default
     */
    public function generateTemporaryUrl(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'media_path' => 'required|string',
            'expires_in_hours' => 'nullable|integer|min:1|max:168' // Max 1 week
        ]);

        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $mediaPath = $request->input('media_path');
        $expiresInHours = $request->input('expires_in_hours', 24);

        // Validate the file exists
        if (!Storage::exists($mediaPath)) {
            return response()->json(['error' => 'Media file not found'], 404);
        }

        try {
            // Generate signed temporary URL
            $temporaryUrl = Storage::temporaryUrl(
                $mediaPath,
                now()->addHours($expiresInHours)
            );

            Log::info('Temporary URL generated for WhatsApp media', [
                'media_path' => $mediaPath,
                'expires_in_hours' => $expiresInHours,
                'user_id' => Auth::id(),
                'generated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'temporary_url' => $temporaryUrl,
                'expires_at' => now()->addHours($expiresInHours)->toISOString(),
                'expires_in_seconds' => $expiresInHours * 3600
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating temporary URL', [
                'media_path' => $mediaPath,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to generate temporary URL'
            ], 500);
        }
    }

    /**
     * Get media file info without serving the file
     */
    public function getMediaInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'media_path' => 'required|string'
        ]);

        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $mediaPath = $request->input('media_path');

        if (!Storage::exists($mediaPath)) {
            return response()->json(['error' => 'Media file not found'], 404);
        }

        try {
            $filename = basename($mediaPath);
            $fileSize = Storage::size($mediaPath);
            $lastModified = Storage::lastModified($mediaPath);
            $mimeType = $this->getMimeTypeFromExtension($filename);

            return response()->json([
                'success' => true,
                'media_info' => [
                    'filename' => $filename,
                    'file_size' => $fileSize,
                    'file_size_human' => $this->formatBytes($fileSize),
                    'mime_type' => $mimeType,
                    'last_modified' => date('Y-m-d H:i:s', $lastModified),
                    'path' => $mediaPath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting media info', [
                'media_path' => $mediaPath,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to get media info'
            ], 500);
        }
    }

    /**
     * List WhatsApp media files for authenticated user
     */
    public function listWhatsAppMedia(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $type = $request->query('type'); // Optional filter by type
        $limit = min($request->query('limit', 50), 100); // Max 100 files per request

        try {
            $files = [];
            $mediaTypes = $type ? [$type] : ['images', 'videos', 'audios', 'documents', 'contacts'];

            foreach ($mediaTypes as $mediaType) {
                $typePath = "private/whatsapp/{$mediaType}";
                
                if (Storage::exists($typePath)) {
                    $typeFiles = Storage::files($typePath);
                    
                    foreach ($typeFiles as $file) {
                        if (count($files) >= $limit) break 2;
                        
                        $filename = basename($file);
                        $files[] = [
                            'filename' => $filename,
                            'type' => $mediaType,
                            'path' => $file,
                            'size' => Storage::size($file),
                            'size_human' => $this->formatBytes(Storage::size($file)),
                            'modified_at' => date('Y-m-d H:i:s', Storage::lastModified($file)),
                            'mime_type' => $this->getMimeTypeFromExtension($filename)
                        ];
                    }
                }
            }

            // Sort by modification date (newest first)
            usort($files, function($a, $b) {
                return strtotime($b['modified_at']) - strtotime($a['modified_at']);
            });

            return response()->json([
                'success' => true,
                'files' => $files,
                'total_count' => count($files),
                'filtered_by_type' => $type
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing WhatsApp media files', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to list media files'
            ], 500);
        }
    }

    /**
     * Get MIME type from file extension
     */
    private function getMimeTypeFromExtension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'vcf' => 'text/vcard'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}