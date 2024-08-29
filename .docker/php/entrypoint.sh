#!/usr/bin/env bash
composer install -n
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:database:create --if-not-exists --no-interaction --env=test
php bin/console doctrine:schema:update --no-interaction --force --complete --env=test
exec "$@"

