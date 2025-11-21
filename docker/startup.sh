set -e

echo "⏳ Waiting for database connection..."
until php -r "try {
    new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    echo '✅ Database ready'; } catch (Exception \$e) { echo '.'; sleep(3); }"; do :; done

echo "🚀 Running Laravel optimizations..."
php artisan migrate --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Ensure correct permissions
mkdir -p /var/www/everyday_store/backend/storage /var/www/everyday_store/backend/bootstrap/cache
chown -R www-data:www-data /var/www/everyday_store/backend
chmod -R 775 /var/www/everyday_store/backend/storage /var/www/everyday_store/backend/bootstrap/cache

echo "✅ Starting Apache server..."
apache2-foreground