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
fi

# Fix permissions for node_modules/.bin executables (needed for Vite)
if [ -d "node_modules/.bin" ]; then
    find node_modules/.bin -type f -exec chmod +x {} \; 2>/dev/null || true
fi

# Always ensure Vite assets are built (check if build directory exists and has manifest)
if [ ! -d "public/build" ] || [ ! -f "public/build/.vite/manifest.json" ]; then
    echo "Building Vite assets..."
    npm run build
fi

# Set proper permissions (directories 755, files 644)
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Restore execute permissions for binaries in node_modules/.bin (after chmod 644)
if [ -d "node_modules/.bin" ]; then
    find node_modules/.bin -type f -exec chmod +x {} \; 2>/dev/null || true
fi

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Specifically ensure build directory has correct permissions
if [ -d "public/build" ]; then
    chown -R www-data:www-data /var/www/html/public/build
    find /var/www/html/public/build -type d -exec chmod 755 {} \; 2>/dev/null || true
    find /var/www/html/public/build -type f -exec chmod 644 {} \; 2>/dev/null || true
fi

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

