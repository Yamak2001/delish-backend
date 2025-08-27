# Delish ERP - Controller Implementation Plan

## 📋 Document Overview
**Date:** August 26, 2025  
**Status:** Implementation Planning Phase  
**Purpose:** Document current controller status and detailed implementation plans

---

## 🎯 Controller Status Overview

### ✅ **Implemented & Working Controllers**
- **AuthController** - JWT authentication, user management ✅
- **MerchantController** - Merchant CRUD, analytics ✅
- **SupplierController** - Supplier management, performance tracking ✅
- **PurchaseOrderController** - PO management, approval workflow ✅
- **DeliveryController** - Delivery tracking, GPS integration ✅
- **WhatsAppController** - Webhook integration ✅

### ❌ **Empty Controllers (Need Full Implementation)**
- **WorkflowController** - Workflow template management
- **RecipeController** - Recipe & ingredient management
- **InventoryController** - Stock tracking & management  
- **OrderController** - Order processing & workflow integration
- **JobTicketController** - Production task management
- **InvoiceController** - Billing & financial tracking

---

## 📋 Detailed Controller Implementation Plans

### **1. WorkflowController**

#### **Current State:**
```php
// Completely empty - only has method stubs
class WorkflowController extends Controller
{
    public function index() { // }
    public function store(Request $request) { // }
    // ... all methods empty
}
```

#### **Department-Oriented Implementation Plan:**

```php
class WorkflowController extends Controller
{
    // Dashboard for workflow management
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        $stats = [
            'total_workflows' => Workflow::count(),
            'active_workflows' => Workflow::where('active_status', true)->count(),
            'workflows_by_department' => $this->getWorkflowsByDepartment(),
            'average_completion_time' => $this->getAverageCompletionTime(),
        ];
        
        return response()->json(['success' => true, 'data' => $stats]);
    }
    
    // Department-specific workflow listing
    public function indexForDepartment(): JsonResponse
    {
        $userDepartment = auth()->user()->department;
        
        $workflows = Workflow::whereHas('steps', function($q) use ($userDepartment) {
            $q->where('required_department', $userDepartment);
        })->with(['steps' => function($q) use ($userDepartment) {
            $q->where('required_department', $userDepartment);
        }])->paginate();
        
        return response()->json(['success' => true, 'data' => $workflows]);
    }
    
    // Create workflow template with steps
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_name' => 'required|string|max:255',
            'description' => 'required|string',
            'workflow_type' => 'required|in:production,quality_control,packaging,delivery',
            'steps' => 'required|array|min:1',
            'steps.*.step_name' => 'required|string',
            'steps.*.assigned_role' => 'required|string',
            'steps.*.required_department' => 'required|string',
            'steps.*.estimated_duration_minutes' => 'required|integer|min:1',
        ]);
        
        DB::transaction(function () use ($validated) {
            $workflow = Workflow::create([
                'workflow_name' => $validated['workflow_name'],
                'description' => $validated['description'],
                'workflow_type' => $validated['workflow_type'],
                'active_status' => true,
                'created_by_user_id' => auth()->id(),
            ]);
            
            foreach ($validated['steps'] as $index => $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_number' => $index + 1,
                    'step_name' => $stepData['step_name'],
                    'assigned_role' => $stepData['assigned_role'],
                    'required_department' => $stepData['required_department'],
                    'estimated_duration_minutes' => $stepData['estimated_duration_minutes'],
                    'instructions' => $stepData['instructions'] ?? null,
                ]);
            }
            
            return $workflow;
        });
    }
    
    // Clone workflow template
    public function clone(Workflow $workflow): JsonResponse
    {
        // Clone workflow with all steps for different departments
    }
    
    // Workflow performance analytics
    public function analytics(Workflow $workflow): JsonResponse
    {
        // Performance metrics per step and department
    }
}
```

**Key Features:**
- **Department Filtering**: Only show steps relevant to user's department
- **Template Management**: Create reusable workflow templates
- **Performance Analytics**: Track completion times by department
- **Step Assignment Logic**: Auto-assign based on role and department

---

### **2. RecipeController**

#### **Current State:** Completely empty

