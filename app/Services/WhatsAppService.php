<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiVersion = 'v21.0';
    private string $phoneNumberId;
    private string $accessToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->accessToken = config('whatsapp.access_token');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}";
    }

    public function sendMessage(string $to, string $message): array
    {
        $to = $this->formatPhoneNumber($to);
        
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to send WhatsApp message: ' . $response->body());
        }

        return $response->json();
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): array
    {
        $to = $this->formatPhoneNumber($to);
        
        $components = [];
        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters)
            ];
        }

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'en'],
                    'components' => $components
                ]
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp Template API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to send WhatsApp template: ' . $response->body());
        }

        return $response->json();
    }

    public function sendMedia(string $to, string $type, string $mediaUrl, ?string $caption = null): array
    {
        $to = $this->formatPhoneNumber($to);
        
        $mediaData = [
            'link' => $mediaUrl
        ];
        
        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $mediaData['caption'] = $caption;
        }

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => $type,
                $type => $mediaData
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp Media API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to send WhatsApp media: ' . $response->body());
        }

        return $response->json();
    }

    public function markAsRead(string $messageId): array
    {
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp Mark as Read Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return $response->json();
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming US if starts with 1)
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        return $phone;
    }

    public function validateWebhook(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = config('whatsapp.verify_token');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }
        
        return null;
    }

    public function processWebhook(array $data): void
    {
        Log::info('WhatsApp Webhook Received', $data);
        
        // Process the webhook data
        if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
            foreach ($data['entry'][0]['changes'][0]['value']['messages'] as $message) {
                $this->processIncomingMessage($message);
            }
        }
        
        // Process status updates
        if (isset($data['entry'][0]['changes'][0]['value']['statuses'])) {
            foreach ($data['entry'][0]['changes'][0]['value']['statuses'] as $status) {
                $this->processStatusUpdate($status);
            }
        }
    }

    private function processIncomingMessage(array $message): void
    {
        Log::info('Processing incoming WhatsApp message', $message);
        
        // Here you would typically:
        // 1. Store the message in database
        // 2. Trigger any business logic
        // 3. Send auto-reply if needed
    }

    private function processStatusUpdate(array $status): void
    {
        Log::info('Processing WhatsApp status update', $status);
        
        // Update message status in database
    }
}