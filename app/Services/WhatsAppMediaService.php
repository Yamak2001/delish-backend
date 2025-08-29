<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppMediaService
{
    private string $accessToken;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    /**
     * Download media file from WhatsApp
     */
    public function downloadMedia(string $mediaId, string $messageType = 'unknown'): ?array
    {
        try {
            // Step 1: Get media URL
            $mediaUrlResponse = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v18.0/{$mediaId}");

            if (!$mediaUrlResponse->successful()) {
                Log::error('Failed to get media URL', [
                    'media_id' => $mediaId,
                    'response' => $mediaUrlResponse->body()
                ]);
                return null;
            }

            $mediaData = $mediaUrlResponse->json();
            $mediaUrl = $mediaData['url'] ?? null;
            $mimeType = $mediaData['mime_type'] ?? 'application/octet-stream';
            $fileSize = $mediaData['file_size'] ?? 0;

            if (!$mediaUrl) {
                Log::error('No media URL found', ['media_data' => $mediaData]);
                return null;
            }

            // Step 2: Download the actual media file
            $fileResponse = Http::withToken($this->accessToken)
                ->timeout(60) // Increase timeout for large files
                ->get($mediaUrl);

            if (!$fileResponse->successful()) {
                Log::error('Failed to download media file', [
                    'media_url' => $mediaUrl,
                    'status' => $fileResponse->status()
                ]);
                return null;
            }

            // Step 3: Generate filename and save to PRIVATE storage
            $extension = $this->getExtensionFromMimeType($mimeType);
            $filename = $this->generateUniqueFilename($mediaId, $messageType, $extension);
            $relativePath = "private/whatsapp/{$messageType}s/{$filename}";

            // Save to PRIVATE storage (not publicly accessible)
            Storage::put($relativePath, $fileResponse->body());

            Log::info('Media downloaded successfully to private storage', [
                'media_id' => $mediaId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'private_path' => $relativePath
            ]);

            return [
                'media_id' => $mediaId,
                'filename' => $filename,
                'path' => $relativePath,
                'full_path' => Storage::path($relativePath),
                'private_storage' => true,
                'mime_type' => $mimeType,
                'file_size' => $fileSize
            ];

        } catch (\Exception $e) {
            Log::error('Media download exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'audio/webm' => 'webm',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $mediaId, string $messageType, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $shortId = Str::substr($mediaId, -8);
        return "{$messageType}_{$timestamp}_{$shortId}.{$extension}";
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'text' => ['body' => $message]
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'to' => $phoneNumber,
                    'message_preview' => Str::limit($message, 100)
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp message', [
                    'to' => $phoneNumber,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp message send exception', [
                'to' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send interactive menu for catalog browsing
     */
    public function sendCatalogMenu(string $phoneNumber, array $categories): bool
    {
        try {
            $buttons = [];
            foreach (array_slice($categories, 0, 3) as $index => $category) {
                $buttons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => "cat_{$category['id']}",
                        'title' => Str::limit($category['name'], 20)
                    ]
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'button',
                        'body' => [
                            'text' => '🍰 Welcome to Delish! Browse our catalog:'
                        ],
                        'action' => [
                            'buttons' => $buttons
                        ]
                    ]
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send catalog menu', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            return false;
        }
    }

    /**
     * Send product list for a category
     */
    public function sendProductList(string $phoneNumber, array $products, string $categoryName): bool
    {
        try {
            $rows = [];
            foreach (array_slice($products, 0, 10) as $product) {
                $rows[] = [
                    'id' => "prod_{$product['id']}",
                    'title' => Str::limit($product['name'], 24),
                    'description' => Str::limit($product['description'] ?? '', 72)
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'list',
                        'body' => [
                            'text' => "🧁 {$categoryName} Products:"
                        ],
                        'action' => [
                            'button' => 'View Products',
                            'sections' => [
                                [
                                    'title' => $categoryName,
                                    'rows' => $rows
                                ]
                            ]
                        ]
                    ]
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send product list', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            return false;
        }
    }
}