#### **Department-Oriented Implementation Plan:**

```php
class RecipeController extends Controller
{
    // Dashboard with recipe analytics
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->department === 'kitchen') {
            return $this->kitchenDashboard();
        } elseif ($user->department === 'management') {
            return $this->managementDashboard();
        }
        
        return $this->generalDashboard();
    }
    
    // Kitchen staff view - focus on preparation
    private function kitchenDashboard(): JsonResponse
    {
        $stats = [
            'active_recipes' => Recipe::where('active_status', true)->count(),
            'prep_time_today' => $this->calculateTotalPrepTime(),
            'complex_recipes' => Recipe::whereHas('parentRecipes')->count(),
            'quality_issues' => $this->getQualityIssues(),
        ];
        
        $urgentRecipes = $this->getUrgentRecipes();
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'urgent_recipes' => $urgentRecipes,
                'prep_schedule' => $this->getTodayPrepSchedule(),
            ]
        ]);
    }
    
    // Management view - focus on costs and profitability
    private function managementDashboard(): JsonResponse
    {
        $stats = [
            'total_recipes' => Recipe::count(),
            'average_cost_per_unit' => Recipe::avg('cost_per_unit'),
            'most_profitable' => $this->getMostProfitableRecipes(),
            'cost_trends' => $this->getCostTrends(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'profitability_analysis' => $this->getProfitabilityAnalysis(),
                'ingredient_cost_breakdown' => $this->getIngredientCostBreakdown(),
            ]
        ]);
    }
    
    // Recipe listing with department-specific data
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Recipe::query();
        
        if ($user->department === 'kitchen') {
            // Kitchen staff needs prep instructions and ingredient details
            $query->with(['ingredients', 'preparationSteps', 'qualityCheckpoints']);
        } elseif ($user->department === 'management') {
            // Management needs cost analysis and profitability
            $query->with(['costBreakdown', 'profitabilityMetrics', 'salesHistory']);
        }
        
        // Handle nested recipes
        if ($request->get('include_components')) {
            $query->with(['childRecipes', 'parentRecipes']);
        }
        
        $recipes = $query->paginate($request->get('per_page', 15));
        
        return response()->json(['success' => true, 'data' => $recipes]);
    }
    
    // Create recipe with nested recipe support
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_name' => 'required|string|max:255',
            'description' => 'required|string',
            'preparation_time_minutes' => 'required|integer|min:1',
            'serving_size' => 'required|integer|min:1',
            'parent_recipe_id' => 'nullable|exists:recipes,id',
            'quantity_in_parent' => 'nullable|numeric|min:0.001',
            'unit_in_parent' => 'nullable|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'ingredients.*.quantity_required' => 'required|numeric|min:0.001',
            'preparation_steps' => 'required|array|min:1',
            'preparation_steps.*.step_number' => 'required|integer',
            'preparation_steps.*.instruction' => 'required|string',
            'preparation_steps.*.duration_minutes' => 'required|integer|min:1',
        ]);
        
        return DB::transaction(function () use ($validated) {
            $recipe = Recipe::create([
                'recipe_name' => $validated['recipe_name'],
                'description' => $validated['description'],
                'preparation_time_minutes' => $validated['preparation_time_minutes'],
                'serving_size' => $validated['serving_size'],
                'parent_recipe_id' => $validated['parent_recipe_id'],
                'quantity_in_parent' => $validated['quantity_in_parent'],
                'unit_in_parent' => $validated['unit_in_parent'],
                'active_status' => true,
            ]);
            
            // Add ingredients
            foreach ($validated['ingredients'] as $ingredient) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'inventory_item_id' => $ingredient['inventory_item_id'],
                    'quantity_required' => $ingredient['quantity_required'],
                    'unit_of_measurement' => $ingredient['unit_of_measurement'],
                ]);
            }
            
            // Add preparation steps
            foreach ($validated['preparation_steps'] as $step) {
                RecipePreparationStep::create([
                    'recipe_id' => $recipe->id,
                    'step_number' => $step['step_number'],
                    'instruction' => $step['instruction'],
                    'duration_minutes' => $step['duration_minutes'],
                    'required_equipment' => $step['required_equipment'] ?? null,
                ]);
            }
            
            // Calculate and update cost
            $totalCost = $this->calculateRecipeCost($recipe);
            $recipe->update(['cost_per_unit' => $totalCost]);
            
            return $recipe->load(['ingredients', 'preparationSteps']);
        });
    }
    
    // Calculate recipe cost including nested recipes
    private function calculateRecipeCost(Recipe $recipe): float
    {
        $totalCost = 0;
        
        foreach ($recipe->ingredients as $ingredient) {
            $totalCost += $ingredient->quantity_required * $ingredient->inventoryItem->cost_per_unit;
        }
        
        // Add costs from nested recipes
        foreach ($recipe->childRecipes as $childRecipe) {
            $childCost = $this->calculateRecipeCost($childRecipe);
            $totalCost += $childCost * $childRecipe->quantity_in_parent;
        }
        
        return $totalCost;
    }
}
```

