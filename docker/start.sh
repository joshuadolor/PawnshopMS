#!/bin/bash

# Install dependencies if vendor directory is empty or missing
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
    echo "Installing Composer dependencies..."
    composer install --optimize-autoloader --no-dev --no-interaction
fi

# Install Node dependencies if node_modules is empty or missing
if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules)" ]; then
    echo "Installing Node dependencies..."
    npm install
    echo "Building assets..."
    npm run build
fi

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

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

