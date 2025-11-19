#!/bin/bash
set -e

echo "Starting SuiteCRM with extended functionalities..."

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Create config_override.php if it doesn't exist (for environment variable loading)
if [ ! -f "/var/www/html/config_override.php" ]; then
    cp /var/www/html/config_override.php.template /var/www/html/config_override.php
    chown www-data:www-data /var/www/html/config_override.php
fi

# Install custom modules ONLY if SuiteCRM is already installed
if [ -f "/var/www/html/config.php" ] && [ ! -f "/var/www/html/.modules_installed" ]; then
    echo "Installing custom modules..."
    /usr/local/bin/install-modules.sh || true
    touch /var/www/html/.modules_installed
fi

# Start cron service
service cron start || true

# Execute the main container command
exec "$@"
