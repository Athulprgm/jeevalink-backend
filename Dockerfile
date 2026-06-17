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

# Copy and prepare entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Railway injects PORT dynamically — do NOT hardcode
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
