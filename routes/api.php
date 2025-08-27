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
use App\Http\Controllers\API\SupplierController;
use App\Http\Controllers\API\PurchaseOrderController;
use App\Http\Controllers\API\DeliveryController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// WhatsApp webhook (public endpoint)
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp', [WhatsAppController::class, 'webhook']);
    Route::get('/whatsapp/verify', [WhatsAppController::class, 'verify']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'service' => 'Delish ERP Backend'
    ]);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Merchant Management
    Route::prefix('merchants')->controller(MerchantController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{merchant}', 'show');
        Route::put('/{merchant}', 'update');
        Route::delete('/{merchant}', 'destroy');
        Route::get('/{merchant}/analytics', 'analytics');
    });

    // Recipe Management
    Route::apiResource('recipes', RecipeController::class);

    // Inventory Management  
    Route::apiResource('inventory', InventoryController::class);

    // Order Management
    Route::apiResource('orders', OrderController::class);

    // Job Tickets Management
    Route::apiResource('job-tickets', JobTicketController::class);

    // Workflow Management
    Route::apiResource('workflows', WorkflowController::class);

    // Invoice Management
    Route::apiResource('invoices', InvoiceController::class);

    // Supplier Management
    Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {
        Route::get('/dashboard', 'dashboard');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{supplier}', 'show');
        Route::put('/{supplier}', 'update');
        Route::delete('/{supplier}', 'destroy');
        Route::get('/{supplier}/performance', 'performance');
        Route::patch('/{supplier}/rating', 'updateRating');
        Route::patch('/{supplier}/suspend', 'suspend');
        Route::patch('/{supplier}/activate', 'activate');
    });

    // Purchase Orders Management
    Route::prefix('purchase-orders')->controller(PurchaseOrderController::class)->group(function () {
        Route::get('/dashboard', 'dashboard');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{purchaseOrder}', 'show');
        Route::put('/{purchaseOrder}', 'update');
        Route::delete('/{purchaseOrder}', 'destroy');
        Route::patch('/{purchaseOrder}/status', 'updateStatus');
        Route::patch('/{purchaseOrder}/approve', 'approve');
        Route::patch('/{purchaseOrder}/send', 'sendToSupplier');
        Route::patch('/{purchaseOrder}/confirm', 'confirm');
        Route::patch('/{purchaseOrder}/cancel', 'cancel');
    });

    // Delivery Management
    Route::prefix('deliveries')->controller(DeliveryController::class)->group(function () {
        Route::get('/dashboard', 'dashboard');
        Route::get('/optimize-routes', 'optimizeRoutes');
        Route::get('/performance-report', 'deliveryPerformanceReport');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{delivery}', 'show');
        Route::put('/{delivery}', 'update');
        Route::delete('/{delivery}', 'destroy');
        Route::post('/{delivery}/assign-driver', 'assignDriver');
        Route::patch('/{delivery}/status', 'updateStatus');
        Route::get('/{delivery}/track', 'trackDelivery');
    });
});