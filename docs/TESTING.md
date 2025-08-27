# Testing Documentation - Delish ERP System

## Overview

The Delish ERP system includes comprehensive testing strategies covering unit tests, integration tests, API tests, and end-to-end system tests. This document provides complete testing guidelines and procedures.

## Testing Strategy

### Testing Pyramid
```
           ┌─────────────┐
           │ E2E Tests   │ ← Few, High-level, Slow
           ├─────────────┤
           │ Integration │ ← Some, Medium-level
           │   Tests     │
           ├─────────────┤
           │ Unit Tests  │ ← Many, Fast, Isolated
           └─────────────┘
```

### Test Types
1. **Unit Tests** - Individual component testing
2. **Feature Tests** - API endpoint testing
3. **Integration Tests** - Module interaction testing
4. **Browser Tests** - End-to-end workflow testing
5. **Performance Tests** - Load and stress testing

## Test Environment Setup

### Prerequisites
```bash
# Install testing dependencies
composer install --dev
npm install --dev

# Setup test database
php artisan config:clear
php artisan migrate --env=testing --database=testing
```

### Environment Configuration

Create `.env.testing`:
```env
APP_ENV=testing
APP_KEY=base64:your-test-key
APP_DEBUG=true

# Test Database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
# Or use separate test MySQL database
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=delish_erp_test

# Disable external services in testing
MAIL_MAILER=log
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# Test-specific settings
JWT_TTL=60
BCRYPT_ROUNDS=4

# Disable external API calls
ENABLE_SMS_NOTIFICATIONS=false
ENABLE_EMAIL_NOTIFICATIONS=false
```

### Database Testing Setup

```php
// tests/TestCase.php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
        
        // Seed test data
        $this->artisan('db:seed', ['--class' => 'TestSeeder']);
    }
}
```

## Unit Testing

### Model Testing Examples

```php
// tests/Unit/Models/InventoryItemTest.php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\InventoryItem;
use App\Models\Supplier;

class InventoryItemTest extends TestCase
{
    public function test_inventory_item_can_be_created()
    {
        $supplier = Supplier::factory()->create();
        
        $item = InventoryItem::create([
            'sku' => 'TEST-001',
            'name' => 'Test Item',
            'category' => 'raw_materials',
            'unit_cost' => 10.50,
            'supplier_id' => $supplier->id
        ]);
        
        $this->assertDatabaseHas('inventory_items', [
            'sku' => 'TEST-001',
            'name' => 'Test Item'
        ]);
    }
    
    public function test_inventory_item_calculates_low_stock_correctly()
    {
        $item = InventoryItem::factory()->create([
            'current_stock' => 5,
            'reorder_point' => 10
        ]);
        
        $this->assertTrue($item->isLowStock());
    }
    
    public function test_inventory_item_belongs_to_supplier()
    {
        $supplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create(['supplier_id' => $supplier->id]);
        
        $this->assertInstanceOf(Supplier::class, $item->supplier);
        $this->assertEquals($supplier->id, $item->supplier->id);
    }
}
```

### Service Testing Examples

```php
// tests/Unit/Services/OrderServiceTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OrderService;
use App\Models\Order;
use App\Models\Recipe;

class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }
    
    public function test_can_calculate_order_total()
    {
        $order = Order::factory()->create();
        $recipe = Recipe::factory()->create(['cost_per_serving' => 25.00]);
        
        $order->items()->create([
            'recipe_id' => $recipe->id,
            'quantity' => 3,
            'unit_price' => 25.00,
            'total_price' => 75.00
        ]);
        
        $total = $this->orderService->calculateOrderTotal($order);
        
        $this->assertEquals(75.00, $total);
    }
    
    public function test_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        $updated = $this->orderService->updateStatus($order, 'confirmed');
        
        $this->assertTrue($updated);
        $this->assertEquals('confirmed', $order->fresh()->status);
    }
}
```

## Feature/API Testing

### Authentication Testing

```php
// tests/Feature/Auth/AuthenticationTest.php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);
        
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'token',
                    'user' => ['id', 'name', 'email', 'role']
                ]);
    }
    
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $response->assertStatus(401)
                ->assertJson(['success' => false]);
    }
    
    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
                        ->getJson('/api/user');
        
        $response->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'email' => $user->email
                ]);
    }
}
```

### Inventory Management Testing

