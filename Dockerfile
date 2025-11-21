# ============================================
# Stage 1: Vendor / Composer
# ============================================
FROM php:8.2-apache-bullseye AS vendor

# Install minimal system dependencies (no compiling needed)
RUN apt-get update && apt-get install -y \
    git unzip curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (no-dev)
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy the rest of the Laravel project
COPY . .


# ============================================
# Stage 2: Final production image
# ============================================
FROM php:8.2-apache-bullseye

# Minimal system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl vim nano \
    certbot python3-certbot-apache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache settings
RUN echo "ServerName everydaypak.com" >> /etc/apache2/apache2.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/everyday_store/backend

# Copy Laravel app from vendor stage
COPY --from=vendor /app ./

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Set permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["startup.sh"]
