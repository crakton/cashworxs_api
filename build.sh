#!/bin/bash

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Run production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check if migrations need to run (only on first deploy)
if [ ! -f "/var/www/html/storage/framework/migrations_ran" ]; then
    # Wait for database to be ready (important for Render)
    echo "Waiting for database to be ready..."
    for i in {1..10}; do
        php artisan db:show > /dev/null 2>&1 && break
        sleep 2
    done

    # Run migrations and seeding
    php artisan migrate --force
    php artisan db:seed --force
    
    # Create marker file to prevent future runs
    touch /var/www/html/storage/framework/migrations_ran
else
    echo "Migrations already run - skipping"
fi