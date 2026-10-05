#!/bin/sh
# ComplySmart Backend Entrypoint
# Note: No set -e — we handle errors manually

echo "==> Starting ComplySmart Backend..."

# ── 1. Always run composer install to ensure vendor is correct ────────────
# This is critical because the host bind-mount may have an old/wrong vendor.
# The named vendor volume will persist this across restarts.
echo "==> Running composer install to sync dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
if [ $? -ne 0 ]; then
    echo "WARNING: composer install failed. Trying composer update..."
    composer update --no-interaction --prefer-dist --optimize-autoloader --no-dev
    if [ $? -ne 0 ]; then
        echo "ERROR: Both composer install and update failed!"
        exit 1
    fi
fi

# ── 2. Generate APP_KEY if missing or empty ───────────────────────────────
APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env 2>/dev/null | sed 's/APP_KEY=//' | tr -d '"')
if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" = "base64:" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── 3. Generate JWT_SECRET if missing ─────────────────────────────────────
JWT_SECRET_VALUE=$(grep -E '^JWT_SECRET=' .env 2>/dev/null | sed 's/JWT_SECRET=//' | tr -d '"')
if [ -z "$JWT_SECRET_VALUE" ]; then
    echo "==> JWT_SECRET not found. Generating via openssl..."
    JWT_GENERATED=$(openssl rand -base64 64 | tr -d '\n=')
    echo "JWT_SECRET=${JWT_GENERATED}" >> .env
    echo "==> JWT_SECRET appended to .env"
fi

# ── 4. Ensure storage directories exist and are writable ──────────────────
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ── 5. Clear old cache and re-cache with fresh config ─────────────────────
echo "==> Clearing old cache..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "==> Running package discovery..."
php artisan package:discover --ansi 2>/dev/null || true

echo "==> Caching config and routes..."
php artisan config:cache
if [ $? -ne 0 ]; then
    echo "WARNING: config:cache failed — running without config cache"
fi

php artisan route:cache
if [ $? -ne 0 ]; then
    echo "WARNING: route:cache failed — running without route cache"
fi

# ── 6. Run database migrations (creates jobs collection in MongoDB) ────────
echo "==> Running migrations..."
php artisan migrate --force 2>/dev/null || echo "WARNING: migrate failed (may already be up to date)"

# ── 7. Start PHP-FPM in background ────────────────────────────────────────
echo "==> Starting PHP-FPM..."
php-fpm -D
if [ $? -ne 0 ]; then
    echo "ERROR: PHP-FPM failed to start!"
    exit 1
fi

# ── 8. Start queue worker in background (processes OCR jobs async) ─────────
echo "==> Starting queue worker..."
php artisan queue:work --sleep=3 --tries=1 --timeout=120 --queue=default \
    >> storage/logs/queue.log 2>&1 &
echo "    Queue worker PID: $!"

# ── 9. Start Nginx in foreground (PID 1) ──────────────────────────────────
echo "==> Starting Nginx..."
exec nginx -g "daemon off;"
