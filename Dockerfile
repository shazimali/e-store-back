FROM php:8.2-apache

# Install minimal system dependencies + required PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libonig-dev zlib1g-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/everyday_store/backend

# Copy Laravel project
COPY . .

# Install Composer dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Apache configuration
RUN echo "ServerName everydaypak.com" >> /etc/apache2/apache2.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Set storage/cache permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["startup.sh"]
