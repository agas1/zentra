#!/bin/sh
set -e

cd /var/www/html

# Cache config for production
php artisan config:cache
php artisan view:cache

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Run migrations and seed plans
php artisan migrate --force
php artisan db:seed --class=PlanSeeder --force 2>/dev/null || true

# Start supervisor (nginx + php-fpm + queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