```php
// tests/Feature/Inventory/InventoryManagementTest.php
<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Supplier;

class InventoryManagementTest extends TestCase
{
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }
    
    public function test_can_list_inventory_items()
    {
        InventoryItem::factory()->count(5)->create();
        
        $response = $this->actingAs($this->user, 'api')
                        ->getJson('/api/inventory');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'sku', 'name', 'category', 'current_stock']
                    ],
                    'meta' => ['current_page', 'total']
                ]);
    }
    
    public function test_can_create_inventory_item()
    {
        $supplier = Supplier::factory()->create();
        
        $itemData = [
            'sku' => 'NEW-001',
            'name' => 'New Test Item',
            'category' => 'raw_materials',
            'unit_cost' => 15.50,
            'reorder_point' => 20,
            'supplier_id' => $supplier->id
        ];
        
        $response = $this->actingAs($this->user, 'api')
                        ->postJson('/api/inventory', $itemData);
        
        $response->assertStatus(201)
                ->assertJson(['sku' => 'NEW-001']);
        
        $this->assertDatabaseHas('inventory_items', $itemData);
    }
    
    public function test_can_search_inventory_items()
    {
        InventoryItem::factory()->create(['name' => 'Premium Flour']);
        InventoryItem::factory()->create(['name' => 'Regular Sugar']);
        
        $response = $this->actingAs($this->user, 'api')
                        ->getJson('/api/inventory?search=flour');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
    
    public function test_can_get_low_stock_alerts()
    {
        InventoryItem::factory()->create([
            'current_stock' => 5,
            'reorder_point' => 10
        ]);
        
        $response = $this->actingAs($this->user, 'api')
                        ->getJson('/api/inventory/low-stock');
        
        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }
}
```

### Order Management Testing

```php
// tests/Feature/Orders/OrderManagementTest.php
<?php

namespace Tests\Feature\Orders;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Recipe;

class OrderManagementTest extends TestCase
{
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'manager']);
    }
    
    public function test_can_create_order_with_items()
    {
        $recipe = Recipe::factory()->create();
        
        $orderData = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@test.com',
            'delivery_date' => '2025-08-30',
            'items' => [
                [
                    'recipe_id' => $recipe->id,
                    'quantity' => 2,
                    'unit_price' => 25.00
                ]
            ]
        ];
        
        $response = $this->actingAs($this->user, 'api')
                        ->postJson('/api/orders', $orderData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id', 'order_number', 'customer_name', 'total_amount'
                ]);
        
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test Customer'
        ]);
        
        $this->assertDatabaseHas('order_items', [
            'recipe_id' => $recipe->id,
            'quantity' => 2
        ]);
    }
    
    public function test_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        $response = $this->actingAs($this->user, 'api')
                        ->putJson("/api/orders/{$order->id}/status", [
                            'status' => 'confirmed',
                            'notes' => 'Order confirmed'
                        ]);
        
        $response->assertStatus(200);
        
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);
    }
    
    public function test_validates_order_creation_data()
    {
        $response = $this->actingAs($this->user, 'api')
                        ->postJson('/api/orders', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_name']);
    }
}
```

### Delivery Tracking Testing

```php
// tests/Feature/Delivery/DeliveryTrackingTest.php
<?php

namespace Tests\Feature\Delivery;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Delivery;

class DeliveryTrackingTest extends TestCase
{
    public function test_can_create_delivery()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);
        $order = Order::factory()->create();
        
        $deliveryData = [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'vehicle_info' => 'Toyota Pickup - AA1234',
            'scheduled_pickup_time' => '2025-08-26T10:00:00Z',
            'delivery_address' => 'Test Address, Amman'
        ];
        
        $response = $this->actingAs($admin, 'api')
                        ->postJson('/api/deliveries', $deliveryData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id', 'delivery_number', 'status'
                ]);
    }
    
    public function test_can_update_gps_location()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $delivery = Delivery::factory()->create([
            'driver_id' => $driver->id,
            'status' => 'in_transit'
        ]);
        
        $locationData = [
            'latitude' => 31.9454,
            'longitude' => 35.9284
        ];
        
        $response = $this->actingAs($driver, 'api')
                        ->putJson("/api/deliveries/{$delivery->id}/location", $locationData);
        
        $response->assertStatus(200);
        
        $delivery->refresh();
        $this->assertEquals(31.9454, $delivery->current_latitude);
        $this->assertEquals(35.9284, $delivery->current_longitude);
    }
    
    public function test_can_track_delivery_status()
    {
        $delivery = Delivery::factory()->create([
            'status' => 'in_transit',
            'current_latitude' => 31.9454,
            'current_longitude' => 35.9284
        ]);
        
        $response = $this->getJson("/api/deliveries/{$delivery->id}/track");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'delivery_id',
                    'status',
                    'current_location' => ['latitude', 'longitude'],
                    'estimated_arrival'
                ]);
    }
}
```

