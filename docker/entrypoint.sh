#!/bin/sh
composer install
php artisan key:generate --no-interaction
php artisan config:clear
php artisan migrate --seed --no-interaction
php-fpm
