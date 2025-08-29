<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppTokenService;

class WhatsAppTokenManager extends Command
{
    protected $signature = 'whatsapp:token 
                            {action : check|refresh|generate}';
    
    protected $description = 'Manage WhatsApp access tokens';

    private WhatsAppTokenService $tokenService;

    public function __construct(WhatsAppTokenService $tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'check':
                $this->checkToken();
                break;
            case 'refresh':
                $this->refreshToken();
                break;
            case 'generate':
                $this->generateToken();
                break;
            default:
                $this->error('Invalid action. Use: check, refresh, or generate');
        }
    }

    private function checkToken()
    {
        $this->info('Checking current WhatsApp token...');
        
        $validity = $this->tokenService->checkTokenValidity();
        
        if ($validity['is_valid']) {
            $this->info('✅ Token is valid');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Type', $validity['type'] ?? 'Unknown'],
                    ['App ID', $validity['app_id'] ?? 'N/A'],
                    ['Expires At', $validity['expires_at'] ?? 'Never'],
                    ['Scopes', implode(', ', $validity['scopes'] ?? [])]
                ]
            );
        } else {
            $this->error('❌ Token is invalid or expired');
            $this->error($validity['error'] ?? 'Unknown error');
        }
    }

    private function refreshToken()
    {
        $this->info('Refreshing WhatsApp token...');
        
        $newToken = $this->tokenService->refreshToken();
        
        if ($newToken) {
            $this->info('✅ Token refreshed successfully!');
            $this->info('New token (first 20 chars): ' . substr($newToken, 0, 20) . '...');
        } else {
            $this->error('❌ Failed to refresh token');
            $this->info('Trying to generate a new token...');
            $this->generateToken();
        }
    }

    private function generateToken()
    {
        $this->info('Generating new WhatsApp token...');
        
        $result = $this->tokenService->generatePermanentToken();
        
        if ($result) {
            $this->info('✅ Token generated successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Type', $result['type']],
                    ['Token (first 20 chars)', substr($result['token'], 0, 20) . '...'],
                    ['Expires In', $result['expires_in'] ?? 'Never']
                ]
            );
            
            // Update .env file
            $this->updateEnvFile('WHATSAPP_ACCESS_TOKEN', $result['token']);
            $this->info('Token saved to .env file');
        } else {
            $this->error('❌ Failed to generate token');
            $this->info('Please check your APP_ID and APP_SECRET in .env');
        }
    }

    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');
        $content = file_get_contents($path);
        
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
        $replacement = $key . '=' . $value;
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n" . $replacement;
        }
        
        file_put_contents($path, $content);
    }
}