## Integration Testing

### Supplier Management Integration

```php
// tests/Integration/SupplierPurchaseOrderTest.php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;

class SupplierPurchaseOrderTest extends TestCase
{
    public function test_complete_purchase_order_workflow()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create(['supplier_id' => $supplier->id]);
        
        // Create purchase order
        $poData = [
            'supplier_id' => $supplier->id,
            'expected_delivery_date' => '2025-08-30',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 100,
                    'unit_price' => 1.50
                ]
            ]
        ];
        
        $response = $this->actingAs($admin, 'api')
                        ->postJson('/api/purchase-orders', $poData);
        
        $po = PurchaseOrder::find($response->json('id'));
        
        // Update to sent status
        $this->actingAs($admin, 'api')
             ->putJson("/api/purchase-orders/{$po->id}/status", [
                 'status' => 'sent'
             ]);
        
        $po->refresh();
        $this->assertEquals('sent', $po->status);
        
        // Mark as received
        $this->actingAs($admin, 'api')
             ->putJson("/api/purchase-orders/{$po->id}/status", [
                 'status' => 'received'
             ]);
        
        $po->refresh();
        $this->assertEquals('received', $po->status);
        
        // Verify supplier balance updated
        $supplier->refresh();
        $this->assertEquals(150.00, $supplier->current_balance);
    }
}
```

## Performance Testing

### Load Testing Setup

```php
// tests/Performance/APILoadTest.php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class APILoadTest extends TestCase
{
    public function test_inventory_list_performance()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Create large dataset
        \App\Models\InventoryItem::factory()->count(1000)->create();
        
        $startTime = microtime(true);
        
        // Execute multiple requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user, 'api')
                            ->getJson('/api/inventory');
            $response->assertStatus(200);
        }
        
        $endTime = microtime(true);
        $averageTime = ($endTime - $startTime) / 10;
        
        // Assert response time is under 500ms
        $this->assertLessThan(0.5, $averageTime, 
            "Average response time should be under 500ms, got {$averageTime}s");
    }
    
    public function test_database_query_performance()
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $user = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($user, 'api')
                        ->getJson('/api/orders');
        
        $queries = DB::getQueryLog();
        
        // Assert no N+1 query problems
        $this->assertLessThan(10, count($queries),
            'Too many database queries executed');
    }
}
```

## Automated Testing Scripts

### Complete ERP System Test

The system includes comprehensive bash testing scripts:

#### 1. Basic System Test
```bash
# test-complete-erp-system.sh
#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"

echo "=== DELISH ERP COMPLETE SYSTEM TEST ==="

# Test all modules without authentication
test_endpoint() {
    local endpoint=$1
    local description=$2
    
    echo "Testing: $description"
    
    response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X GET "$BASE_URL$endpoint")
    http_code=$(echo $response | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    
    if [ $http_code -eq 200 ]; then
        echo "✅ $description - SUCCESS"
    else
        echo "❌ $description - FAILED (HTTP $http_code)"
    fi
}

# Test all modules
test_endpoint "/health" "System Health Check"
test_endpoint "/inventory" "Inventory Management"
test_endpoint "/recipes" "Recipe Management"
test_endpoint "/orders" "Order Management"
test_endpoint "/suppliers" "Supplier Management"
test_endpoint "/deliveries" "Delivery Management"
```

#### 2. Authenticated System Test
```bash
# test-authenticated-erp.sh
#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"

# Authenticate and get token
login_response=$(curl -s -X POST "$BASE_URL/login" \
    -H "Content-Type: application/json" \
    -d '{"email": "test@delish.com", "password": "testpass"}')

token=$(echo "$login_response" | jq -r '.token')

# Test with authentication
test_authenticated_endpoint() {
    local endpoint=$1
    local description=$2
    
    echo "Testing: $description"
    
    response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X GET "$BASE_URL$endpoint" \
        -H "Authorization: Bearer $token")
    
    http_code=$(echo $response | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    
    if [ $http_code -eq 200 ]; then
        echo "✅ $description - SUCCESS"
    else
        echo "❌ $description - FAILED (HTTP $http_code)"
    fi
}
```

## Continuous Integration

### GitHub Actions Workflow

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: delish_erp_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: pdo, pdo_mysql, mbstring, xml, curl, zip, gd, json
    
    - name: Install dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader
    
    - name: Copy environment file
      run: cp .env.testing .env
    
    - name: Generate application key
      run: php artisan key:generate
    
    - name: Run migrations
      run: php artisan migrate --force
    
    - name: Run tests
      run: php artisan test --coverage --min=80
    
    - name: Run API tests
      run: |
        php artisan serve &
        sleep 5
        ./test-complete-erp-system.sh
