<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Recipe;
use App\Services\OrderProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    private OrderProcessingService $orderProcessingService;

    public function __construct(OrderProcessingService $orderProcessingService)
    {
        $this->orderProcessingService = $orderProcessingService;
    }

    /**
     * WhatsApp webhook endpoint - handles both verification and messages
     * GET: Webhook verification from WhatsApp
     * POST: Receives messages from merchants and processes orders
     */
    public function webhook(Request $request): JsonResponse|string
    {
        // Handle GET request for webhook verification
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');
            
            Log::info('WhatsApp webhook verification attempt', [
                'mode' => $mode,
                'token_provided' => $token,
                'challenge' => $challenge
            ]);
            
            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
                Log::info('WhatsApp webhook verified successfully');
                // Return just the challenge value as plain text
                echo $challenge;
                exit;
            }
            
            Log::warning('WhatsApp webhook verification failed');
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        // Handle POST request for webhook messages
        try {
            Log::info('WhatsApp webhook received', $request->all());

            // Verify webhook signature for POST requests
            $this->verifyWebhook($request);

            $messages = $request->input('entry.0.changes.0.value.messages', []);
            
            foreach ($messages as $message) {
                $this->processMessage($message);
            }

            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process individual WhatsApp message and extract order
     */
    private function processMessage(array $message): void
    {
        $phoneNumber = $message['from'] ?? null;
        $messageBody = $message['text']['body'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$phoneNumber || !$messageBody) {
            return;
        }

        // Find merchant by WhatsApp phone number
        $merchant = Merchant::where('whatsapp_business_phone', $phoneNumber)
                           ->where('account_status', 'active')
                           ->first();

        if (!$merchant) {
            $this->sendWhatsAppMessage($phoneNumber, "Sorry, your business is not registered with Delish. Please contact us to set up your account.");
            return;
        }

        // Parse order from message
        $orderData = $this->parseOrderFromMessage($messageBody);
        
        if (!$orderData) {
            $this->sendWhatsAppMessage($phoneNumber, "I couldn't understand your order. Please format like:\nOrder:\n- Chocolate Cake x2\n- Vanilla Cupcakes x12\n\nDelivery: Tomorrow 2PM\nAddress: [Your address]");
            return;
        }

        // Process the order
        $result = $this->orderProcessingService->processWhatsAppOrder($merchant, $orderData, $messageId);
        
        if ($result['success']) {
            $this->sendOrderConfirmation($phoneNumber, $result['order']);
        } else {
            $this->sendWhatsAppMessage($phoneNumber, $result['message']);
        }
    }

    /**
     * Smart order parsing from natural language text
     */
    private function parseOrderFromMessage(string $message): ?array
    {
        $message = strtolower($message);
        
        // Extract order items using regex patterns
        $items = [];
        $lines = explode("\n", $message);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Pattern: "chocolate cake x2", "vanilla cupcakes x12", "tiramisu - 5"
            if (preg_match('/^[-•\*]?\s*(.+?)\s*[x\-]\s*(\d+)$/i', $line, $matches)) {
                $productName = trim($matches[1]);
                $quantity = (int)$matches[2];
                
                // Try to find matching recipe
                $recipe = $this->findRecipeByName($productName);
                if ($recipe) {
                    $items[] = [
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->recipe_name,
                        'quantity' => $quantity
                    ];
                }
            }
        }

        if (empty($items)) {
            return null;
        }

        // Extract delivery information
        $deliveryDate = $this->extractDeliveryDate($message);
        $deliveryAddress = $this->extractDeliveryAddress($message);

        return [
            'items' => $items,
            'delivery_date' => $deliveryDate,
            'delivery_address' => $deliveryAddress,
            'special_notes' => $this->extractSpecialNotes($message)
        ];
    }

    /**
     * Find recipe by fuzzy name matching
     */
    private function findRecipeByName(string $productName): ?Recipe
    {
        // First try exact match
        $recipe = Recipe::where('active_status', true)
                       ->whereRaw('LOWER(recipe_name) = ?', [strtolower($productName)])
                       ->first();
        
        if ($recipe) {
            return $recipe;
        }

        // Try partial match
        $recipe = Recipe::where('active_status', true)
                       ->whereRaw('LOWER(recipe_name) LIKE ?', ['%' . strtolower($productName) . '%'])
                       ->first();

        return $recipe;
    }

    /**
     * Extract delivery date from message
     */
    private function extractDeliveryDate(string $message): ?string
    {
        // Look for date patterns like "tomorrow", "today", "monday", "25/12", etc.
        if (preg_match('/delivery[:\s]*(.+?)(?:\n|$)/i', $message, $matches)) {
            $dateStr = trim($matches[1]);
            
            if (stripos($dateStr, 'today') !== false) {
                return now()->format('Y-m-d');
            }
            
            if (stripos($dateStr, 'tomorrow') !== false) {
                return now()->addDay()->format('Y-m-d');
            }
            
            // Try to parse other date formats
            try {
                return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return now()->addDay()->format('Y-m-d'); // Default to tomorrow
            }
        }
        
        return now()->addDay()->format('Y-m-d'); // Default to tomorrow
    }

    /**
     * Extract delivery address from message
     */
    private function extractDeliveryAddress(string $message): ?string
    {
        if (preg_match('/address[:\s]*(.+?)(?:\n|$)/i', $message, $matches)) {
            return trim($matches[1]);
        }
        
        return null; // Will use merchant's default address
    }

    /**
     * Extract special notes from message
     */
    private function extractSpecialNotes(string $message): ?string
    {
        if (preg_match('/notes?[:\s]*(.+?)(?:\n|$)/i', $message, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Send order confirmation to merchant
     */
    private function sendOrderConfirmation(string $phoneNumber, Order $order): void
    {
        $message = "✅ Order Confirmed!\n\n";
        $message .= "Order #: {$order->id}\n";
        $message .= "Total: $" . number_format($order->total_amount, 2) . "\n\n";
        
        $message .= "Items:\n";
        foreach ($order->order_items as $item) {
            $message .= "• {$item['recipe_name']} x{$item['quantity']}\n";
        }
        
        $message .= "\nDelivery: {$order->requested_delivery_date->format('M j, Y')}\n";
        
        if ($order->job_ticket) {
            $message .= "Production started - Job Ticket #{$order->job_ticket->job_ticket_number}\n";
        }
        
        $message .= "\nThank you for your order! 🍰";
        
        $this->sendWhatsAppMessage($phoneNumber, $message);
    }

    /**
     * Send WhatsApp message (integrate with your WhatsApp Business API)
     */
    private function sendWhatsAppMessage(string $phoneNumber, string $message): void
    {
        // TODO: Integrate with WhatsApp Business API
        // For now, just log the message
        Log::info("WhatsApp message to {$phoneNumber}: {$message}");
        
        // In production, you would call WhatsApp API here:
        /*
        $response = Http::withToken(config('services.whatsapp.token'))
            ->post('https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'text' => ['body' => $message]
            ]);
        */
    }

    /**
     * Verify WhatsApp webhook signature
     */
    private function verifyWebhook(Request $request): void
    {
        // For development, skip verification
        if (app()->environment('local')) {
            return;
        }

        // In production, verify the webhook signature
        $signature = $request->header('X-Hub-Signature-256');
        
        // Skip verification if no signature header is provided (for testing)
        if (!$signature) {
            Log::warning('WhatsApp webhook called without signature header');
            return;
        }
        
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, config('services.whatsapp.webhook_secret'));
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }
    }

}