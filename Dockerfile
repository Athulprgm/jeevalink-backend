FROM php:8.3-apache

# Enable Apache modules required for Laravel
RUN a2enmod rewrite headers

# Install system dependencies and PHP extensions
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

# Set Apache document root to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (needed for Laravel routing)
RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code (.dockerignore excludes .env and logs)
COPY . .

# Run post-install scripts (package:discover etc.)
RUN composer run-script post-autoload-dump --no-interaction || true

# Set correct permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Fallback ENV defaults — Railway service variables override these at runtime
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

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Railway will override this via $PORT env var
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