**Key Features:**
- **Department-Specific Dashboards**: Kitchen vs Management views
- **Nested Recipe Support**: Handle recipes within recipes
- **Cost Calculation**: Automatic cost calculation with recipe hierarchy
- **Preparation Workflow**: Step-by-step instructions for kitchen staff

---

### **3. InventoryController**

#### **Current State:** Completely empty

#### **Implementation Plan:**

```php
class InventoryController extends Controller
{
    // Inventory dashboard with type-based analytics
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_items' => InventoryItem::count(),
            'low_stock_alerts' => InventoryItem::whereRaw('current_quantity <= minimum_stock_level')->count(),
            'items_by_type' => $this->getItemsByType(),
            'expiring_soon' => $this->getExpiringSoonItems(),
            'total_inventory_value' => $this->getTotalInventoryValue(),
        ];
        
        return response()->json(['success' => true, 'data' => $stats]);
    }
    
    // Inventory listing with type filtering
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query();
        
        // Filter by inventory type
        if ($request->has('type')) {
            $query->where('inventory_type', $request->type);
        }
        
        // Filter by low stock
        if ($request->boolean('low_stock_only')) {
            $query->whereRaw('current_quantity <= minimum_stock_level');
        }
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        
        $items = $query->with(['movements' => function($q) {
            $q->latest()->limit(5);
        }])->paginate($request->get('per_page', 15));
        
        return response()->json(['success' => true, 'data' => $items]);
    }
    
    // Create inventory item with type validation
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'inventory_type' => 'required|in:raw_material,recipe_component,finished_product,packaging,other',
            'current_quantity' => 'required|numeric|min:0',
            'unit_of_measurement' => 'required|string',
            'cost_per_unit' => 'required|numeric|min:0',
            'minimum_stock_level' => 'required|numeric|min:0',
            'maximum_stock_level' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'shelf_life_days' => 'nullable|integer|min:1',
            'storage_requirements' => 'nullable|string',
            'can_be_recipe_ingredient' => 'boolean',
            'requires_batch_tracking' => 'boolean',
        ]);
        
        $item = InventoryItem::create($validated + [
            'qr_code' => $this->generateQRCode(),
            'status' => 'active',
        ]);
        
        // Create initial stock movement
        InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'movement_type' => 'initial_stock',
            'quantity_change' => $validated['current_quantity'],
            'new_quantity' => $validated['current_quantity'],
            'performed_by_user_id' => auth()->id(),
            'notes' => 'Initial stock entry',
        ]);
        
        return response()->json(['success' => true, 'data' => $item], 201);
    }
    
    // Update inventory quantities with movement tracking
    public function updateQuantity(InventoryItem $item, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movement_type' => 'required|in:stock_in,stock_out,adjustment,waste,production_use',
            'quantity_change' => 'required|numeric',
            'notes' => 'nullable|string',
            'reference_id' => 'nullable|integer', // job_ticket_id, purchase_order_id, etc.
        ]);
        
        return DB::transaction(function () use ($item, $validated) {
            $newQuantity = $item->current_quantity + $validated['quantity_change'];
            
            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Current quantity: ' . $item->current_quantity
                ], 400);
            }
            
            // Update item quantity
            $item->update(['current_quantity' => $newQuantity]);
            
            // Record movement
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => $validated['movement_type'],
                'quantity_change' => $validated['quantity_change'],
                'new_quantity' => $newQuantity,
                'performed_by_user_id' => auth()->id(),
                'notes' => $validated['notes'],
                'reference_id' => $validated['reference_id'],
            ]);
            
            // Check for low stock alert
            if ($newQuantity <= $item->minimum_stock_level) {
                $this->triggerLowStockAlert($item);
            }
            
            return response()->json([
                'success' => true,
                'data' => $item->fresh(),
                'message' => 'Inventory updated successfully'
            ]);
        });
    }
    
    // FIFO stock deduction for production
    public function deductForProduction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_needed' => 'required|numeric|min:0.001',
            'job_ticket_id' => 'required|exists:job_tickets,id',
        ]);
        
        return DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $itemData) {
                $item = InventoryItem::find($itemData['inventory_item_id']);
                
                if ($item->current_quantity < $itemData['quantity_needed']) {
                    throw new \Exception("Insufficient stock for {$item->item_name}");
                }
                
                $this->updateQuantity($item, [
                    'movement_type' => 'production_use',
                    'quantity_change' => -$itemData['quantity_needed'],
                    'reference_id' => $validated['job_ticket_id'],
                    'notes' => 'Used in production - Job Ticket #' . $validated['job_ticket_id'],
                ]);
            }
            
            return response()->json(['success' => true, 'message' => 'Inventory deducted for production']);
        });
    }
}
```

