# Delish ERP System

## Overview

Delish ERP is a comprehensive Enterprise Resource Planning system designed specifically for bakery and food production businesses. Built with Laravel 12.x, it provides complete end-to-end business management from inventory tracking to customer delivery.

## 🏗️ System Architecture

### Core Technologies
- **Backend**: Laravel 12.x with PHP 8.3
- **Database**: MySQL 8.0+ with advanced indexing
- **Authentication**: Laravel Passport (JWT tokens)
- **API**: RESTful API design
- **Real-time Features**: GPS tracking, live status updates

### Key Business Modules

#### 🔐 Authentication & User Management
- JWT-based authentication with Laravel Passport
- Role-based access control (Admin, Manager, Staff)
- Department-based user organization
- Secure API token management

#### 📦 Inventory Management System
- Real-time stock tracking with FIFO logic
- Automated reorder point alerts
- Comprehensive item categorization
- Unit conversion and measurement tracking
- Expiry date management and alerts

#### 👨‍🍳 Recipe Management System
- Ingredient requirement calculation
- Cost analysis per recipe
- Production quantity scaling
- Nutritional information tracking
- Recipe version control

#### 📋 Order Management System
- Customer order processing
- Order status workflow management
- Integration with production planning
- Order fulfillment tracking
- Customer communication automation

#### 🏭 Production Workflow & Job Tickets
- Production job scheduling
- Workflow step management
- Quality control checkpoints
- Production time tracking
- Resource allocation optimization

#### 🏪 Merchant Management System
- Multi-merchant support
- Custom pricing structures
- Performance analytics
- Revenue tracking per merchant
- Contract management

#### 💰 Invoice Management System
- Automated invoice generation
- Payment tracking and reconciliation
- Tax calculation and reporting
- Credit management
- Financial reporting integration

#### 🚛 Supplier Management System
- Comprehensive supplier profiles
- Performance rating system
- Credit limit management
- Purchase order automation
- Supplier analytics and reporting

#### 📄 Purchase Order System
- Multi-approval workflow
- Automated reorder suggestions
- Goods receipt verification
- Invoice matching
- Vendor performance tracking

#### 🚚 Delivery & Tracking System
- Real-time GPS tracking
- Route optimization
- Driver assignment and management
- Delivery performance metrics
- Customer delivery notifications

#### 🗑️ Waste Management System
- Comprehensive waste logging
- Cost analysis and reporting
- Prevention strategy tracking
- Disposal method documentation
- Sustainability reporting

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- MySQL 8.0+
- Composer 2.0+
- Node.js 18+ (for frontend assets)

### Installation
```bash
# Clone the repository
git clone <repository-url>
cd delish-backend

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env file
# DB_DATABASE=delish_erp
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate --seed

# Install Passport
php artisan passport:install

# Start development server
php artisan serve
```

## 📊 Database Schema

### Core Tables
- `users` - User authentication and profiles
- `inventory_items` - Product and ingredient catalog
- `recipes` - Recipe definitions and costs
- `orders` - Customer order management
- `job_tickets` - Production workflow tracking

### Business Logic Tables
- `suppliers` - Supplier management
- `purchase_orders` - Procurement workflow
- `deliveries` - Delivery tracking
- `waste_logs` - Waste management
- `invoices` - Financial management

### Integration Tables
- `purchase_order_items` - Purchase order line items
- `delivery_items` - Delivery tracking items
- `recipe_ingredients` - Recipe component mapping
- `order_items` - Order line items

## 🔌 API Documentation

Base URL: `http://127.0.0.1:8000/api`

### Authentication Endpoints
```http
POST /api/login
POST /api/register
POST /api/logout
GET  /api/user
```

### Core Business Endpoints

#### Inventory Management
```http
GET    /api/inventory              # List all inventory items
POST   /api/inventory              # Create new item
GET    /api/inventory/{id}         # Get specific item
PUT    /api/inventory/{id}         # Update item
DELETE /api/inventory/{id}         # Delete item
GET    /api/inventory/low-stock    # Get low stock alerts
```

