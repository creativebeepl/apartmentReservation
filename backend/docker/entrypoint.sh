#!/bin/sh
set -e
cd /app

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p var
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:seed

exec "$@"
