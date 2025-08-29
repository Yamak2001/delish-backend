<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    private ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Get all conversations
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'active');
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);

        $conversations = $this->conversationService->getConversations($status, $limit, $offset);

        return response()->json($conversations);
    }

    /**
     * Get single conversation with messages
     */
    public function show(int $id): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($id);

        if (!$conversation) {
            return response()->json([
                'error' => 'Conversation not found'
            ], 404);
        }

        return response()->json($conversation);
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|string',
            'type' => 'required|in:text,template,image,document',
            'content' => 'required_if:type,text|string',
            'templateName' => 'required_if:type,template|string',
            'templateVariables' => 'array',
            'mediaUrl' => 'required_if:type,image,document|url'
        ]);

        $result = $this->conversationService->sendMessage($request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'details' => $result['details'] ?? null
        ], 400);
    }

    /**
     * Get message history for a phone number
     */
    public function messageHistory(string $phone, Request $request): JsonResponse
    {
        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        $history = $this->conversationService->getMessageHistory($phone, $limit, $offset);

        return response()->json($history);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(int $conversationId): JsonResponse
    {
        $success = $this->conversationService->markMessagesAsRead($conversationId);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ]);
        }

        return response()->json([
            'error' => 'Failed to mark messages as read'
        ], 400);
    }

    /**
     * Update conversation status
     */
    public function updateStatus(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,archived,resolved'
        ]);

        $success = $this->conversationService->updateConversationStatus(
            $conversationId, 
            $request->input('status')
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Conversation status updated'
            ]);
        }

        return response()->json([
            'error' => 'Failed to update conversation status'
        ], 400);
    }

    /**
     * Search conversations
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:3'
        ]);

        $query = $request->query('q');
        $limit = $request->query('limit', 20);

        $results = $this->conversationService->searchConversations($query, $limit);

        return response()->json($results);
    }

    /**
     * Get conversation statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->conversationService->getConversationStats();

        return response()->json($stats);
    }
}