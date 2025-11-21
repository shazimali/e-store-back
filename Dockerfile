# ============================================
# Stage 1: PHP + Composer (Vendor)
# ============================================
FROM php:8.2-cli AS vendor

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zlib1g-dev libxml2-dev pkg-config libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip intl gd curl fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Increase Composer memory and run install
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy the rest of Laravel app
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
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip intl curl fileinfo \
    && a2enmod rewrite headers ssl \
    && rm -rf /var/lib/apt/lists/*

# Fix Apache FQDN warning
RUN echo "ServerName everydaypak.com" >> /etc/apache2/apache2.conf

# Copy Apache virtual host
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/everyday_store/backend

# Copy Laravel app from vendor stage
COPY --from=vendor /app ./

# Copy startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Set Laravel storage & cache permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["startup.sh"]
