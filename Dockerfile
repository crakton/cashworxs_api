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
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy ALL application files first
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies without running scripts initially
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Now run the post-autoload scripts (this will work because artisan file is present)
RUN composer run-script post-autoload-dump

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Create bootstrap/cache directory if it doesn't exist
RUN mkdir -p bootstrap/cache && \
    chown -R www-data:www-data bootstrap/cache

# Production optimizations (only if .env exists or you handle the missing key gracefully)
RUN php artisan config:cache || true && \
    php artisan route:cache || true && \
    php artisan view:cache || true

# Expose port
EXPOSE 10000

# Use the PORT environment variable provided by Render
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]