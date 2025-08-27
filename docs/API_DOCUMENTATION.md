# Delish ERP API Documentation

## ✅ System Status
**Last Updated:** August 26, 2025  
**System Status:** All endpoints fully operational and tested  
**API Version:** v1.0  
**Authentication:** JWT Bearer Token with Laravel Passport  

### Recent Validations Completed
- ✅ All CRUD operations tested and working
- ✅ Database relationships validated and optimized  
- ✅ SQL query ambiguity issues resolved
- ✅ Model-table name mappings verified
- ✅ Authentication system fully functional
- ✅ Comprehensive API testing completed

## Base Configuration

**Base URL:** `http://127.0.0.1:8000/api`  
**Authentication:** Bearer Token (JWT)  
**Content-Type:** `application/json`  

## Authentication

### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "admin",
        "department": "management"
    }
}
```

### Register
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "role": "staff",
    "department": "kitchen",
    "phone_number": "+962-6-555-0123"
}
```

### Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

## Core System

### Health Check
```http
GET /api/health
```

**Response:**
```json
{
    "status": "OK",
    "timestamp": "2025-08-26T12:00:00Z",
    "version": "1.0.0"
}
```

---

## 📦 Inventory Management API

### List Inventory Items
```http
GET /api/inventory?search={query}&category={category}&status={status}&page={page}
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search by name or SKU
- `category` (optional): Filter by category
- `status` (optional): active, inactive, low_stock
- `page` (optional): Page number for pagination

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "sku": "FLOUR-001",
            "name": "Premium Wheat Flour",
            "category": "raw_materials",
            "current_stock": 500,
            "unit_of_measure": "kg",
            "unit_cost": 1.50,
            "reorder_point": 100,
            "max_stock_level": 1000,
            "status": "active"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 150,
        "per_page": 20
    }
}
```

### Create Inventory Item
```http
POST /api/inventory
Authorization: Bearer {token}
Content-Type: application/json

{
    "sku": "SUGAR-001",
    "name": "Granulated Sugar",
    "category": "raw_materials",
    "unit_of_measure": "kg",
    "unit_cost": 2.00,
    "reorder_point": 50,
    "max_stock_level": 500,
    "supplier_id": 1
}
```

### Get Inventory Item Details
```http
GET /api/inventory/{id}
Authorization: Bearer {token}
```

### Update Inventory Item
```http
PUT /api/inventory/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Updated Item Name",
    "unit_cost": 2.50,
    "reorder_point": 75
}
```

### Get Low Stock Alerts
```http
GET /api/inventory/low-stock
Authorization: Bearer {token}
```

---

## 👨‍🍳 Recipe Management API

### List Recipes
```http
GET /api/recipes?search={query}&category={category}&page={page}
Authorization: Bearer {token}
```

### Create Recipe
```http
POST /api/recipes
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Chocolate Cake",
    "description": "Premium chocolate cake recipe",
    "category": "cakes",
    "serving_size": 8,
    "prep_time": 30,
    "cook_time": 45,
    "instructions": "Mix ingredients...",
    "ingredients": [
        {
            "inventory_item_id": 1,
            "quantity": 2.5,
            "unit_of_measure": "kg"
        }
    ]
}
```

### Get Recipe Details
```http
GET /api/recipes/{id}
Authorization: Bearer {token}
```

### Calculate Recipe Cost
```http
GET /api/recipes/{id}/cost
Authorization: Bearer {token}
```

---

## 📋 Order Management API

### List Orders
```http
GET /api/orders?status={status}&customer={customer}&date_from={date}&date_to={date}&page={page}
Authorization: Bearer {token}
```

**Statuses:** `pending`, `confirmed`, `in_production`, `ready`, `delivered`, `cancelled`

