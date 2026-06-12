#!/usr/bin/env bash
set -euo pipefail

cp .env.ci .env
php artisan key:generate --force
php artisan config:clear

./vendor/bin/pint --test
php tools/check-native-types.php
./vendor/bin/phpstan analyse --no-progress --memory-limit=2G
php artisan test --coverage --min=50
