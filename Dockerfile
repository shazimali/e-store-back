# ==================================================
# Stage 1: Base PHP Image
# ==================================================
FROM php:8.2-apache AS base

RUN apt-get update && apt-get install -y \
    git unzip curl supervisor \
    libzip-dev libonig-dev libxml2-dev libicu-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    gd \
    bcmath \
    exif \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/everyday_store/backend

# ==================================================
# Stage 2: Composer Dependencies (CACHEABLE)
# ==================================================
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1

COPY composer.json composer.lock ./

RUN composer validate --no-check-publish

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==================================================
# Stage 3: Final Runtime Image
# ==================================================
FROM base

# Copy vendor folder only
COPY --from=vendor /app/vendor ./vendor

# Copy application source AFTER vendor
COPY . .

# Apache config
RUN echo "ServerName api.everydaypak.com" >> /etc/apache2/apache2.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Permissions (only required paths)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["startup.sh"]
