FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
      libpq-dev libzip-dev zip unzip netcat-openbsd git \
    && docker-php-ext-install pdo pdo_pgsql zip pcntl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN sed -i 's/\r$//' docker/entrypoint.sh
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-recloset.ini
RUN composer config --no-plugins policy.advisories.block false 2>/dev/null || true
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
