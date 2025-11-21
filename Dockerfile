# ============================================
# Stage 1: Composer + PHP dependencies
# ============================================
FROM php:8.2-apache-bullseye AS vendor

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zlib1g-dev libxml2-dev pkg-config libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip intl gd curl fileinfo opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy full Laravel app
COPY . .


# ============================================
# Stage 2: Final Production Image
# ============================================
FROM php:8.2-apache

# Install system dependencies for Apache
RUN apt-get update && apt-get install -y \
    git unzip curl vim nano \
    certbot python3-certbot-apache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix Apache FQDN warning
RUN echo "ServerName everydayplastic.co" >> /etc/apache2/apache2.conf

# Copy Apache vhost
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/everyday_store/backend

# Copy Laravel app from vendor stage
COPY --from=vendor /app ./

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Set Laravel storage/cache permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["startup.sh"]