**Key Features:**
- **Type-Based Management**: Handle raw materials, components, finished products
- **FIFO Logic**: First-in-first-out stock management
- **Movement Tracking**: Complete audit trail of all inventory changes
- **Low Stock Alerts**: Automatic notifications for reordering

---

### **4. OrderController**

#### **Current State:** Completely empty

#### **Implementation Plan:**

```php
class OrderController extends Controller
{
    protected OrderProcessingService $orderService;
    protected JobTicketService $jobTicketService;
    
    // Order dashboard with workflow integration
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->department === 'sales') {
            return $this->salesDashboard();
        } elseif ($user->department === 'kitchen') {
            return $this->kitchenDashboard();
        } elseif ($user->department === 'delivery') {
            return $this->deliveryDashboard();
        }
        
        return $this->generalDashboard();
    }
    
    // Kitchen dashboard - focus on production orders
    private function kitchenDashboard(): JsonResponse
    {
        $stats = [
            'orders_in_production' => Order::where('order_status', 'in_production')->count(),
            'orders_pending_quality' => $this->getOrdersPendingQuality(),
            'production_queue' => $this->getProductionQueue(),
            'estimated_completion_times' => $this->getEstimatedCompletionTimes(),
        ];
        
        return response()->json(['success' => true, 'data' => $stats]);
    }
    
    // Create order and auto-assign workflow
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'whatsapp_order_id' => 'nullable|string',
            'requested_delivery_date' => 'required|date|after:today',
            'special_notes' => 'nullable|string',
            'delivery_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.recipe_id' => 'required|exists:recipes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_notes' => 'nullable|string',
        ]);
        
        return DB::transaction(function () use ($validated) {
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $recipe = Recipe::find($item['recipe_id']);
                $totalAmount += $recipe->cost_per_unit * $item['quantity'];
            }
            
            // Create order
            $order = Order::create([
                'merchant_id' => $validated['merchant_id'],
                'whatsapp_order_id' => $validated['whatsapp_order_id'],
                'order_date' => now(),
                'requested_delivery_date' => $validated['requested_delivery_date'],
                'order_status' => 'pending',
                'total_amount' => $totalAmount,
                'special_notes' => $validated['special_notes'],
                'delivery_address' => $validated['delivery_address'],
            ]);
            
            // Create order items
            foreach ($validated['items'] as $itemData) {
                $recipe = Recipe::find($itemData['recipe_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'recipe_id' => $itemData['recipe_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $recipe->cost_per_unit,
                    'total_price' => $recipe->cost_per_unit * $itemData['quantity'],
                    'special_notes' => $itemData['special_notes'],
                ]);
            }
            
            // Auto-assign appropriate workflow
            $workflow = $this->determineWorkflow($order);
            $order->update(['assigned_workflow_id' => $workflow->id]);
            
            return $order->load(['items', 'merchant', 'assignedWorkflow']);
        });
    }
    
    // Confirm order and create job ticket
    public function confirm(Order $order): JsonResponse
    {
        if ($order->order_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be confirmed'
            ], 400);
        }
        
        return DB::transaction(function () use ($order) {
            // Update order status
            $order->update(['order_status' => 'confirmed']);
            
            // Create job ticket
            $jobTicket = $this->jobTicketService->createFromOrder($order);
            
            // Check inventory availability
            $availabilityCheck = $this->checkIngredientAvailability($order);
            
            if (!$availabilityCheck['sufficient']) {
                $order->update(['order_status' => 'pending_inventory']);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient inventory for order',
                    'missing_items' => $availabilityCheck['missing_items'],
                    'job_ticket' => $jobTicket,
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order->fresh(),
                    'job_ticket' => $jobTicket,
                ],
                'message' => 'Order confirmed and job ticket created'
            ]);
        });
    }
    
    // Department-specific order listing
    public function indexForDepartment(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Order::with(['items.recipe', 'merchant', 'assignedWorkflow']);
        
        switch ($user->department) {
            case 'kitchen':
                // Kitchen staff see production orders
                $query->whereIn('order_status', ['confirmed', 'in_production', 'quality_check']);
                break;
                
            case 'delivery':
                // Delivery staff see completed orders ready for delivery
                $query->whereIn('order_status', ['completed', 'out_for_delivery']);
                $query->with(['deliveryDetails', 'merchantLocation']);
                break;
                
            case 'sales':
                // Sales see all orders for customer management
                $query->with(['payments', 'invoices']);
                break;
        }
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }
        
        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }
        
        $orders = $query->orderBy('order_date', 'desc')
                       ->paginate($request->get('per_page', 15));
        
        return response()->json(['success' => true, 'data' => $orders]);
    }
    
    // Auto-determine workflow based on order characteristics
    private function determineWorkflow(Order $order): Workflow
    {
        $totalItems = $order->items->sum('quantity');
        $hasComplexRecipes = $order->items->whereIn('recipe_id', 
            Recipe::whereNotNull('parent_recipe_id')->pluck('id'))->count() > 0;
        
        // Rush orders get express workflow
        if ($order->requested_delivery_date->isToday() || 
            $order->requested_delivery_date->isTomorrow()) {
            return Workflow::where('workflow_type', 'express_production')->first();
        }
        
        // Large orders get bulk workflow
        if ($totalItems > 100) {
            return Workflow::where('workflow_type', 'bulk_production')->first();
        }
        
        // Complex recipes get detailed workflow
        if ($hasComplexRecipes) {
            return Workflow::where('workflow_type', 'detailed_production')->first();
        }
        
        // Default workflow
        return Workflow::where('workflow_type', 'standard_production')->first();
    }
}
```

