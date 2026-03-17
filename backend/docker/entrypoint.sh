#!/bin/sh
set -e

cd /var/www/html

# Cache config for production
php artisan config:cache
php artisan route:clear
php artisan view:cache

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Run migrations and seed plans
php artisan migrate --force
php artisan db:seed --class=PlanSeeder --force 2>/dev/null || true

# Setup admin accounts via env vars (no credentials in code)
# ADMIN_EMAIL: upgrade this user's workspace to Business plan
# ADMIN_MEMBER_EMAIL: add as admin to ADMIN_EMAIL's workspace
# ADMIN_MEMBER_PASSWORD: reset password for ADMIN_MEMBER_EMAIL
if [ -n "$ADMIN_EMAIL" ]; then
  php artisan tinker --execute="
  \$user = \App\Domain\User\Models\User::where('email', env('ADMIN_EMAIL'))->first();
  if (\$user) {
      \$plan = \App\Domain\Plan\Models\Plan::where('slug', 'business')->first();
      if (\$plan) {
          foreach (\$user->workspaces as \$ws) { \$ws->update(['plan_id' => \$plan->id]); }
          echo 'Upgraded to Business';
      }
  }
  " 2>/dev/null || true
fi

if [ -n "$ADMIN_MEMBER_EMAIL" ] && [ -n "$ADMIN_EMAIL" ]; then
  php artisan tinker --execute="
  \$owner = \App\Domain\User\Models\User::where('email', env('ADMIN_EMAIL'))->first();
  \$member = \App\Domain\User\Models\User::where('email', env('ADMIN_MEMBER_EMAIL'))->first();
  if (\$owner && \$member) {
      \$ws = \$owner->workspaces()->first();
      if (\$ws && !\$ws->members()->where('user_id', \$member->id)->exists()) {
          \$ws->members()->attach(\$member->id, ['role' => 'admin']);
          echo 'Added member as admin';
      }
  }
  " 2>/dev/null || true
fi

if [ -n "$ADMIN_MEMBER_EMAIL" ] && [ -n "$ADMIN_MEMBER_PASSWORD" ]; then
  php artisan tinker --execute="
  \$user = \App\Domain\User\Models\User::where('email', env('ADMIN_MEMBER_EMAIL'))->first();
  if (\$user) { \$user->password = \Illuminate\Support\Facades\Hash::make(env('ADMIN_MEMBER_PASSWORD')); \$user->save(); echo 'Password reset'; }
  " 2>/dev/null || true
fi

# Start supervisor (nginx + php-fpm + queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
