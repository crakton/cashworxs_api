FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
	libpq-dev \
	git \
	zip \
	unzip \
	&& docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html
WORKDIR /var/www/html

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

ENV APP_NAME=cashworx-api \
	APP_ENV=production \
	APP_DEBUG=false \
	DB_CONNECTION=pgsql \
	DB_HOST=dpg-ctlcgkrqf0us7387pgn0-a \
	DB_PORT=5432 \
	DB_DATABASE=cashworx_e51v \
	DB_USERNAME=crakton

CMD sh -c "php artisan migrate --force && php artisan serve --port=80"