**Key Features:**
- **Department-Specific Views**: Different dashboards for sales, kitchen, delivery
- **Workflow Auto-Assignment**: Smart workflow selection based on order characteristics
- **Inventory Integration**: Check availability before production
- **Job Ticket Creation**: Seamless integration with production workflow

---

### **5. JobTicketController**

#### **Current State:** Completely empty (but JobTicketService exists and is comprehensive)

#### **Implementation Plan:**

```php
class JobTicketController extends Controller
{
    protected JobTicketService $jobTicketService;
    
    // Department-specific job ticket dashboard
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        switch ($user->department) {
            case 'kitchen':
                return $this->kitchenDashboard();
            case 'quality_control':
                return $this->qualityControlDashboard();
            case 'packaging':
                return $this->packagingDashboard();
            default:
                return $this->generalDashboard();
        }
    }
    
    // Kitchen dashboard - active production tasks
    private function kitchenDashboard(): JsonResponse
    {
        $userId = auth()->id();
        
        $stats = [
            'my_active_tasks' => JobTicketStep::where('assigned_user_id', $userId)
                                             ->where('status', 'active')
                                             ->count(),
            'pending_assignments' => JobTicketStep::where('assigned_user_id', $userId)
                                                  ->where('status', 'pending')
                                                  ->count(),
            'completed_today' => JobTicketStep::where('completed_by_user_id', $userId)
                                              ->whereDate('completion_timestamp', today())
                                              ->count(),
            'average_step_time' => $this->getAverageStepTime($userId),
        ];
        
        // Get current tasks for this user
        $myTasks = JobTicketStep::where('assigned_user_id', $userId)
                                ->whereIn('status', ['pending', 'active'])
                                ->with(['jobTicket.order.merchant', 'jobTicket.workflow'])
                                ->orderBy('created_at')
                                ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'my_tasks' => $myTasks,
                'urgent_tasks' => $this->getUrgentTasks(),
            ]
        ]);
    }
    
    // Get job tickets filtered by department
    public function indexForDepartment(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = JobTicket::with(['order.merchant', 'workflow', 'currentStep', 'assignedUsers']);
        
        // Filter by current step department
        $query->whereHas('currentStep', function($q) use ($user) {
            $q->where('required_department', $user->department);
        });
        
        // Apply status filters
        if ($request->has('status')) {
            $query->where('current_status', $request->status);
        }
        
        // Apply priority filters
        if ($request->has('priority')) {
            $query->where('priority_level', $request->priority);
        }
        
        $jobTickets = $query->orderBy('priority_level', 'desc')
                           ->orderBy('estimated_completion_timestamp', 'asc')
                           ->paginate($request->get('per_page', 15));
        
        return response()->json(['success' => true, 'data' => $jobTickets]);
    }
    
    // Start working on a step
    public function startStep(JobTicketStep $step): JsonResponse
    {
        $user = auth()->user();
        
        if ($step->assigned_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this step'
            ], 403);
        }
        
        if ($step->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Step is not in pending status'
            ], 400);
        }
        
        $step->startStep($user);
        
        // Log the audit trail
        SystemAuditLog::create([
            'entity_type' => 'job_ticket_step',
            'entity_id' => $step->id,
            'field_name' => 'status',
            'old_value' => 'pending',
            'new_value' => 'active',
            'changed_by_user_id' => $user->id,
            'department' => $user->department,
            'change_reason' => 'Step started by user',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $step->fresh(),
            'message' => 'Step started successfully'
        ]);
    }
    
    // Complete a step and progress workflow
    public function completeStep(JobTicketStep $step, Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'quality_check_passed' => 'nullable|boolean',
            'quality_notes' => 'nullable|string',
            'time_spent_minutes' => 'nullable|integer|min:1',
        ]);
        
        if ($step->assigned_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this step'
            ], 403);
        }
        
        // Use JobTicketService to handle step completion and workflow progression
        $result = $this->jobTicketService->progressToNextStep(
            $step->jobTicket, 
            $user, 
            $validated
        );
        
        // Create audit log
        SystemAuditLog::create([
            'entity_type' => 'job_ticket_step',
            'entity_id' => $step->id,
            'field_name' => 'status',
            'old_value' => 'active',
            'new_value' => 'completed',
            'changed_by_user_id' => $user->id,
            'department' => $user->department,
            'change_reason' => 'Step completed by user',
        ]);
        
        return response()->json([
            'success' => $result['success'],
            'data' => $result,
            'message' => $result['success'] ? 'Step completed successfully' : $result['message']
        ]);
    }
    
    // Reassign step to another user
    public function reassignStep(JobTicketStep $step, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
        ]);
        
        $user = auth()->user();
        $newUser = User::find($validated['new_user_id']);
        
        // Verify new user has required role/department
        if ($newUser->department !== $step->required_department) {
            return response()->json([
                'success' => false,
                'message' => 'New user does not belong to required department'
            ], 400);
        }
        
        $oldUserId = $step->assigned_user_id;
        
        $step->update([
            'assigned_user_id' => $validated['new_user_id'],
            'notes' => ($step->notes ? $step->notes . ' | ' : '') . 
                      "Reassigned from User {$oldUserId} to User {$validated['new_user_id']}: {$validated['reason']}"
        ]);
        
        // Create audit log
        SystemAuditLog::create([
            'entity_type' => 'job_ticket_step',
            'entity_id' => $step->id,
            'field_name' => 'assigned_user_id',
            'old_value' => $oldUserId,
            'new_value' => $validated['new_user_id'],
            'changed_by_user_id' => $user->id,
            'department' => $user->department,
            'change_reason' => $validated['reason'],
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $step->fresh(),
            'message' => 'Step reassigned successfully'
        ]);
    }
    
    // Get step history and performance metrics
    public function stepAnalytics(JobTicketStep $step): JsonResponse
    {
        $analytics = [
            'step_history' => SystemAuditLog::where('entity_type', 'job_ticket_step')
                                          ->where('entity_id', $step->id)
                                          ->with('changedByUser')
                                          ->orderBy('created_at')
                                          ->get(),
            'average_completion_time' => $this->getAverageCompletionTimeForStep($step),
            'quality_metrics' => $this->getQualityMetricsForStep($step),
            'user_performance' => $this->getUserPerformanceForStep($step),
        ];
        
        return response()->json(['success' => true, 'data' => $analytics]);
    }
}
```

