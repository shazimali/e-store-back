#!/bin/bash
set -e

echo "⏳ Waiting for database connection..."

# Wait until database is ready — timeout after 60 seconds
MAX_TRIES=20
COUNT=0
until php artisan migrate:status > /dev/null 2>&1; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "❌ Database not ready after ${MAX_TRIES} attempts. Starting anyway..."
        break
    fi
    echo "Waiting for database... (attempt $COUNT/$MAX_TRIES)"
    sleep 3
done

echo "✅ Database check done"

# Generate APP_KEY if missing (check env var, not file — .env is not inside the container)
if [ -z "$APP_KEY" ]; then
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

# Run Laravel optimizations AFTER migrations so caches are consistent
echo "🚀 Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

# Start Apache in foreground
echo "✅ Starting Apache server..."
exec apache2-foreground
