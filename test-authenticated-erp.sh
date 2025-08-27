#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

BASE_URL="http://127.0.0.1:8000/api"

echo -e "${BLUE}=== DELISH ERP AUTHENTICATED SYSTEM TEST ===${NC}"
echo "Testing the complete ERP system with proper authentication..."
echo

# Get authentication token
echo -e "${YELLOW}🔐 Authenticating...${NC}"

# Create a test user if not exists
echo "Creating/verifying test user..."
php artisan tinker --execute="
try {
    \$user = App\Models\User::where('email', 'test@delish.com')->first();
    if (!\$user) {
        \$user = App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@delish.com',
            'password' => bcrypt('testpass'),
            'role' => 'admin',
            'department' => 'management',
            'phone_number' => '+962-6-555-TEST',
            'status' => 'active'
        ]);
        echo 'Test user created!';
    } else {
        echo 'Test user already exists!';
    }
} catch (Exception \$e) {
    echo 'User creation skipped: ' . \$e->getMessage();
}
" 2>/dev/null

# Get authentication token
login_response=$(curl -s -X POST "$BASE_URL/login" \
    -H "Content-Type: application/json" \
    -d '{"email": "test@delish.com", "password": "testpass"}')

# Check if login was successful and extract token
if echo "$login_response" | grep -q '"success":true'; then
    token=$(echo "$login_response" | jq -r '.token' 2>/dev/null)
    if [ "$token" != "null" ] && [ -n "$token" ]; then
        echo -e "${GREEN}✅ Authentication successful${NC}"
        echo "Token: ${token:0:30}..."
    else
        echo -e "${RED}❌ Failed to extract token${NC}"
        echo "Response: $login_response"
        exit 1
    fi
else
    echo -e "${RED}❌ Authentication failed${NC}"
    echo "Response: $login_response"
    exit 1
fi
echo

# Function to test authenticated API endpoint
test_authenticated_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    
    echo -e "${YELLOW}Testing: $description${NC}"
    
    response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X $method "$BASE_URL$endpoint" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $token")
    
    http_code=$(echo $response | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    body=$(echo $response | sed -e 's/HTTPSTATUS\:.*//g')
    
    if [ $http_code -eq 200 ] || [ $http_code -eq 201 ]; then
        echo -e "${GREEN}✅ $description - SUCCESS (HTTP $http_code)${NC}"
        # Show a preview of the response
        if command -v jq >/dev/null 2>&1; then
            echo "$body" | jq -r '.message // .status // "Response received"' 2>/dev/null || echo "Data received successfully"
        fi
    else
        echo -e "${RED}❌ $description - FAILED (HTTP $http_code)${NC}"
        if [ ${#body} -lt 200 ]; then
            echo "Error: $body"
        fi
    fi
    echo "---"
}

# Test all ERP modules with authentication
echo -e "${PURPLE}📦 INVENTORY MANAGEMENT SYSTEM${NC}"
test_authenticated_endpoint "GET" "/inventory" "Inventory Items List"

echo -e "${PURPLE}👨‍🍳 RECIPE MANAGEMENT SYSTEM${NC}"  
test_authenticated_endpoint "GET" "/recipes" "Recipe Management"

echo -e "${PURPLE}📋 ORDER MANAGEMENT SYSTEM${NC}"
test_authenticated_endpoint "GET" "/orders" "Order Management"

echo -e "${PURPLE}🏭 PRODUCTION WORKFLOW SYSTEM${NC}"
test_authenticated_endpoint "GET" "/job-tickets" "Job Tickets Management"
test_authenticated_endpoint "GET" "/workflows" "Production Workflows"

echo -e "${PURPLE}🏪 MERCHANT MANAGEMENT SYSTEM${NC}"
test_authenticated_endpoint "GET" "/merchants" "Merchant Management"

echo -e "${PURPLE}💰 INVOICE MANAGEMENT SYSTEM${NC}"
test_authenticated_endpoint "GET" "/invoices" "Invoice Management"

echo -e "${PURPLE}🚛 SUPPLIER MANAGEMENT SYSTEM${NC}"
test_authenticated_endpoint "GET" "/suppliers/dashboard" "Supplier Dashboard"
test_authenticated_endpoint "GET" "/suppliers" "Supplier Management"

echo -e "${PURPLE}📄 PURCHASE ORDER SYSTEM${NC}"
test_authenticated_endpoint "GET" "/purchase-orders/dashboard" "Purchase Orders Dashboard"
test_authenticated_endpoint "GET" "/purchase-orders" "Purchase Orders Management"

echo -e "${PURPLE}🚚 DELIVERY & TRACKING SYSTEM${NC}"
test_authenticated_endpoint "GET" "/deliveries/dashboard" "Delivery Dashboard" 
test_authenticated_endpoint "GET" "/deliveries" "Delivery Management"

echo
echo -e "${BLUE}=== AUTHENTICATION TEST COMPLETE ===${NC}"
echo -e "${GREEN}🎉 ERP System tested with proper authentication! 🎉${NC}"
echo