#!/bin/sh
php artisan migrate --force
exec "$@"
