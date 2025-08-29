<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshWhatsAppToken extends Command
{
    protected $signature = 'whatsapp:refresh-token';
    protected $description = 'Refresh WhatsApp Business API token';

    public function handle()
    {
        $appId = env('WHATSAPP_APP_ID');
        $appSecret = env('WHATSAPP_APP_SECRET');
        $currentToken = env('WHATSAPP_ACCESS_TOKEN');

        try {
            // Exchange for long-lived token (60 days)
            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $currentToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 5184000; // 60 days in seconds

                // Update .env file
                $this->updateEnvFile('WHATSAPP_ACCESS_TOKEN', $newToken);
                
                $this->info('Token refreshed successfully!');
                $this->info('New token expires in: ' . round($expiresIn / 86400) . ' days');
                
                Log::info('WhatsApp token refreshed', [
                    'expires_in_days' => round($expiresIn / 86400)
                ]);

                return Command::SUCCESS;
            }

            $this->error('Failed to refresh token: ' . $response->body());
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Error refreshing token: ' . $e->getMessage());
            Log::error('WhatsApp token refresh failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }

    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');
        $content = file_get_contents($path);
        
        // Handle multiline values
        if (strpos($content, "$key=\"") !== false) {
            // Key with quotes exists
            $pattern = '/^' . preg_quote($key, '/') . '=".*?"$/m';
            $replacement = $key . '="' . $value . '"';
        } else {
            // Key without quotes or doesn't exist
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $replacement = $key . '=' . $value;
        }
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n" . $replacement;
        }
        
        file_put_contents($path, $content);
    }
}