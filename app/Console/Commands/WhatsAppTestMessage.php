<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class WhatsAppTestMessage extends Command
{
    protected $signature = 'whatsapp:send-test 
                            {phone? : Phone number to send test message to}
                            {--message= : Custom message to send}';
    
    protected $description = 'Send a test WhatsApp message';

    private WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $phone = $this->argument('phone') ?? config('whatsapp.test_number', '+15551575779');
        $message = $this->option('message') ?? 'Test message from Delish ERP system at ' . now()->format('Y-m-d H:i:s');

        $this->info('Sending test message to: ' . $phone);
        $this->info('Message: ' . $message);

        try {
            $response = $this->whatsappService->sendMessage($phone, $message);
            
            if (isset($response['messages'][0]['id'])) {
                $this->info('✅ Message sent successfully!');
                $this->info('Message ID: ' . $response['messages'][0]['id']);
            } else {
                $this->error('❌ Failed to send message');
                $this->error('Response: ' . json_encode($response));
            }
        } catch (\Exception $e) {
            $this->error('❌ Error sending message: ' . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'token')) {
                $this->info('Token issue detected. Try running: php artisan whatsapp:token refresh');
            }
        }
    }
}