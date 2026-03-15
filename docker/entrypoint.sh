#!/bin/sh
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
composer install
php artisan key:generate --no-interaction
php artisan config:clear
php artisan migrate --seed --no-interaction
php-fpm
