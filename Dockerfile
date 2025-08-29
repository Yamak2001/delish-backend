FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    cron

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies (including dev for Telescope)
RUN composer install --no-interaction --optimize-autoloader

# Create storage and cache directories with proper structure
RUN mkdir -p storage/app/public \
    storage/app/private/whatsapp/images \
    storage/app/private/whatsapp/videos \
    storage/app/private/whatsapp/audio \
    storage/app/private/whatsapp/documents \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache

# Run Laravel post-install commands (only those that don't need DB)
RUN php artisan storage:link || true
RUN php artisan telescope:publish --force || true

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Copy nginx config
RUN echo "server { \
    listen 80; \
    index index.php index.html; \
    root /var/www/public; \
    location ~ \.php$ { \
        try_files \$uri =404; \
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
        fastcgi_param PATH_INFO \$fastcgi_path_info; \
    } \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
}" > /etc/nginx/sites-available/default

# Copy cron file
COPY docker/cron/laravel-cron /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron \
    && crontab /etc/cron.d/laravel-cron \
    && touch /var/log/cron.log

# Supervisor config
RUN echo "[supervisord] \n\
nodaemon=true \n\
[program:nginx] \n\
command=/usr/sbin/nginx -g 'daemon off;' \n\
autostart=true \n\
autorestart=true \n\
[program:php-fpm] \n\
command=php-fpm \n\
autostart=true \n\
autorestart=true \n\
[program:cron] \n\
command=cron -f \n\
autostart=true \n\
autorestart=true" > /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord"]