#### Order Management
```http
GET    /api/orders                 # List orders
POST   /api/orders                 # Create order
GET    /api/orders/{id}            # Get order details
PUT    /api/orders/{id}/status     # Update order status
```

#### Supplier Management
```http
GET    /api/suppliers/dashboard    # Supplier analytics dashboard
GET    /api/suppliers              # List suppliers
POST   /api/suppliers              # Create supplier
PUT    /api/suppliers/{id}/rating  # Update supplier rating
```

#### Delivery Tracking
```http
GET    /api/deliveries/dashboard   # Delivery analytics
GET    /api/deliveries/{id}/track  # Real-time tracking
PUT    /api/deliveries/{id}/status # Update delivery status
PUT    /api/deliveries/{id}/location # Update GPS location
```

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### API Testing Scripts
The system includes comprehensive testing scripts:

```bash
# Test complete ERP system
./test-complete-erp-system.sh

# Test with authentication
./test-authenticated-erp.sh

# Test specific modules
./test-supplier-system.sh
./test-inventory-simple.sh
```

## 📈 Business Intelligence & Analytics

### Dashboard Metrics
- Real-time inventory levels
- Order fulfillment rates
- Supplier performance scores
- Delivery efficiency metrics
- Waste cost analysis
- Revenue per merchant

### Reporting Features
- Financial performance reports
- Inventory movement analysis
- Production efficiency metrics
- Supplier comparison analytics
- Delivery route optimization reports

## 🔒 Security Features

### Authentication Security
- JWT token-based authentication
- Role-based access control
- Department-level permissions
- API rate limiting
- Request validation and sanitization

### Data Protection
- Encrypted sensitive data storage
- Audit trail logging
- Backup and recovery procedures
- GDPR compliance ready

## 🌟 Advanced Features

### Real-time Capabilities
- GPS-based delivery tracking
- Live inventory updates
- Real-time order status notifications
- Production workflow monitoring

### Business Intelligence
- Predictive analytics for reorder points
- Supplier performance scoring
- Route optimization algorithms
- Waste reduction insights

### Scalability Features
- Multi-tenant architecture ready
- Horizontal scaling support
- Caching layer implementation
- Database optimization

## 📞 Support & Maintenance

### Monitoring
- Application health checks
- Performance monitoring
- Error tracking and alerting
- Database performance optimization

### Backup & Recovery
- Automated daily backups
- Point-in-time recovery
- Disaster recovery procedures
- Data migration tools

## 🚀 Deployment

### Production Environment
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Queue workers
php artisan queue:work
```

### Environment Configuration
- Production database setup
- SSL certificate configuration
- Environment variable security
- Performance optimization settings

---

## 📋 System Status
**Last Updated:** August 26, 2025

✅ **Fully Implemented & Tested Modules:**
- Core Authentication & User Management
- Inventory Management with FIFO logic
- Recipe Management with costing
- Order Management with workflow
- Production Management with job tickets
- Merchant Management with analytics *(Recently debugged & validated)*
- Invoice Management with automation
- Supplier Management with performance tracking
- Purchase Order System with approval workflow *(Recently debugged & validated)*
- Delivery & Tracking with GPS integration *(Recently debugged & validated)*
- Waste Management with cost analysis

🎯 **System Readiness:** Production Ready - All endpoints operational

📊 **Test Coverage:** Comprehensive API testing suite implemented and validated

🔐 **Security Status:** JWT authentication with Laravel Passport fully functional

🛠️ **Recent System Validations (Aug 26, 2025):**
- ✅ All API endpoints tested and confirmed working
- ✅ Database relationship integrity validated
- ✅ SQL query optimization completed
- ✅ Model-table mappings verified and corrected
- ✅ Authentication token system fully operational

---

*Last Updated: August 26, 2025*
*Version: 1.0.0*