FROM php:8.2-fpm

# Install system dependencies including PostgreSQL client libraries and intl dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev postgresql-client zip unzip \
    libicu-dev \  
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions with proper PostgreSQL support and intl
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for dependency installation
COPY composer.json composer.lock ./

# Install dependencies (skip scripts that require .env)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the application
COPY . .

# Remove any existing .env files to prevent conflicts
RUN rm -f .env .env.example

# Copy build script and make it executable
COPY build.sh /usr/local/bin/build.sh
RUN chmod +x /usr/local/bin/build.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 10000

# Use build script as entrypoint
CMD ["/usr/local/bin/build.sh"]