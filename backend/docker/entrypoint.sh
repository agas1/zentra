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

# Upgrade dev account to Business plan
php artisan tinker --execute="
\$user = \App\Domain\User\Models\User::where('email', 'dev@master.com.br')->first();
if (\$user) {
    \$plan = \App\Domain\Plan\Models\Plan::where('slug', 'business')->first();
    if (\$plan) {
        foreach (\$user->workspaces as \$ws) {
            \$ws->update(['plan_id' => \$plan->id]);
        }
        echo 'Upgraded to Business';
    }
}
" 2>/dev/null || true

# Start supervisor (nginx + php-fpm + queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
