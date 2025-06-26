#!/bin/bash
set -e

echo "Starting Laravel application setup..."

# Create required directories
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set proper permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Always create .env file from environment variables (overwrite if exists)
echo "Creating .env file from environment variables..."
cat > .env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_TIMEZONE=${APP_TIMEZONE:-UTC}
APP_URL=${APP_URL:-http://localhost}
APP_LOCALE=${APP_LOCALE:-en}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-en}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-en_US}
APP_MAINTENANCE_STORE=${APP_MAINTENANCE_STORE:-database}

# Database Configuration
DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
PGSSLMODE=${PGSSLMODE:-require}

# Application Settings
PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-4}
BCRYPT_ROUNDS=${BCRYPT_ROUNDS:-12}

# Logging
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-single}
LOG_LEVEL=${LOG_LEVEL:-debug}

# Session
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=${SESSION_ENCRYPT:-false}
SESSION_PATH=${SESSION_PATH:-/}

# Cache
CACHE_DRIVER=${CACHE_DRIVER:-file}
CACHE_STORE=${CACHE_STORE:-file}

# Queue
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}

# Filesystem
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}

# JWT Authentication
JWT_SECRET=${JWT_SECRET}
JWT_TTL=${JWT_TTL:-1440}
JWT_REFRESH_TTL=${JWT_REFRESH_TTL:-43200}
JWT_ALGO=${JWT_ALGO:-HS256}

# Payment Gateway
PAYMENT_GATEWAY_KEY=${PAYMENT_GATEWAY_KEY}
PAYMENT_GATEWAY_SECRET=${PAYMENT_GATEWAY_SECRET}

# Termii SMS
TERMII_BASE_URI=${TERMII_BASE_URI:-https://api.ng.termii.com/api}
TERMII_API_SECRET=${TERMII_API_SECRET}
TERMII_API_KEY=${TERMII_API_KEY}

# Cloudinary
CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME}
CLOUDINARY_API_KEY=${CLOUDINARY_API_KEY}
CLOUDINARY_API_SECRET=${CLOUDINARY_API_SECRET}
CLOUDINARY_SECURE=${CLOUDINARY_SECURE:-true}
CLOUDINARY_URL=${CLOUDINARY_URL}

# CASHWORX Integration
CASHWORXS_BASE_URL=${CASHWORXS_BASE_URL}
CASHWORXS_ENABLED=${CASHWORXS_ENABLED:-true}
CASHWORXS_WEBHOOK_URL=${CASHWORXS_WEBHOOK_URL}

# Paystack Payment Gateway
PAYSTACK_PAYMENT_URL=${PAYSTACK_PAYMENT_URL:-https://api.paystack.co}
PAYSTACK_PUBLIC_KEY=${PAYSTACK_PUBLIC_KEY}
PAYSTACK_SECRET_KEY=${PAYSTACK_SECRET_KEY}

# OneSignal Push Notifications
ONESIGNAL_API_KEY=${ONESIGNAL_API_KEY}
ONESIGNAL_API_APP_ID=${ONESIGNAL_API_APP_ID}
FCM_SERVER_KEY=${FCM_SERVER_KEY}
EOF

# Generate app key if needed
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "generateValue" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Test database connection with timeout
echo "Testing database connection..."
timeout 60 bash -c '
until php artisan db:show 2>/dev/null; do
    echo "Waiting for database connection... (retry in 3 seconds)"
    sleep 3
done
'

if [ $? -ne 0 ]; then
    echo "ERROR: Database connection timeout after 60 seconds"
    echo "Please check your database credentials and ensure the database is running"
    exit 1
fi

echo "Database connection successful!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed database only on first deployment
if [ ! -f "storage/framework/seeded" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
    touch storage/framework/seeded
fi

# Clear and cache configurations
echo "Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Application setup complete!"
echo "Starting PHP server on port 10000..."

# Start the server
exec php artisan serve --host=0.0.0.0 --port=10000