**Key Features:**
- **Department-Specific Dashboards**: Each department sees relevant tasks
- **Real-Time Task Management**: Start, complete, reassign steps
- **Comprehensive Audit Trail**: Track all changes with user attribution
- **Performance Analytics**: Track completion times and quality metrics

---

### **6. InvoiceController**

#### **Current State:** Completely empty

#### **Implementation Plan:**

```php
class InvoiceController extends Controller
{
    // Invoice dashboard with financial metrics
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->department === 'finance') {
            return $this->financeDashboard();
        } elseif ($user->department === 'management') {
            return $this->managementDashboard();
        }
        
        return $this->generalDashboard();
    }
    
    // Finance dashboard - focus on payments and outstanding amounts
    private function financeDashboard(): JsonResponse
    {
        $stats = [
            'total_invoices' => Invoice::count(),
            'unpaid_invoices' => Invoice::where('payment_status', 'unpaid')->count(),
            'overdue_invoices' => Invoice::where('payment_status', 'unpaid')
                                        ->where('due_date', '<', now())
                                        ->count(),
            'total_outstanding' => Invoice::where('payment_status', 'unpaid')->sum('total_amount'),
            'revenue_this_month' => Invoice::where('payment_status', 'paid')
                                          ->whereMonth('created_at', now()->month)
                                          ->sum('total_amount'),
        ];
        
        return response()->json(['success' => true, 'data' => $stats]);
    }
    
    // Auto-generate invoice from completed job ticket
    public function generateFromJobTicket(JobTicket $jobTicket): JsonResponse
    {
        if ($jobTicket->current_status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Can only generate invoice from completed job tickets'
            ], 400);
        }
        
        // Check if invoice already exists
        $existingInvoice = Invoice::where('related_job_ticket_id', $jobTicket->id)->first();
        if ($existingInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already exists for this job ticket',
                'data' => $existingInvoice
            ], 400);
        }
        
        return DB::transaction(function () use ($jobTicket) {
            $order = $jobTicket->order;
            $merchant = $order->merchant;
            
            // Calculate totals
            $subtotal = $order->items->sum('total_price');
            $taxAmount = $subtotal * 0.16; // 16% VAT in Jordan
            $totalAmount = $subtotal + $taxAmount;
            
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'merchant_id' => $merchant->id,
                'related_job_ticket_id' => $jobTicket->id,
                'order_id' => $order->id,
                'issue_date' => now(),
                'due_date' => now()->addDays($this->getDueDaysForMerchant($merchant)),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'unpaid',
                'currency' => 'JOD',
                'payment_terms' => $merchant->payment_terms,
                'created_by_user_id' => auth()->id(),
            ]);
            
            // Create invoice line items
            foreach ($order->items as $orderItem) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $orderItem->recipe->recipe_name,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'total_price' => $orderItem->total_price,
                    'recipe_id' => $orderItem->recipe_id,
                ]);
            }
            
            // Add production costs if applicable
            if ($jobTicket->total_production_cost > 0) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Production Costs',
                    'quantity' => 1,
                    'unit_price' => $jobTicket->total_production_cost,
                    'total_price' => $jobTicket->total_production_cost,
                ]);
            }
            
            // Create audit log
            SystemAuditLog::create([
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'field_name' => 'status',
                'old_value' => null,
                'new_value' => 'generated',
                'changed_by_user_id' => auth()->id(),
                'department' => auth()->user()->department,
                'change_reason' => "Invoice auto-generated from Job Ticket #{$jobTicket->job_ticket_number}",
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $invoice->load(['lineItems', 'merchant']),
                'message' => 'Invoice generated successfully'
            ]);
        });
    }
    
    // Record payment against invoice
    public function recordPayment(Invoice $invoice, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,card',
            'payment_reference' => 'nullable|string',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        return DB::transaction(function () use ($invoice, $validated) {
            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
                'amount' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'recorded_by_user_id' => auth()->id(),
            ]);
            
            // Calculate total payments
            $totalPaid = $invoice->payments->sum('amount') + $validated['amount_paid'];
            
            // Update invoice payment status
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->update([
                    'payment_status' => 'paid',
                    'paid_date' => $validated['payment_date'],
                ]);
                $paymentStatus = 'paid';
            } else {
                $invoice->update(['payment_status' => 'partial']);
                $paymentStatus = 'partial';
            }
            
            // Create audit log
            SystemAuditLog::create([
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'field_name' => 'payment_status',
                'old_value' => $invoice->payment_status,
                'new_value' => $paymentStatus,
                'changed_by_user_id' => auth()->id(),
                'department' => auth()->user()->department,
                'change_reason' => "Payment recorded: {$validated['amount_paid']} JOD",
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $invoice->fresh(),
                    'payment' => $payment,
                    'remaining_balance' => $invoice->total_amount - $totalPaid,
                ],
                'message' => 'Payment recorded successfully'
            ]);
        });
    }
    
    // Financial analytics and reporting
    public function financialAnalytics(Request $request): JsonResponse
    {
        $dateRange = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);
        
        $analytics = [
            'revenue_summary' => $this->getRevenueSummary($dateRange),
            'payment_trends' => $this->getPaymentTrends($dateRange),
            'merchant_performance' => $this->getMerchantPaymentPerformance($dateRange),
            'outstanding_receivables' => $this->getOutstandingReceivables(),
            'profitability_analysis' => $this->getProfitabilityAnalysis($dateRange),
        ];
        
        return response()->json(['success' => true, 'data' => $analytics]);
    }
}
```

