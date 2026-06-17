FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libxml2-dev \
    libonig-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml ctype fileinfo opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application files
COPY . .

# Run composer post-install scripts (register autoloaders etc.)
RUN composer run-script post-autoload-dump --no-interaction || true

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Fallback env defaults — Railway overrides these at runtime via service variables
ENV APP_NAME=Jeevalink \
    APP_ENV=production \
    APP_KEY=base64:47wk2MEQHOtHS8Yb8bdOv0gtQDPJc7b8W6UaxryrUfg= \
    APP_DEBUG=false \
    APP_URL=http://localhost \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=error \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

# Copy and prepare entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Railway dynamically assigns PORT at runtime
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
