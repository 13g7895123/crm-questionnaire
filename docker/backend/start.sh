#!/bin/bash
set -e

cd /var/www/html

# Install or update Composer dependencies
# Using --no-dev for production, remove it for development
echo "Checking Composer dependencies..."
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "Updating Composer dependencies..."
    # Use install instead of update to respect composer.lock
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure writable directory exists and has correct permissions
mkdir -p writable/logs writable/cache writable/session writable/uploads writable/debugbar
chmod -R 777 writable

echo "Starting PHP development server..."
php spark serve --host 0.0.0.0 --port 8080
