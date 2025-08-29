<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppTokenService
{
    private string $appId;
    private string $appSecret;
    
    public function __construct()
    {
        $this->appId = env('WHATSAPP_APP_ID');
        $this->appSecret = env('WHATSAPP_APP_SECRET');
    }

    /**
     * Get a valid access token (from cache or refresh)
     */
    public function getValidToken(): ?string
    {
        // Try to get token from cache first
        $cachedToken = Cache::get('whatsapp_access_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        // If not in cache, try to refresh
        return $this->refreshToken();
    }

    /**
     * Generate a new long-lived token using permanent token
     * Note: This requires a System User token or App token
     */
    public function generatePermanentToken(): ?array
    {
        try {
            // For System Users (most reliable method)
            // This requires manual setup in Business Manager
            
            // Step 1: Create System User in Business Settings
            // Step 2: Assign WhatsApp assets to System User
            // Step 3: Generate token with no expiration
            
            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'scope' => 'whatsapp_business_messaging,whatsapp_business_management'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // This generates an app access token
                // For production, use System User tokens instead
                return [
                    'token' => $data['access_token'],
                    'type' => 'app_token',
                    'expires_in' => null // App tokens don't expire
                ];
            }

            Log::error('Failed to generate permanent token', [
                'response' => $response->body()
            ]);
            
            return null;

        } catch (\Exception $e) {
            Log::error('Error generating permanent token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Refresh existing token
     */
    public function refreshToken(): ?string
    {
        $currentToken = env('WHATSAPP_ACCESS_TOKEN');
        
        try {
            // Exchange for long-lived token
            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $currentToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 5184000; // 60 days
                
                // Cache the token (expires 1 day before actual expiry)
                Cache::put('whatsapp_access_token', $newToken, $expiresIn - 86400);
                
                // Update environment file
                $this->updateEnvToken($newToken);
                
                Log::info('WhatsApp token refreshed', [
                    'expires_in_days' => round($expiresIn / 86400)
                ]);
                
                return $newToken;
            }

            Log::error('Failed to refresh token', [
                'response' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Check token validity
     */
    public function checkTokenValidity(string $token = null): array
    {
        $token = $token ?? env('WHATSAPP_ACCESS_TOKEN');
        
        try {
            $response = Http::get('https://graph.facebook.com/v18.0/debug_token', [
                'input_token' => $token,
                'access_token' => $this->appId . '|' . $this->appSecret
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                return [
                    'is_valid' => $data['is_valid'] ?? false,
                    'expires_at' => isset($data['expires_at']) ? date('Y-m-d H:i:s', $data['expires_at']) : null,
                    'scopes' => $data['scopes'] ?? [],
                    'type' => $data['type'] ?? 'unknown',
                    'app_id' => $data['app_id'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Token validation error', [
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'is_valid' => false,
            'error' => 'Could not validate token'
        ];
    }

    /**
     * Update token in .env file
     */
    private function updateEnvToken(string $token): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);
        
        $pattern = '/^WHATSAPP_ACCESS_TOKEN=.*/m';
        $replacement = 'WHATSAPP_ACCESS_TOKEN=' . $token;
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n" . $replacement;
        }
        
        file_put_contents($path, $content);
    }
}