# ============================================
# Stage 1: Composer dependencies (Vendor)
# ============================================
FROM composer:2 AS vendor

# Install required PHP extensions for Composer to run successfully
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip intl gd

WORKDIR /app

# Copy only composer files first (cache-friendly)
COPY composer.json composer.lock ./

# Install PHP dependencies (no-dev for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Now copy the rest of the application source
COPY . .



# ============================================
# Stage 2: Final Laravel production image
# ============================================
FROM php:8.2-apache

# Install system dependencies required by Laravel + Apache
RUN apt-get update && apt-get install -y \
    git curl zip unzip vim nano \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libxml2-dev libicu-dev libonig-dev \
    certbot python3-certbot-apache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip intl \
    && a2enmod rewrite headers ssl \
    && rm -rf /var/lib/apt/lists/*

# Fix FQDN warning
RUN echo "ServerName everydaypak.com" >> /etc/apache2/apache2.conf

# Copy Apache virtual host config
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/everyday_store/backend

# Copy vendor + Laravel code from composer stage
COPY --from=vendor /app ./

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Laravel storage & cache permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["startup.sh"]
