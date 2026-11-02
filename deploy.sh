#!/usr/bin/env bash
# Deploy del Aula Virtual — se corre a mano desde /var/www/aulavirtual:  ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"
echo "==> Aula Virtual: deploy desde main"

php artisan down --retry=15 || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

git fetch origin main
git reset --hard origin/main

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo "   (sin npm: se usa public/build del repo)"
fi

php artisan migrate --force

[ -L public/storage ] || php artisan storage:link

mkdir -p storage/app/respaldos
sudo chown -R "$USER":www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache

php artisan optimize:clear
php artisan optimize

# Que los procesos de la cola tomen el código nuevo
php artisan queue:restart

php artisan up
echo "==> Listo: https://aulavirtual.migestion.com.ar"
