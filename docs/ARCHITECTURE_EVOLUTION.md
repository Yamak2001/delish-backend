# Delish ERP - Architecture Evolution & Redesign Strategy

## 📋 Document Overview
**Date:** August 26, 2025  
**Status:** Architecture Planning Phase  
**Purpose:** Document current architectural challenges and proposed solutions for system evolution

---

## 🎯 Current System State

### ✅ **Strengths of Current Architecture**

1. **Excellent Workflow Engine Foundation**
   - Robust `JobTicketService` with comprehensive workflow automation
   - Department-based step assignment logic
   - Quality control integration with failure handling
   - Priority-based task allocation

2. **Well-Designed Core Models**
   - Proper Eloquent relationships
   - Good separation of concerns
   - Strong authentication system with role/department support
   - Comprehensive business entity modeling

3. **Working System Components**
   - Authentication (JWT with Laravel Passport)
   - Merchant, Supplier, Purchase Order, and Delivery management
   - Real-time tracking capabilities
   - Comprehensive API testing suite

### ❌ **Critical Architectural Issues**

#### **1. JSON Column Overuse**
Current problematic JSON implementations:

```php
// workflows table
'workflow_steps' => 'array'  // Should be relational table

// orders table  
'order_items' => 'array'     // Should be order_items table

// job_tickets table
'assigned_users' => 'array'  // Should be job_assignments table
```

**Problems:**
- **No Query Capability:** Cannot `WHERE` on JSON array elements
- **No Audit Trail:** Changes to JSON fields are not tracked
- **No Referential Integrity:** Can't foreign key to users inside JSON
- **Poor Performance:** JSON queries are slower and not indexable
- **Data Integrity:** No validation of JSON structure consistency

#### **2. Missing Controller Implementation**
Empty controllers preventing system functionality:
- `WorkflowController` - Workflow template management
- `RecipeController` - Recipe and ingredient management  
- `InventoryController` - Stock and ingredient tracking
- `OrderController` - Order processing and workflow integration
- `JobTicketController` - Production task management
- `InvoiceController` - Billing and cost tracking

#### **3. Recipe Hierarchy Complexity**
Current approach lacks nested recipe support:
- No way to have recipes as ingredients of other recipes
- Complex cost calculations for multi-level recipes
- Missing component recipe tracking

---

## 🔧 Proposed Architectural Solutions

### **1. Schema Redesign: JSON → Relational Tables**

#### **Replace `workflow_steps` JSON with relational structure:**

```php
// New table: workflow_steps
Schema::create('workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
    $table->integer('step_number');
    $table->string('step_name');
    $table->string('assigned_role');
    $table->string('required_department')->nullable();
    $table->string('step_type'); // 'preparation', 'quality_check', 'packaging', etc.
    $table->integer('estimated_duration_minutes');
    $table->json('step_parameters')->nullable(); // Only for truly dynamic data
    $table->boolean('quality_check_required')->default(false);
    $table->text('instructions')->nullable();
    $table->timestamps();
    
    $table->unique(['workflow_id', 'step_number']);
});
```

#### **Replace `order_items` JSON with proper table:**

```php
// New table: order_items  
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('recipe_id')->constrained();
    $table->decimal('quantity', 10, 3);
    $table->decimal('unit_price', 8, 2);
    $table->decimal('total_price', 10, 2);
    $table->text('special_notes')->nullable();
    $table->json('customizations')->nullable(); // Only for truly custom data
    $table->timestamps();
});
```

#### **Replace `assigned_users` JSON with assignment tracking:**