```

## Test Data Management

### Factory Definitions

```php
// database/factories/InventoryItemFactory.php
<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->paragraph,
            'category' => $this->faker->randomElement([
                'raw_materials', 'finished_goods', 'packaging'
            ]),
            'current_stock' => $this->faker->numberBetween(0, 1000),
            'unit_of_measure' => $this->faker->randomElement(['kg', 'liter', 'piece']),
            'unit_cost' => $this->faker->randomFloat(2, 1, 100),
            'reorder_point' => $this->faker->numberBetween(10, 100),
            'max_stock_level' => $this->faker->numberBetween(500, 2000),
            'supplier_id' => Supplier::factory(),
            'status' => 'active'
        ];
    }
    
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->numberBetween(1, 9),
            'reorder_point' => 10
        ]);
    }
}
```

### Test Seeders

```php
// database/seeders/TestSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
use App\Models\InventoryItem;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'role' => 'admin'
        ]);
        
        User::factory()->create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'role' => 'manager'
        ]);
        
        // Create test suppliers
        Supplier::factory()->count(5)->create();
        
        // Create test inventory
        InventoryItem::factory()->count(20)->create();
        InventoryItem::factory()->lowStock()->count(3)->create();
    }
}
```

## Test Commands

### Custom Artisan Commands for Testing

```php
// app/Console/Commands/RunSystemTests.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunSystemTests extends Command
{
    protected $signature = 'test:system {--module=all}';
    protected $description = 'Run comprehensive system tests';
    
    public function handle()
    {
        $module = $this->option('module');
        
        $this->info('Running Delish ERP System Tests...');
        
        match($module) {
            'inventory' => $this->testInventory(),
            'orders' => $this->testOrders(),
            'delivery' => $this->testDelivery(),
            'all' => $this->testAllModules(),
            default => $this->testAllModules()
        };
        
        $this->info('System tests completed!');
    }
    
    private function testAllModules()
    {
        $this->call('test', ['--testsuite' => 'Feature']);
        $this->runBashTest('test-complete-erp-system.sh');
        $this->runBashTest('test-authenticated-erp.sh');
    }
    
    private function runBashTest(string $script)
    {
        $this->info("Running {$script}...");
        $output = shell_exec("./{$script}");
        $this->line($output);
    }
}
```

## Testing Best Practices

### 1. Test Organization
- Group tests by feature/module
- Use descriptive test method names
- Keep tests focused and isolated
- Follow AAA pattern (Arrange, Act, Assert)

### 2. Data Management
- Use factories for test data
- Reset database between tests
- Avoid hard-coded IDs
- Use meaningful test data

### 3. API Testing
- Test both success and error scenarios
- Validate response structure
- Test authentication and authorization
- Check status codes and response format

### 4. Performance Guidelines
- Monitor test execution time
- Profile database queries
- Test with realistic data volumes
- Set performance benchmarks

### 5. Maintenance
- Update tests when code changes
- Remove obsolete tests
- Keep test documentation current
- Regular test suite review

## Running Tests

### Command Reference

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage --min=80

# Run specific test class
php artisan test --filter=InventoryManagementTest

# Run tests in parallel
php artisan test --parallel

# Run system tests
php artisan test:system

# Run bash API tests
./test-complete-erp-system.sh
./test-authenticated-erp.sh
./test-supplier-system.sh
```

### Test Reports

Tests generate comprehensive reports:

- **Unit Test Coverage**: HTML coverage report in `tests/coverage/`
- **API Test Results**: JSON results from bash scripts
- **Performance Metrics**: Response time measurements
- **Error Logs**: Detailed error information in `storage/logs/testing.log`

---

## Summary

The Delish ERP testing framework provides:

✅ **Comprehensive Coverage**: Unit, Feature, Integration, and E2E tests  
✅ **Automated Testing**: CI/CD integration with GitHub Actions  
✅ **Performance Testing**: Load testing and query optimization  
✅ **API Testing**: Complete REST API validation  
✅ **Real-world Testing**: Bash scripts for system validation  
✅ **Maintainable Tests**: Factory patterns and test organization  

**Total Test Coverage Target**: 80%+ code coverage  
**Performance Targets**: <500ms API response times  
**Test Execution Time**: <2 minutes for full suite  

*Last Updated: August 26, 2025*