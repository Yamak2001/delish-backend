<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MerchantController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\JobTicketController;
use App\Http\Controllers\API\WorkflowController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\WhatsAppController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// WhatsApp webhook (public endpoint)
Route::post('/webhooks/whatsapp', [WhatsAppController::class, 'webhook']);
Route::get('/webhooks/whatsapp/verify', [WhatsAppController::class, 'verify']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Merchants routes
    Route::apiResource('merchants', MerchantController::class);
    Route::get('/merchants/{merchant}/analytics', [MerchantController::class, 'analytics']);

    // Recipes routes
    Route::apiResource('recipes', RecipeController::class);

    // Inventory routes
    Route::apiResource('inventory', InventoryController::class);

    // Orders routes
    Route::apiResource('orders', OrderController::class);

    // Job Tickets routes
    Route::apiResource('job-tickets', JobTicketController::class);

    // Workflows routes
    Route::apiResource('workflows', WorkflowController::class);

    // Invoices routes
    Route::apiResource('invoices', InvoiceController::class);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'service' => 'Delish ERP Backend'
    ]);
});