```php
// New table: job_assignments
Schema::create('job_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('job_ticket_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained();
    $table->integer('step_number');
    $table->enum('assignment_type', ['assigned', 'completed', 'transferred']);
    $table->timestamp('assigned_at');
    $table->timestamp('completed_at')->nullable();
    $table->foreignId('assigned_by_user_id')->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### **2. Audit Trail System**

#### **Universal Change Tracking Table:**

```php
// New table: system_audit_log
Schema::create('system_audit_log', function (Blueprint $table) {
    $table->id();
    $table->string('entity_type'); // 'job_ticket', 'order', 'workflow_step', etc.
    $table->unsignedBigInteger('entity_id');
    $table->string('field_name');
    $table->text('old_value')->nullable();
    $table->text('new_value')->nullable();
    $table->foreignId('changed_by_user_id')->constrained('users');
    $table->string('change_reason')->nullable();
    $table->string('department'); // Track which department made the change
    $table->timestamps();
    
    $table->index(['entity_type', 'entity_id']);
    $table->index('changed_by_user_id');
    $table->index('created_at');
});
```

**Benefits:**
- **Complete Audit Trail:** Every change tracked with user and timestamp
- **Department Visibility:** Know which department made what changes
- **Queryable History:** Can filter changes by user, entity, field, or department
- **Compliance Ready:** Full audit trail for business compliance

### **3. Recipe Hierarchy: Simple Column Approach**

Instead of complex pivot tables:

```php
// Add to existing recipes table
$table->foreignId('parent_recipe_id')->nullable()->constrained('recipes');
$table->decimal('quantity_in_parent', 10, 3)->nullable(); // How much of this recipe is used in parent
$table->string('unit_in_parent')->nullable(); // Unit measurement in parent recipe
```

**Benefits:**
- **Simple Queries:** `WHERE parent_recipe_id = ?` to find sub-recipes
- **Recursive Relationships:** Easy to build recipe trees
- **Cost Cascading:** Calculate costs up the hierarchy
- **Clear Relationships:** Obvious parent-child structure

### **4. Inventory Categorization Enhancement**

```php
// Update inventory_items table
$table->enum('inventory_type', ['raw_material', 'recipe_component', 'finished_product', 'packaging', 'other'])
      ->default('raw_material');
