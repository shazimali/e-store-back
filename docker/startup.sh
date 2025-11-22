#!/bin/bash
set -e

echo "⏳ Waiting for database connection..."

# Wait until database is ready (adjust DB_HOST and DB_PORT from .env)
until php artisan migrate:status > /dev/null 2>&1; do
    echo "Waiting for database..."
    sleep 3
done

echo "✅ Database ready"

# Run Laravel optimizations
echo "🚀 Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

# Generate APP_KEY if missing
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🗝️ Generating APP_KEY..."
    php artisan key:generate --force
else
    echo "✅ APP_KEY already set"
fi

# Run pending migrations
echo "🔁 Running migrations..."
php artisan migrate --force || true

# Fix storage symlink
echo "🔗 Fixing storage link..."
php artisan storage:link || true

# Clear caches
echo "🧹 Clearing cache..."
php artisan optimize:clear || true

# Start Apache in foreground
echo "✅ Starting Apache server..."
apache2-foreground