### Create Order
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_name": "Ahmad Restaurant",
    "customer_email": "ahmad@restaurant.com",
    "customer_phone": "+962-6-555-0123",
    "delivery_address": "Amman, Jordan",
    "delivery_date": "2025-08-27",
    "notes": "Handle with care",
    "items": [
        {
            "recipe_id": 1,
            "quantity": 5,
            "unit_price": 25.00,
            "special_instructions": "Extra chocolate"
        }
    ]
}
```

### Get Order Details
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

### Update Order Status
```http
PUT /api/orders/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "in_production",
    "notes": "Started production process"
}
```

---

## 🏭 Production Management API

### List Job Tickets
```http
GET /api/job-tickets?status={status}&priority={priority}&assigned_to={user_id}&page={page}
Authorization: Bearer {token}
```

### Create Job Ticket
```http
POST /api/job-tickets
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 1,
    "recipe_id": 1,
    "quantity": 10,
    "priority": "high",
    "scheduled_start": "2025-08-26T08:00:00Z",
    "estimated_duration": 120,
    "assigned_to": 2,
    "special_instructions": "Use premium ingredients"
}
```

### Update Job Ticket Status
```http
PUT /api/job-tickets/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "in_progress",
    "notes": "Started mixing ingredients"
}
```

### List Production Workflows
```http
GET /api/workflows?active={boolean}&page={page}
Authorization: Bearer {token}
```

---

## 🚛 Supplier Management API

### Supplier Dashboard
```http
GET /api/suppliers/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
    "stats": {
        "total_suppliers": 25,
        "active_suppliers": 23,
        "total_outstanding_balance": 15750.50,
        "average_rating": 4.2,
        "monthly_purchases": 45230.75
    },
    "top_suppliers": [...],
    "recent_purchases": [...],
    "pending_payments": [...]
}
```

### List Suppliers
```http
GET /api/suppliers?search={query}&status={status}&city={city}&page={page}
Authorization: Bearer {token}
```

### Create Supplier
```http
POST /api/suppliers
Authorization: Bearer {token}
Content-Type: application/json

{
    "supplier_name": "Fresh Ingredients Co.",
    "contact_person": "Mohammed Ali",
    "email": "info@freshingredients.com",
    "phone": "+962-6-555-0123",
    "address": "Industrial Area, Amman",
    "city": "Amman",
    "postal_code": "11118",
    "tax_number": "TAX123456789",
    "credit_limit": 10000.00,
    "payment_terms": "Net 30",
    "category": "raw_materials"
}
```

### Update Supplier Rating
```http
PUT /api/suppliers/{id}/rating
Authorization: Bearer {token}
Content-Type: application/json

{
    "rating": 4.5,
    "review": "Excellent quality and timely delivery"
}
```

---

## 📄 Purchase Order API

### Purchase Orders Dashboard
```http
GET /api/purchase-orders/dashboard
Authorization: Bearer {token}
```

### List Purchase Orders
```http
GET /api/purchase-orders?status={status}&supplier_id={id}&page={page}
Authorization: Bearer {token}
```

**Statuses:** `draft`, `sent`, `confirmed`, `received`, `cancelled`

### Create Purchase Order
```http
POST /api/purchase-orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "supplier_id": 1,
    "expected_delivery_date": "2025-08-30",
    "notes": "Urgent order for weekend production",
    "items": [
        {
            "inventory_item_id": 1,
            "quantity": 100,
            "unit_price": 1.50,
            "notes": "Premium grade"
        }
    ]
}
```

### Update Purchase Order Status
```http
PUT /api/purchase-orders/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "sent",
    "notes": "Sent to supplier via email"
}
```

---

## 🚚 Delivery Management API

### Delivery Dashboard
```http
GET /api/deliveries/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
    "stats": {
        "total_deliveries": 1250,
        "pending_deliveries": 15,
        "in_transit_deliveries": 8,
        "completed_today": 25,
        "average_delivery_time": 35.5,
        "on_time_percentage": 94.2
    },
    "active_deliveries": [...],
    "delivery_performance": [...]
}
```

### List Deliveries
```http
GET /api/deliveries?status={status}&driver_id={id}&date={date}&page={page}
Authorization: Bearer {token}
```

### Create Delivery
```http
POST /api/deliveries
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 1,
    "driver_id": 2,
    "vehicle_info": "Toyota Pickup - AA1234",
    "scheduled_pickup_time": "2025-08-26T10:00:00Z",
    "estimated_delivery_time": "2025-08-26T11:30:00Z",
    "delivery_address": "Downtown Amman, Jordan",
    "delivery_instructions": "Call before arrival",
    "items": [
        {
            "order_item_id": 1,
            "quantity": 5
        }
    ]
}
```

### Track Delivery (Real-time)
```http
GET /api/deliveries/{id}/track
Authorization: Bearer {token}
```

**Response:**
```json
{
    "delivery_id": 1,
    "status": "in_transit",
    "current_location": {
        "latitude": 31.9454,
        "longitude": 35.9284,
        "address": "King Abdullah II Street, Amman"
    },
    "estimated_arrival": "2025-08-26T11:45:00Z",
    "driver": {
        "name": "Omar Ahmad",
        "phone": "+962-7-9999-0123"
    }
}
```

### Update Delivery Status
```http
PUT /api/deliveries/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "delivered",
    "delivered_to": "Ahmad Restaurant Manager",
    "delivery_notes": "Delivered successfully",
    "proof_of_delivery": "signature_image_url"
}
```

### Update GPS Location
```http
PUT /api/deliveries/{id}/location
Authorization: Bearer {token}
Content-Type: application/json