$table->boolean('can_be_recipe_ingredient')->default(true);
$table->boolean('requires_batch_tracking')->default(false);
```

---

## 🏗️ Implementation Strategy

### **Phase 1: Schema Migration & Data Restructuring** 

#### **Step 1: Create New Tables**
1. Create migration for `workflow_steps` table
2. Create migration for `order_items` table  
3. Create migration for `job_assignments` table
4. Create migration for `system_audit_log` table
5. Update `recipes` table with `parent_recipe_id`
6. Update `inventory_items` with type categorization

#### **Step 2: Data Migration**
1. **Workflow Steps Migration:**
   ```php
   // Migrate workflow_steps JSON to relational table
   foreach (Workflow::all() as $workflow) {
       foreach ($workflow->workflow_steps as $index => $step) {
           WorkflowStep::create([
               'workflow_id' => $workflow->id,
               'step_number' => $index + 1,
               'step_name' => $step['step_name'],
               'assigned_role' => $step['assigned_role'],
               // ... other fields
           ]);
       }
   }
   ```

2. **Order Items Migration:**
   ```php
   // Migrate order_items JSON to relational table
   foreach (Order::all() as $order) {
       foreach ($order->order_items as $item) {
           OrderItem::create([
               'order_id' => $order->id,
               'recipe_id' => $item['recipe_id'],
               'quantity' => $item['quantity'],
               // ... other fields
           ]);
       }
   }
   ```

#### **Step 3: Model Updates**
1. Update Eloquent models to use new relationships
2. Remove JSON casting from old columns
3. Add new relationship methods
4. Update existing services to use relational data

### **Phase 2: Controller Implementation**

#### **Department-Oriented Controllers**

1. **WorkflowController**
   ```php
   // Department-specific workflow views
   public function indexForDepartment(Request $request)
   {
       $userDepartment = auth()->user()->department;
       return Workflow::whereHas('steps', function($q) use ($userDepartment) {
           $q->where('required_department', $userDepartment);
       })->paginate();
   }
   ```

2. **RecipeController**
   ```php
   // Kitchen staff see preparation details
   // Management sees cost analysis
   public function show(Recipe $recipe, Request $request)
   {
       $user = auth()->user();
       
       if ($user->department === 'kitchen') {
           return $recipe->load(['ingredients', 'preparationSteps']);
       }
       
       if ($user->department === 'management') {
           return $recipe->load(['ingredients', 'costAnalysis', 'profitability']);
       }
   }
   ```

3. **JobTicketController**
   ```php
   // Department-filtered job tickets
   public function dashboardForDepartment()
   {
       $user = auth()->user();
       
       return JobTicket::whereHas('currentStep', function($q) use ($user) {
           $q->where('required_department', $user->department);
       })->with(['order', 'currentStep', 'assignedUsers'])
         ->orderBy('priority_level', 'desc')
         ->paginate();
   }
   ```

### **Phase 3: Advanced Features**

1. **Real-time Department Notifications**
2. **Advanced Analytics & Reporting**
3. **Mobile App Department Views**
4. **Performance Optimization**

---

## 📊 Benefits of New Architecture

### **1. Data Integrity & Performance**
- **Proper Foreign Keys:** Referential integrity enforced
- **Indexable Queries:** Fast lookups on step assignments, order items
- **Query Optimization:** Join queries instead of JSON parsing
- **ACID Compliance:** Proper transaction support

### **2. Audit & Compliance**
- **Complete Change History:** Every field change tracked
- **User Attribution:** Know who changed what when
- **Department Visibility:** Track cross-department interactions
- **Regulatory Compliance:** Full audit trail for food safety regulations

### **3. Developer Experience**
- **Eloquent Relationships:** Proper Laravel ORM usage
- **Type Safety:** No more array access on undefined JSON keys
- **IDE Support:** Auto-completion and error detection
- **Testability:** Easier to write unit tests with relational data

### **4. Business Intelligence**
- **Advanced Reporting:** Query individual steps, assignments, changes
- **Performance Metrics:** Track department efficiency, user productivity
- **Cost Analysis:** Detailed ingredient and labor cost tracking
- **Predictive Analytics:** Use historical data for forecasting

---

## ⚠️ Migration Risks & Mitigation

### **Risks:**
1. **Data Loss:** JSON migration could lose data if not careful
2. **Downtime:** Schema changes require application downtime
3. **Performance:** Initial migration could be slow with large datasets
4. **Breaking Changes:** Existing API endpoints might break

### **Mitigation Strategies:**
1. **Backup Everything:** Full database backup before migration
2. **Gradual Migration:** Migrate one table at a time
3. **Parallel Development:** Keep old and new systems running temporarily
4. **Comprehensive Testing:** Full test suite before deployment
5. **Rollback Plan:** Ability to revert to previous schema if needed

---

## 🎯 Success Metrics

### **Technical Metrics:**
- Query performance improvement (target: 50%+ faster)
- API response time reduction
- Database storage optimization
- Reduced N+1 query problems

### **Business Metrics:**
- Department efficiency tracking
- Order processing time reduction
- Error rate decrease in production workflows
- User satisfaction with department-specific views

---

## 📅 Timeline Estimate

- **Phase 1 (Schema & Migration):** 2-3 weeks
- **Phase 2 (Controller Implementation):** 3-4 weeks  
- **Phase 3 (Advanced Features):** 2-3 weeks
- **Testing & Deployment:** 1-2 weeks

**Total Estimated Time:** 8-12 weeks for complete migration

---

## 💡 Conclusion

This architectural evolution will transform the Delish ERP system from a partially functional prototype into a production-ready, enterprise-grade system. By eliminating JSON column antipatterns and implementing proper relational design, we'll achieve:

- **Better Performance:** Faster queries and indexable data
- **Stronger Data Integrity:** Foreign key constraints and proper relationships  
- **Complete Audit Trail:** Track every change with user attribution
- **Department Efficiency:** Role-based views and workflows
- **Maintainable Codebase:** Standard Laravel patterns and practices

The investment in this architectural refactoring will pay dividends in system performance, maintainability, and business intelligence capabilities.

---

*This document serves as our architectural roadmap and will be updated as implementation progresses.*