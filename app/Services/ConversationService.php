<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ConversationService
{
    private string $messagingServiceUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->messagingServiceUrl = env('MESSAGING_SERVICE_URL', 'http://delish-messaging:3000');
        $this->apiKey = env('MESSAGING_API_KEY', 'delish_microservice_key_2025');
    }

    /**
     * Get all conversations from messaging service
     */
    public function getConversations(string $status = 'active', int $limit = 20, int $offset = 0): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->messagingServiceUrl}/api/conversations", [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch conversations', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'conversations' => [],
                'total' => 0,
                'error' => 'Failed to fetch conversations'
            ];
        } catch (\Exception $e) {
            Log::error('Conversation service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'conversations' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get single conversation with messages
     */
    public function getConversation(int $conversationId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->messagingServiceUrl}/api/conversations/{$conversationId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch conversation', [
                'conversation_id' => $conversationId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Get conversation error', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Send WhatsApp message via messaging service
     */
    public function sendMessage(array $messageData): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->messagingServiceUrl}/api/messages/send", $messageData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Failed to send message', [
                'status' => $response->status(),
                'body' => $response->body(),
                'data' => $messageData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send message',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Send message error', [
                'error' => $e->getMessage(),
                'data' => $messageData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get message history for a phone number
     */
    public function getMessageHistory(string $phone, int $limit = 50, int $offset = 0): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->messagingServiceUrl}/api/messages/history/{$phone}", [
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch message history', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'conversation' => null,
                'messages' => []
            ];
        } catch (\Exception $e) {
            Log::error('Get message history error', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return [
                'conversation' => null,
                'messages' => []
            ];
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(int $conversationId): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->put("{$this->messagingServiceUrl}/api/messages/read/{$conversationId}");

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to mark messages as read', [
                'conversation_id' => $conversationId,
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Mark messages as read error', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Update conversation status
     */
    public function updateConversationStatus(int $conversationId, string $status): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->patch("{$this->messagingServiceUrl}/api/conversations/{$conversationId}/status", [
                'status' => $status
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to update conversation status', [
                'conversation_id' => $conversationId,
                'status' => $status,
                'response_status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Update conversation status error', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Forward webhook to messaging service
     */
    public function forwardWebhook(array $webhookData): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->messagingServiceUrl}/webhooks/whatsapp", $webhookData);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to forward webhook', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Forward webhook error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Search conversations
     */
    public function searchConversations(string $query, int $limit = 20): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->messagingServiceUrl}/api/conversations/search", [
                'q' => $query,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Search conversations error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get conversation statistics
     */
    public function getConversationStats(): array
    {
        $cacheKey = 'conversation_stats';
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                // Get active conversations
                $activeResponse = $this->getConversations('active', 100);
                $activeCount = $activeResponse['total'] ?? 0;

                // Get resolved conversations  
                $resolvedResponse = $this->getConversations('resolved', 100);
                $resolvedCount = $resolvedResponse['total'] ?? 0;

                // Get archived conversations
                $archivedResponse = $this->getConversations('archived', 100);
                $archivedCount = $archivedResponse['total'] ?? 0;

                return [
                    'active' => $activeCount,
                    'resolved' => $resolvedCount,
                    'archived' => $archivedCount,
                    'total' => $activeCount + $resolvedCount + $archivedCount
                ];
            } catch (\Exception $e) {
                Log::error('Get conversation stats error', [
                    'error' => $e->getMessage()
                ]);

                return [
                    'active' => 0,
                    'resolved' => 0,
                    'archived' => 0,
                    'total' => 0
                ];
            }
        });
    }
}