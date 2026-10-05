#!/bin/sh
# Shared entrypoint for backend/queue/reverb/scheduler.
# - Reinstalls deps if a fresh named volume hid the image's vendor/node_modules.
# - Generates APP_KEY on first run (persists to the bind-mounted .env).
# - Runs migrations under an atomic lock so all four services can boot together.
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ ! -f vendor/autoload.php ]; then
  echo "==> vendor/ missing, running composer install..."
  composer install --prefer-dist --optimize-autoloader
fi

if [ ! -d node_modules/puppeteer ]; then
  echo "==> node_modules/ missing, running npm ci (puppeteer for PDF reports)..."
  npm ci
  npx puppeteer browsers install chrome
fi

if [ -f .env ] && ! grep -q "^APP_KEY=.\+" .env; then
  echo "==> APP_KEY empty, generating..."
  php artisan key:generate
fi

# Bind mount hides the image's bootstrap/cache, so (re)discover providers at
# runtime too — covers fresh clones that never ran composer install on the host.
php artisan package:discover --ansi

php artisan storage:link 2>/dev/null || true

echo "==> running migrations..."
php artisan migrate --force --isolated

exec "$@"
