#!/bin/sh
set -e

PORT_TO_USE="${PORT:-10000}"

sed "s/__PORT__/${PORT_TO_USE}/g" /etc/nginx/sites-available/render.conf > /etc/nginx/sites-enabled/default
rm -f /etc/nginx/sites-available/default

php /var/www/artisan migrate --force --no-interaction || true
php /var/www/artisan config:cache || true
php /var/www/artisan view:cache || true

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
