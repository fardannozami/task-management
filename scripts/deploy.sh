#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/task-management"
BACKEND="$APP_DIR/backend"
FRONTEND="$APP_DIR/frontend"

echo "==> Backend: installing dependencies"
cd "$BACKEND"
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

echo "==> Backend: running migrations"
sudo -u www-data env HOME=/tmp php artisan migrate --force

echo "==> Backend: rebuilding caches"
sudo -u www-data env HOME=/tmp php artisan config:cache
sudo -u www-data env HOME=/tmp php artisan route:cache
sudo -u www-data env HOME=/tmp php artisan view:cache
sudo -u www-data env HOME=/tmp php artisan storage:link || true

echo "==> Frontend: installing dependencies"
cd "$FRONTEND"
corepack prepare pnpm@10.14.0 --activate >/dev/null 2>&1 || true
pnpm install --frozen-lockfile

echo "==> Frontend: building"
pnpm build

echo "==> Restarting services"
sudo systemctl restart task-frontend task-reverb task-queue

echo "==> Deploy complete"
