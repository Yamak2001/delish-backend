<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    public function index(): JsonResponse
    {
        $merchants = Merchant::with(['orders', 'pricing', 'productTracking'])->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $merchants
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'location_address' => 'required|string',
            'contact_person_name' => 'required|string|max:255',
            'contact_phone' => 'required|string',
            'contact_email' => 'required|email',
            'payment_terms' => 'required|in:net_30,net_15,cash_on_delivery',
            'credit_limit' => 'required|numeric|min:0',
            'account_status' => 'in:active,inactive,suspended',
            'whatsapp_business_phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $merchant = Merchant::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Merchant created successfully',
            'data' => $merchant
        ], 201);
    }

    public function show(Merchant $merchant): JsonResponse
    {
        $merchant->load([
            'orders.jobTicket',
            'pricing.recipe',
            'productTracking.recipe',
            'wasteManagement',
            'invoices',
            'payments'
        ]);

        return response()->json([
            'success' => true,
            'data' => $merchant
        ]);
    }

    public function update(Request $request, Merchant $merchant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:255',
            'location_address' => 'sometimes|string',
            'contact_person_name' => 'sometimes|string|max:255',
            'contact_phone' => 'sometimes|string',
            'contact_email' => 'sometimes|email',
            'payment_terms' => 'sometimes|in:net_30,net_15,cash_on_delivery',
            'credit_limit' => 'sometimes|numeric|min:0',
            'account_status' => 'sometimes|in:active,inactive,suspended',
            'whatsapp_business_phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $merchant->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Merchant updated successfully',
            'data' => $merchant
        ]);
    }

    public function destroy(Merchant $merchant): JsonResponse
    {
        $merchant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Merchant deleted successfully'
        ]);
    }

    public function analytics(Merchant $merchant): JsonResponse
    {
        $analytics = [
            'total_orders' => $merchant->orders()->count(),
            'total_revenue' => $merchant->orders()->sum('total_amount'),
            'pending_orders' => $merchant->orders()->where('order_status', 'pending')->count(),
            'unpaid_invoices' => $merchant->invoices()->where('payment_status', 'unpaid')->sum('total_amount'),
            'recent_orders' => $merchant->orders()->latest()->take(5)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
}