**Key Features:**
- **Automated Invoice Generation**: From completed job tickets
- **Payment Tracking**: Record and track payments with references
- **Financial Analytics**: Revenue, trends, profitability analysis
- **Department-Specific Views**: Finance vs Management perspectives

---

## 📅 Implementation Timeline

### **Phase 1: Schema Updates & Core Controllers (4-5 weeks)**
- Week 1: Create new relational tables (workflow_steps, order_items, etc.)
- Week 2: Data migration from JSON to relational tables
- Week 3: Implement WorkflowController and RecipeController
- Week 4: Implement InventoryController with FIFO logic
- Week 5: Testing and bug fixes

### **Phase 2: Order Processing & Job Management (3-4 weeks)**
- Week 6: Implement OrderController with workflow integration
- Week 7: Implement JobTicketController with department views
- Week 8: Implement InvoiceController with automated generation
- Week 9: Integration testing across all controllers

### **Phase 3: Advanced Features & Optimization (2-3 weeks)**
- Week 10: Department-specific dashboards and analytics
- Week 11: Performance optimization and caching
- Week 12: Final testing and deployment preparation

---

## 🎯 Success Metrics

- **All 6 controllers fully implemented** with CRUD operations
- **Department-specific views** working for each user type
- **Workflow integration** between Order → JobTicket → Invoice
- **Complete audit trail** for all changes
- **Real-time updates** across departments
- **Performance benchmarks** met (sub-200ms API responses)

---

This plan will transform the empty controllers into a fully functional, department-oriented ERP system that leverages the excellent foundation already built while adding the missing functionality needed for production use.

*Last Updated: August 26, 2025*