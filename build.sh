#!/bin/bash

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Check if this is the first deployment
if [ ! -f "storage/framework/migrations_ran" ]; then
    # Wait for database to be ready
    echo "Waiting for database to be ready..."
    for i in {1..10}; do
        php artisan db:show > /dev/null 2>&1 && break
        sleep 2
    done

    # Run migrations and seeding
    php artisan migrate --force
    php artisan db:seed --force
    
    # Create marker file
    touch storage/framework/migrations_ran
fi