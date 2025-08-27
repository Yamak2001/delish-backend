# Installation Guide - Delish ERP System

## System Requirements

### Minimum Requirements
- **PHP:** 8.3 or higher
- **MySQL:** 8.0 or higher
- **Composer:** 2.0 or higher
- **Node.js:** 18 or higher
- **npm:** 8 or higher

### Recommended Specifications
- **Memory:** 4GB RAM minimum, 8GB recommended
- **Storage:** 10GB free space minimum
- **CPU:** 2 cores minimum, 4 cores recommended

### PHP Extensions Required
```bash
php-cli
php-fpm
php-mysql
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-json
php-tokenizer
php-fileinfo
php-ctype
```

## Installation Steps

### 1. Clone the Repository
```bash
git clone <repository-url> delish-erp
cd delish-erp/delish-backend
```

### 2. Install Dependencies

#### Install PHP Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

#### Install Node.js Dependencies
```bash
npm install
npm run build
```

### 3. Environment Configuration

#### Copy Environment File
```bash
cp .env.example .env
```

#### Generate Application Key
```bash
php artisan key:generate
```

#### Configure Environment Variables
Edit `.env` file with your settings:

```env
# Application
APP_NAME="Delish ERP"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_TIMEZONE=Asia/Amman
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delish_erp
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@delisherp.com"
MAIL_FROM_NAME="Delish ERP"

# JWT Configuration
JWT_SECRET=your-jwt-secret-key
JWT_TTL=1440

# File Storage
FILESYSTEM_DISK=local

# Queue Configuration
QUEUE_CONNECTION=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 4. Database Setup

#### Create Database
```sql
CREATE DATABASE delish_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Run Migrations
```bash
# Run database migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Or combine both
php artisan migrate --seed
```

### 5. Authentication Setup

#### Install Laravel Passport
```bash
# Install Passport
php artisan passport:install

# Generate encryption keys
php artisan passport:keys

# Create default clients
php artisan passport:client --personal --name="Delish ERP Personal Access Client"
```

### 6. Storage and Permissions

#### Create Storage Links
```bash
php artisan storage:link
```

#### Set Proper Permissions
```bash
# For Linux/Mac
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 7. Optimization for Production

#### Cache Configuration
```bash
# Cache configuration files
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## Docker Installation (Alternative)

### Using Docker Compose

Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
    volumes:
      - ./storage:/var/www/html/storage
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: delish_erp
      MYSQL_USER: delish
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./public:/var/www/html/public
    depends_on:
      - app

volumes:
  mysql_data:
```

### Run with Docker
```bash
# Build and start containers
docker-compose up -d

# Run migrations inside container
docker-compose exec app php artisan migrate --seed

# Install Passport
docker-compose exec app php artisan passport:install
```

## Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/delish-erp`:
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/delish-erp/delish-backend/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # API routes
    location ~ ^/api/ {
        try_files $uri $uri/ /index.php?$query_string;
        
        # CORS headers for API
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Content-Type, Authorization, Accept" always;
        
        if ($request_method = 'OPTIONS') {
            return 204;
        }
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# SSL Configuration (after obtaining certificate)
server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Include the same configuration as above
    root /var/www/delish-erp/delish-backend/public;
    # ... rest of configuration
}
```

### Apache Configuration

Create `.htaccess` in public directory:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# CORS for API endpoints
<LocationMatch "^/api/">
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, Accept"
</LocationMatch>
```

## SSL Certificate Setup

### Using Let's Encrypt (Certbot)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal setup
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Background Services

### Queue Worker Setup
```bash
# Create systemd service
sudo nano /etc/systemd/system/delish-queue.service
```

```ini
[Unit]
Description=Delish ERP Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5s
WorkingDirectory=/var/www/delish-erp/delish-backend
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl enable delish-queue
sudo systemctl start delish-queue
sudo systemctl status delish-queue
```

### Task Scheduler (Cron)
```bash
# Add to crontab
crontab -e

# Add this line
* * * * * cd /var/www/delish-erp/delish-backend && php artisan schedule:run >> /dev/null 2>&1
```

## Monitoring and Logging

### Log Rotation
Create `/etc/logrotate.d/delish-erp`:
```
/var/www/delish-erp/delish-backend/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    notifempty
    create 0644 www-data www-data
    postrotate
        systemctl reload php8.3-fpm
    endscript
}
```

## Security Checklist

### Application Security
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong `APP_KEY`
- [ ] Configure proper file permissions (755 for directories, 644 for files)
- [ ] Set up proper database user with limited privileges
- [ ] Configure firewall rules
- [ ] Set up SSL/TLS encryption
- [ ] Configure rate limiting
- [ ] Set up monitoring and alerting

### Database Security
```sql
-- Create dedicated database user
CREATE USER 'delish_app'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON delish_erp.* TO 'delish_app'@'localhost';
FLUSH PRIVILEGES;
```

## Backup Configuration

### Database Backup Script
```bash
#!/bin/bash
# File: /usr/local/bin/delish-backup.sh

DB_NAME="delish_erp"
DB_USER="backup_user"
DB_PASS="backup_password"
BACKUP_DIR="/backups/delish-erp"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz /var/www/delish-erp/delish-backend/storage

# Cleanup old backups (keep last 30 days)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

chmod +x /usr/local/bin/delish-backup.sh
```

### Cron job for backups
```bash
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/delish-backup.sh
```

## Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix storage permissions
sudo chmod -R 775 storage
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### Database Connection Issues
```bash
# Test database connection
php artisan tinker
# Then run: DB::connection()->getPdo();
```

#### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Queue Not Processing
```bash
# Check queue status
php artisan queue:work --once

# Restart queue workers
sudo systemctl restart delish-queue
```

### Performance Optimization

#### OPcache Configuration
Add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=12
opcache.max_accelerated_files=4000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

#### Database Optimization
```sql
-- Add indexes for better performance
ALTER TABLE orders ADD INDEX idx_status_created (status, created_at);
ALTER TABLE inventory_items ADD INDEX idx_category_status (category, status);
ALTER TABLE deliveries ADD INDEX idx_status_date (status, delivery_date);
```

## Post-Installation Verification

### System Health Check
```bash
# Check system status
php artisan health:check

# Verify API endpoints
curl -X GET "http://your-domain.com/api/health"

# Run test suite
php artisan test
```

### Initial Setup
1. Create admin user via seeder or tinker
2. Configure initial inventory items
3. Set up suppliers and merchants
4. Configure delivery zones
5. Test complete order workflow

---

**Installation Complete!** 🎉

Your Delish ERP system should now be running at `https://your-domain.com`

For support and troubleshooting, check the logs at `storage/logs/laravel.log`

*Last Updated: August 26, 2025*