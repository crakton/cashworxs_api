FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (no-dev for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Production optimizations
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Use the PORT environment variable provided by Render
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]