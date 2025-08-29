<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Recipe;
use App\Services\OrderProcessingService;
use App\Services\WhatsAppMediaService;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WhatsAppController extends Controller
{
    private OrderProcessingService $orderProcessingService;
    private WhatsAppMediaService $whatsappMediaService;
    private ConversationService $conversationService;

    public function __construct(
        OrderProcessingService $orderProcessingService,
        WhatsAppMediaService $whatsappMediaService,
        ConversationService $conversationService
    ) {
        $this->orderProcessingService = $orderProcessingService;
        $this->whatsappMediaService = $whatsappMediaService;
        $this->conversationService = $conversationService;
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

            // Forward webhook to messaging microservice for storage
            $this->conversationService->forwardWebhook($request->all());

            // Handle different webhook types
            $entry = $request->input('entry.0', []);
            $changes = $entry['changes'] ?? [];
            
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                
                // Handle incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->processMessage($message);
                    }
                }
                
                // Handle message status updates
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->processMessageStatus($status);
                    }
                }
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
     * Process individual WhatsApp message based on type
     */
    private function processMessage(array $message): void
    {
        $phoneNumber = $message['from'] ?? null;
        $messageType = $message['type'] ?? 'unknown';
        $messageId = $message['id'] ?? null;
        $timestamp = $message['timestamp'] ?? null;

        if (!$phoneNumber) {
            Log::warning('Message received without phone number', $message);
            return;
        }

        // Find merchant by WhatsApp phone number
        $merchant = Merchant::where('whatsapp_business_phone', $phoneNumber)
                           ->where('account_status', 'active')
                           ->first();

        if (!$merchant) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "Sorry, your business is not registered with Delish. Please contact us to set up your account. 📞");
            return;
        }

        Log::info('Processing WhatsApp message', [
            'merchant_id' => $merchant->id,
            'phone' => $phoneNumber,
            'type' => $messageType,
            'message_id' => $messageId
        ]);

        // Route message based on type
        switch ($messageType) {
            case 'text':
                $this->handleTextMessage($merchant, $message);
                break;
            case 'image':
                $this->handleImageMessage($merchant, $message);
                break;
            case 'video':
                $this->handleVideoMessage($merchant, $message);
                break;
            case 'audio':
            case 'voice':
                $this->handleAudioMessage($merchant, $message);
                break;
            case 'document':
                $this->handleDocumentMessage($merchant, $message);
                break;
            case 'location':
                $this->handleLocationMessage($merchant, $message);
                break;
            case 'interactive':
                $this->handleInteractiveMessage($merchant, $message);
                break;
            case 'order':
                $this->handleCatalogOrderMessage($merchant, $message);
                break;
            case 'button':
                $this->handleButtonMessage($merchant, $message);
                break;
            case 'list':
                $this->handleListMessage($merchant, $message);
                break;
            case 'contacts':
                $this->handleContactMessage($merchant, $message);
                break;
            default:
                $this->whatsappMediaService->sendMessage($phoneNumber, "Sorry, I don't understand that type of message yet. Please send a text message with your order. 📝");
                Log::warning('Unhandled message type', ['type' => $messageType, 'message' => $message]);
        }
    }

    /**
     * Handle text messages (original order parsing logic)
     */
    private function handleTextMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $messageBody = $message['text']['body'] ?? '';
        $messageId = $message['id'];

        // Check for command keywords first
        $lowerBody = strtolower($messageBody);
        
        if (str_contains($lowerBody, 'catalog') || str_contains($lowerBody, 'menu') || str_contains($lowerBody, 'browse')) {
            $this->sendCatalogMenu($phoneNumber);
            return;
        }
        
        if (str_contains($lowerBody, 'help') || $messageBody === '?') {
            $this->sendHelpMessage($phoneNumber);
            return;
        }
        
        if (str_contains($lowerBody, 'status') || str_contains($lowerBody, 'order status')) {
            $this->sendOrderStatus($merchant, $phoneNumber);
            return;
        }

        // Try to parse as an order
        $orderData = $this->parseOrderFromMessage($messageBody);
        
        if (!$orderData) {
            $this->whatsappMediaService->sendMessage($phoneNumber, 
                "I couldn't understand your order. Try these options:\n\n" .
                "📝 Text your order like:\n" .
                "- Chocolate Cake x2\n" .
                "- Vanilla Cupcakes x12\n" .
                "Delivery: Tomorrow 2PM\n\n" .
                "🛍️ Type 'catalog' to browse products\n" .
                "❓ Type 'help' for more options"
            );
            return;
        }

        // Process the order
        $result = $this->orderProcessingService->processWhatsAppOrder($merchant, $orderData, $messageId);
        
        if ($result['success']) {
            $this->sendOrderConfirmation($phoneNumber, $result['order']);
        } else {
            $this->whatsappMediaService->sendMessage($phoneNumber, "❌ " . $result['message']);
        }
    }

    /**
     * Handle image messages (could be menu photos, receipts, etc.)
     */
    private function handleImageMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $imageData = $message['image'] ?? [];
        $caption = $imageData['caption'] ?? '';
        $mediaId = $imageData['id'] ?? null;

        if (!$mediaId) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "I received your image but couldn't process it. Please try again. 📷");
            return;
        }

        // Download the image
        $downloadResult = $this->whatsappMediaService->downloadMedia($mediaId, 'image');
        
        if (!$downloadResult) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "Sorry, I couldn't download your image. Please try sending it again. 📷❌");
            return;
        }

        Log::info('Image received from merchant', [
            'merchant_id' => $merchant->id,
            'media_id' => $mediaId,
            'filename' => $downloadResult['filename'],
            'caption' => $caption
        ]);

        // If there's a caption, try to process it as an order
        if (!empty($caption)) {
            $orderData = $this->parseOrderFromMessage($caption);
            if ($orderData) {
                $result = $this->orderProcessingService->processWhatsAppOrder($merchant, $orderData, $message['id']);
                
                if ($result['success']) {
                    $this->sendOrderConfirmation($phoneNumber, $result['order']);
                } else {
                    $this->whatsappMediaService->sendMessage($phoneNumber, "❌ " . $result['message']);
                }
                return;
            }
        }

        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "📷 I received your image! If this contains an order, please send the details as text:\n\n" .
            "Example:\n- Chocolate Cake x2\n- Cupcakes x12\nDelivery: Tomorrow"
        );
    }

    /**
     * Handle video messages
     */
    private function handleVideoMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $videoData = $message['video'] ?? [];
        $caption = $videoData['caption'] ?? '';
        $mediaId = $videoData['id'] ?? null;

        if ($mediaId) {
            $downloadResult = $this->whatsappMediaService->downloadMedia($mediaId, 'video');
            if ($downloadResult) {
                Log::info('Video received from merchant', [
                    'merchant_id' => $merchant->id,
                    'media_id' => $mediaId,
                    'filename' => $downloadResult['filename']
                ]);
            }
        }

        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "🎥 Thanks for the video! For orders, please send text with your requirements:\n\n" .
            "Example: \n- Red Velvet Cake x1\n- Birthday decorations\nDelivery: Saturday"
        );
    }

    /**
     * Handle audio/voice messages
     */
    private function handleAudioMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $audioData = $message['audio'] ?? $message['voice'] ?? [];
        $mediaId = $audioData['id'] ?? null;

        if ($mediaId) {
            $downloadResult = $this->whatsappMediaService->downloadMedia($mediaId, 'audio');
            if ($downloadResult) {
                Log::info('Audio received from merchant', [
                    'merchant_id' => $merchant->id,
                    'media_id' => $mediaId,
                    'filename' => $downloadResult['filename']
                ]);
            }
        }

        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "🔊 I received your voice message! For now, please send your order as text:\n\n" .
            "Example:\n- Chocolate Brownies x6\n- Custom message on cake\nDelivery: Tomorrow 3PM"
        );
    }

    /**
     * Handle document messages (PDFs, receipts, order forms)
     */
    private function handleDocumentMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $docData = $message['document'] ?? [];
        $filename = $docData['filename'] ?? 'document';
        $mediaId = $docData['id'] ?? null;

        if ($mediaId) {
            $downloadResult = $this->whatsappMediaService->downloadMedia($mediaId, 'document');
            if ($downloadResult) {
                Log::info('Document received from merchant', [
                    'merchant_id' => $merchant->id,
                    'media_id' => $mediaId,
                    'original_filename' => $filename,
                    'saved_filename' => $downloadResult['filename']
                ]);
            }
        }

        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "📄 Thanks for the document '{$filename}'! \n\n" .
            "For orders, please also send text details:\n" .
            "- Product names and quantities\n" .
            "- Delivery date and time\n" .
            "- Special requirements"
        );
    }

    /**
     * Handle location messages
     */
    private function handleLocationMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $locationData = $message['location'] ?? [];
        $latitude = $locationData['latitude'] ?? null;
        $longitude = $locationData['longitude'] ?? null;
        $address = $locationData['address'] ?? 'Unknown location';
        $name = $locationData['name'] ?? null;

        Log::info('Location received from merchant', [
            'merchant_id' => $merchant->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
            'name' => $name
        ]);

        $locationText = $name ? "📍 Thanks for sharing '{$name}'!" : "📍 Thanks for sharing your location!";
        
        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "{$locationText}\n\n" .
            "Is this for delivery? Please send your order details:\n" .
            "- Products and quantities\n" .
            "- Delivery date/time\n\n" .
            "Address: {$address}"
        );
    }

    /**
     * Handle interactive messages (button/list responses)
     */
    private function handleInteractiveMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $interactive = $message['interactive'] ?? [];
        $type = $interactive['type'] ?? 'unknown';

        if ($type === 'button_reply') {
            $buttonReply = $interactive['button_reply'] ?? [];
            $buttonId = $buttonReply['id'] ?? '';
            $buttonTitle = $buttonReply['title'] ?? '';
            
            $this->handleButtonResponse($merchant, $phoneNumber, $buttonId, $buttonTitle);
            
        } elseif ($type === 'list_reply') {
            $listReply = $interactive['list_reply'] ?? [];
            $listId = $listReply['id'] ?? '';
            $listTitle = $listReply['title'] ?? '';
            
            $this->handleListResponse($merchant, $phoneNumber, $listId, $listTitle);
        }
    }

    /**
     * Handle catalog order messages (the big one!)
     */
    private function handleCatalogOrderMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $messageId = $message['id'];
        $orderData = $message['order'] ?? [];
        
        $catalogId = $orderData['catalog_id'] ?? null;
        $orderText = $orderData['text'] ?? '';
        $productItems = $orderData['product_items'] ?? [];

        if (empty($productItems)) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "❌ No products found in your order. Please try again.");
            return;
        }

        Log::info('Catalog order received', [
            'merchant_id' => $merchant->id,
            'catalog_id' => $catalogId,
            'product_count' => count($productItems),
            'order_text' => $orderText
        ]);

        // Convert catalog items to our internal format
        $orderItems = [];
        $totalAmount = 0;
        
        foreach ($productItems as $item) {
            $productId = $item['product_retailer_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;
            $itemPrice = $item['item_price'] ?? 0;
            $currency = $item['currency'] ?? 'USD';
            
            // Try to find matching recipe by product ID or name
            $recipe = Recipe::where('active_status', true)
                           ->where(function($query) use ($productId) {
                               $query->where('id', $productId)
                                     ->orWhere('recipe_code', $productId)
                                     ->orWhere('recipe_name', 'LIKE', '%' . $productId . '%');
                           })
                           ->first();
            
            if ($recipe) {
                $orderItems[] = [
                    'recipe_id' => $recipe->id,
                    'recipe_name' => $recipe->recipe_name,
                    'quantity' => $quantity,
                    'catalog_product_id' => $productId,
                    'catalog_price' => $itemPrice
                ];
                $totalAmount += ($itemPrice * $quantity);
            }
        }

        if (empty($orderItems)) {
            $this->whatsappMediaService->sendMessage($phoneNumber, 
                "❌ I couldn't match the catalog items to our recipes. Please contact us for assistance or send a text order."
            );
            return;
        }

        // Create order data
        $processedOrderData = [
            'items' => $orderItems,
            'delivery_date' => now()->addDay()->format('Y-m-d'), // Default tomorrow
            'delivery_address' => null, // Use merchant default
            'special_notes' => $orderText,
            'catalog_order' => true,
            'catalog_id' => $catalogId,
            'catalog_total' => $totalAmount
        ];

        // Process the order
        $result = $this->orderProcessingService->processWhatsAppOrder($merchant, $processedOrderData, $messageId);
        
        if ($result['success']) {
            $this->sendCatalogOrderConfirmation($phoneNumber, $result['order'], $productItems);
        } else {
            $this->whatsappMediaService->sendMessage($phoneNumber, "❌ " . $result['message']);
        }
    }

    /**
     * Handle contact messages
     */
    private function handleContactMessage(Merchant $merchant, array $message): void
    {
        $phoneNumber = $message['from'];
        $contacts = $message['contacts'] ?? [];
        
        if (!empty($contacts)) {
            $contact = $contacts[0];
            $contactName = $contact['name']['formatted_name'] ?? 'Unknown';
            $contactPhone = $contact['phones'][0]['phone'] ?? 'No phone';
            
            Log::info('Contact received from merchant', [
                'merchant_id' => $merchant->id,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone
            ]);
        }

        $this->whatsappMediaService->sendMessage($phoneNumber, 
            "👤 Thanks for sharing the contact! \n\n" .
            "For orders, please send:\n" .
            "- Product names and quantities\n" .
            "- Delivery details\n" .
            "- Any special requirements"
        );
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
     * Process message status updates (delivery receipts)
     */
    private function processMessageStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null; // sent, delivered, read, failed
        $timestamp = $status['timestamp'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;
        
        Log::info('WhatsApp message status update', [
            'message_id' => $messageId,
            'status' => $statusType,
            'timestamp' => $timestamp,
            'recipient' => $recipientId
        ]);
        
        // You could update message delivery status in database here
    }

    /**
     * Handle button responses from interactive messages
     */
    private function handleButtonResponse(Merchant $merchant, string $phoneNumber, string $buttonId, string $buttonTitle): void
    {
        if (str_starts_with($buttonId, 'cat_')) {
            $categoryId = str_replace('cat_', '', $buttonId);
            $this->sendCategoryProducts($phoneNumber, $categoryId);
        } elseif ($buttonId === 'help') {
            $this->sendHelpMessage($phoneNumber);
        } elseif ($buttonId === 'catalog') {
            $this->sendCatalogMenu($phoneNumber);
        } elseif ($buttonId === 'status') {
            $this->sendOrderStatus($merchant, $phoneNumber);
        } else {
            $this->whatsappMediaService->sendMessage($phoneNumber, "Button clicked: {$buttonTitle}");
        }
    }

    /**
     * Handle list responses from interactive messages
     */
    private function handleListResponse(Merchant $merchant, string $phoneNumber, string $listId, string $listTitle): void
    {
        if (str_starts_with($listId, 'prod_')) {
            $productId = str_replace('prod_', '', $listId);
            $this->sendProductDetails($phoneNumber, $productId);
        } else {
            $this->whatsappMediaService->sendMessage($phoneNumber, "Selected: {$listTitle}");
        }
    }

    /**
     * Send catalog menu with categories
     */
    private function sendCatalogMenu(string $phoneNumber): void
    {
        // Get active recipe categories (you might want to create a Category model)
        $categories = [
            ['id' => 1, 'name' => '🎂 Cakes'],
            ['id' => 2, 'name' => '🧁 Cupcakes'], 
            ['id' => 3, 'name' => '🍪 Cookies']
        ];
        
        $this->whatsappMediaService->sendCatalogMenu($phoneNumber, $categories);
    }

    /**
     * Send products for a specific category
     */
    private function sendCategoryProducts(string $phoneNumber, string $categoryId): void
    {
        $products = Recipe::where('active_status', true)
                         ->limit(10)
                         ->get()
                         ->map(function ($recipe) {
                             return [
                                 'id' => $recipe->id,
                                 'name' => $recipe->recipe_name,
                                 'description' => $recipe->recipe_description ?? 'Delicious ' . $recipe->recipe_name
                             ];
                         })
                         ->toArray();
        
        $categoryNames = ['1' => 'Cakes', '2' => 'Cupcakes', '3' => 'Cookies'];
        $categoryName = $categoryNames[$categoryId] ?? 'Products';
        
        $this->whatsappMediaService->sendProductList($phoneNumber, $products, $categoryName);
    }

    /**
     * Send product details
     */
    private function sendProductDetails(string $phoneNumber, string $productId): void
    {
        $recipe = Recipe::find($productId);
        
        if (!$recipe) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "Product not found.");
            return;
        }
        
        $message = "🍰 **{$recipe->recipe_name}**\n\n";
        if ($recipe->recipe_description) {
            $message .= "{$recipe->recipe_description}\n\n";
        }
        $message .= "To order, send: \n{$recipe->recipe_name} x[quantity]\n\n";
        $message .= "Or type 'catalog' to browse more products.";
        
        $this->whatsappMediaService->sendMessage($phoneNumber, $message);
    }

    /**
     * Send help message
     */
    private function sendHelpMessage(string $phoneNumber): void
    {
        $message = "🤖 **Delish Order Assistant**\n\n";
        $message .= "📝 **Text Orders:**\n";
        $message .= "- Chocolate Cake x2\n";
        $message .= "- Cupcakes x12\n";
        $message .= "Delivery: Tomorrow 3PM\n\n";
        $message .= "🛍️ **Commands:**\n";
        $message .= "• 'catalog' - Browse products\n";
        $message .= "• 'status' - Check order status\n";
        $message .= "• 'help' - Show this message\n\n";
        $message .= "📱 **Media:**\n";
        $message .= "Send photos with captions for visual orders!\n\n";
        $message .= "Need help? Contact us! 📞";
        
        $this->whatsappMediaService->sendMessage($phoneNumber, $message);
    }

    /**
     * Send order status for merchant
     */
    private function sendOrderStatus(Merchant $merchant, string $phoneNumber): void
    {
        $recentOrders = Order::where('merchant_id', $merchant->id)
                           ->orderBy('created_at', 'desc')
                           ->limit(3)
                           ->get();
        
        if ($recentOrders->isEmpty()) {
            $this->whatsappMediaService->sendMessage($phoneNumber, "No recent orders found. Place your first order!");
            return;
        }
        
        $message = "📋 **Your Recent Orders:**\n\n";
        
        foreach ($recentOrders as $order) {
            $status = match($order->order_status) {
                'pending' => '⏳ Pending',
                'confirmed' => '✅ Confirmed', 
                'in_production' => '👨‍🍳 In Production',
                'ready' => '📦 Ready',
                'delivered' => '🚚 Delivered',
                'cancelled' => '❌ Cancelled',
                default => '❓ Unknown'
            };
            
            $message .= "**Order #{$order->id}**\n";
            $message .= "Status: {$status}\n";
            $message .= "Total: $" . number_format($order->total_amount, 2) . "\n";
            $message .= "Date: " . $order->created_at->format('M j, Y') . "\n\n";
        }
        
        $this->whatsappMediaService->sendMessage($phoneNumber, $message);
    }

    /**
     * Send order confirmation to merchant
     */
    private function sendOrderConfirmation(string $phoneNumber, Order $order): void
    {
        $message = "✅ **Order Confirmed!**\n\n";
        $message .= "📋 Order #: {$order->id}\n";
        $message .= "💰 Total: $" . number_format($order->total_amount, 2) . "\n\n";
        
        $message .= "📝 **Items:**\n";
        foreach ($order->order_items as $item) {
            $message .= "• {$item['recipe_name']} x{$item['quantity']}\n";
        }
        
        $message .= "\n🚚 Delivery: {$order->requested_delivery_date->format('M j, Y')}\n";
        
        if ($order->jobTicket ?? false) {
            $message .= "🏭 Production started - Job Ticket #{$order->jobTicket->job_ticket_number}\n";
        }
        
        $message .= "\nThank you for your order! 🍰\n";
        $message .= "Type 'status' to check order progress.";
        
        $this->whatsappMediaService->sendMessage($phoneNumber, $message);
    }

    /**
     * Send catalog order confirmation with detailed breakdown
     */
    private function sendCatalogOrderConfirmation(string $phoneNumber, Order $order, array $catalogItems): void
    {
        $message = "✅ **Catalog Order Confirmed!**\n\n";
        $message .= "📋 Order #: {$order->id}\n";
        $message .= "💰 Total: $" . number_format($order->total_amount, 2) . "\n\n";
        
        $message .= "🛍️ **Catalog Items:**\n";
        foreach ($catalogItems as $item) {
            $productId = $item['product_retailer_id'] ?? 'Unknown';
            $quantity = $item['quantity'] ?? 1;
            $price = $item['item_price'] ?? 0;
            $message .= "• Product {$productId} x{$quantity} @ $" . number_format($price, 2) . "\n";
        }
        
        $message .= "\n🚚 Delivery: {$order->requested_delivery_date->format('M j, Y')}\n";
        
        if ($order->special_notes) {
            $message .= "📝 Notes: {$order->special_notes}\n";
        }
        
        $message .= "\n🏭 Your order is now in production!\n";
        $message .= "Type 'status' to track progress. 📱";
        
        $this->whatsappMediaService->sendMessage($phoneNumber, $message);
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