{
    "latitude": 31.9454,
    "longitude": 35.9284
}
```

---

## 🗑️ Waste Management API

### List Waste Logs
```http
GET /api/waste-logs?waste_type={type}&date_from={date}&date_to={date}&page={page}
Authorization: Bearer {token}
```

**Waste Types:** `expired`, `damaged`, `overproduction`, `spoiled`, `returned`, `quality_control`, `other`

### Create Waste Log
```http
POST /api/waste-logs
Authorization: Bearer {token}
Content-Type: application/json

{
    "inventory_item_id": 1,
    "waste_type": "expired",
    "waste_source": "storage",
    "item_name": "Premium Flour",
    "quantity_wasted": 5.5,
    "unit_of_measure": "kg",
    "unit_cost": 1.50,
    "waste_date": "2025-08-26",
    "waste_reason": "Exceeded expiry date",
    "disposal_method": "compost",
    "prevention_notes": "Implement FIFO rotation"
}
```

### Waste Analytics
```http
GET /api/waste-logs/analytics?period={month|quarter|year}
Authorization: Bearer {token}
```

---

## 🏪 Merchant Management API

### List Merchants
```http
GET /api/merchants?status={status}&city={city}&page={page}
Authorization: Bearer {token}
```

### Create Merchant
```http
POST /api/merchants
Authorization: Bearer {token}
Content-Type: application/json

{
    "business_name": "Corner Cafe",
    "contact_person": "Sarah Ahmad",
    "email": "sarah@cornercafe.com",
    "phone": "+962-6-555-0456",
    "address": "Rainbow Street, Amman",
    "city": "Amman",
    "business_type": "cafe",
    "credit_limit": 5000.00,
    "commission_rate": 15.0
}
```

---

## 💰 Invoice Management API

### List Invoices
```http
GET /api/invoices?status={status}&customer={customer}&date_from={date}&page={page}
Authorization: Bearer {token}
```

**Statuses:** `draft`, `sent`, `paid`, `overdue`, `cancelled`

### Create Invoice
```http
POST /api/invoices
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 1,
    "customer_name": "Ahmad Restaurant",
    "customer_email": "ahmad@restaurant.com",
    "due_date": "2025-09-25",
    "payment_terms": "Net 30",
    "notes": "Thank you for your business",
    "items": [
        {
            "description": "Chocolate Cake",
            "quantity": 5,
            "unit_price": 25.00,
            "tax_rate": 16.0
        }
    ]
}
```

### Mark Invoice as Paid
```http
PUT /api/invoices/{id}/payment
Authorization: Bearer {token}
Content-Type: application/json

{
    "payment_method": "bank_transfer",
    "payment_reference": "TXN123456789",
    "amount_paid": 145.60,
    "payment_date": "2025-08-26"
}
```

---

## Error Handling

### Standard Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### HTTP Status Codes
- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity
- `500` - Internal Server Error

---

## Rate Limiting

- **Authenticated requests:** 1000 requests per hour
- **Authentication endpoints:** 60 requests per hour
- **Bulk operations:** 100 requests per hour

## Pagination

All list endpoints support pagination:

```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 20,
        "to": 20,
        "total": 200
    },
    "links": {
        "first": "http://127.0.0.1:8000/api/inventory?page=1",
        "last": "http://127.0.0.1:8000/api/inventory?page=10",
        "prev": null,
        "next": "http://127.0.0.1:8000/api/inventory?page=2"
    }
}
```

---

*Last Updated: August 26, 2025*