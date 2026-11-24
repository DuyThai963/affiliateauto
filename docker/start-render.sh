#!/bin/sh
set -e

PORT_TO_USE="${PORT:-10000}"
echo "[INIT] Starting AffiliateAuto on Render..."
echo "[INIT] Assigned internal port: ${PORT_TO_USE}"

# 1. Setup clean Nginx site config without broken symlinks
rm -rf /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* /etc/nginx/conf.d/*
mkdir -p /etc/nginx/conf.d /etc/nginx/sites-enabled

# Place single clean config into sites-enabled/default (as regular file, not broken symlink)
sed "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/render.conf > /etc/nginx/sites-enabled/default

# Test Nginx syntax and print result
echo "[INIT] Testing Nginx configuration..."
nginx -t

# 2. Fix PostgreSQL SSL and user environment for www-data
echo "[INIT] Configuring environment & permissions..."
chmod 755 /root
mkdir -p /var/www/.postgresql
chown -R www-data:www-data /var/www/.postgresql
chmod 755 /var/www/.postgresql

# 3. Ensure all storage directories and log file exist with full write permissions
echo "[INIT] Initializing storage directories..."
mkdir -p /var/www/storage/logs \
         /var/www/storage/framework/cache \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/app/browser_profiles \
         /var/www/storage/app/buffer_vault \
         /var/www/storage/app/temp_assets \
         /var/www/storage/app/cloud_vault_5tb \
         /var/www/bootstrap/cache

touch /var/www/storage/logs/laravel.log

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# 3. Database migrations (creates tables including jobs, cache, sessions, channels, etc.)
echo "[INIT] Running database migrations..."
php /var/www/artisan migrate --force --no-interaction || true

# 4. Cache optimizations & Queue restart
echo "[INIT] Caching Laravel config, routes, views..."
php /var/www/artisan config:cache || true
php /var/www/artisan route:cache || true
php /var/www/artisan view:cache || true
php /var/www/artisan queue:restart || true

# 5. Re-enforce permissions after artisan commands
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

echo "[INIT] All systems ready! Handing off to supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
