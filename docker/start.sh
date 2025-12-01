#!/bin/bash

# Start PHP-FPM
php-fpm -D

# Wait for PHP-FPM to start
sleep 2

# Run Laravel setup commands (only if .env exists)
if [ -f .env ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Start Nginx
nginx -g 'daemon off;'

