# Stage 1: Vendor / Composer
FROM php:8.2-apache-bullseye AS vendor

# Install system dependencies + required PHP extensions for Laravel
RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zlib1g-dev libxml2-dev pkg-config libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip intl gd curl fileinfo opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files for caching
COPY composer.json composer.lock ./

# Run Composer install as root, memory unlimited
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy Laravel source code
COPY . .



# Stage 2: Production image
FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl vim nano \
    certbot python3-certbot-apache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache config
RUN echo "ServerName everydaypak.com" >> /etc/apache2/apache2.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/everyday_store/backend

# Copy app from vendor stage
COPY --from=vendor /app ./

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Set Laravel permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["startup.sh"]
