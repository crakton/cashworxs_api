#!/bin/bash

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate app key if not exists
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Wait for database to be ready (up to 30 seconds)
echo "Waiting for database to be ready..."
for i in {1..30}; do
    php artisan db:show > /dev/null 2>&1 && break
    echo "Attempt $i: Database not ready, waiting..."
    sleep 2
done

# Run migrations and seeding if needed
if php artisan db:show > /dev/null 2>&1; then
    php artisan migrate --force
    
    # Only seed if this is the first deployment
    if [ ! -f "storage/framework/migrations_ran" ]; then
        php artisan db:seed --force
        touch storage/framework/migrations_ran
    fi
else
    echo "ERROR: Could not connect to database after 30 seconds"
    exit 1
fi