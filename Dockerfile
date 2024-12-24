FROM php:8.2-fpm

RUN apt-get update && apt-get install -y libpq-dev \
	&& docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html
WORKDIR /var/www/html

ENV APP_NAME=cashworx-api \
	APP_ENV=production \
	APP_DEBUG=false \
	DB_CONNECTION=pgsql \
	DB_HOST=dpg-ctlcgkrqf0us7387pgn0-a \
	DB_PORT=5432 \
	DB_DATABASE=cashworx_e51v \
	DB_USERNAME=crakton

CMD sh -c "php artisan migrate --force && php artisan serve --port=80"
