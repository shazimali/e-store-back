FROM php:8.2-apache

# --------------------------------------------------
# PHP Extensions & System Packages
# --------------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libonig-dev zlib1g-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# Working directory
# --------------------------------------------------
WORKDIR /var/www/everyday_store/backend

# --------------------------------------------------
# Install Composer
# --------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --------------------------------------------------
# Copy Laravel Project Files
# --------------------------------------------------
COPY . .

# --------------------------------------------------
# Install PHP Dependencies (Laravel 9)
# --------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# --------------------------------------------------
# Apache Configuration
# --------------------------------------------------
RUN echo "ServerName api.everydaypak.com" >> /etc/apache2/apache2.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# --------------------------------------------------
# Startup Script
# --------------------------------------------------
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# --------------------------------------------------
# Permissions (VERY IMPORTANT FOR 500 ERROR FIX)
# --------------------------------------------------
RUN chown -R www-data:www-data /var/www/everyday_store/backend \
    && chmod -R 775 /var/www/everyday_store/backend/storage \
    && chmod -R 775 /var/www/everyday_store/backend/bootstrap/cache

# --------------------------------------------------
# Expose port
# --------------------------------------------------
EXPOSE 80

CMD ["startup.sh"]
