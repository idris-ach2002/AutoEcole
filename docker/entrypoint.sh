#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

php bin/console doctrine:database:create --if-not-exists --no-interaction || true
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ "${AUTOECOLE_SEED:-0}" = "1" ]; then
  php bin/console app:seed-demo --no-interaction || true
fi

php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

chown -R www-data:www-data var public || true
exec "$@"
